<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlgorithmSetting;
use App\Models\ShiftRequirement;
use Illuminate\Http\Request;

class AlgorithmSettingsController extends Controller
{
    public function index()
    {
        // Автоматически инициализируем настройки если их нет
        if (AlgorithmSetting::count() === 0) {
            AlgorithmSetting::initializeDefaults();
        }

        // Получаем настройки по категориям
        $settingsFromDb = AlgorithmSetting::all();
        
        $settingsByCategory = $settingsFromDb->groupBy('category')->map(function ($items) {
            return $items->keyBy('key')->map(function ($item) {
                return [
                    'value' => AlgorithmSetting::castValue($item->value, $item->type),
                    'type' => $item->type,
                    'description' => $item->description,
                ];
            });
        })->toArray();

        // Стандартные значения для новых настроек
        $defaultSettings = [
            'general' => [
                'season' => [
                    'value' => 'season',
                    'type' => 'string',
                    'description' => 'Текущий сезон работы',
                ],
                'balance_workload' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Балансировка нагрузки между сотрудниками',
                ],
                'enable_shift_overlap' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Разрешить совмещение смен (утро+день, день+ночь)',
                ],
                'auto_reassign_unfilled' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Автоперераспределение при недокомплекте',
                ],
                'cross_training' => [
                    'value' => false,
                    'type' => 'boolean',
                    'description' => 'Разрешить работу в смежных должностях',
                ],
            ],
            'labor_law' => [
                'enforce_labor_law' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Проверка соответствия ТК РФ',
                ],
                'max_weekly_hours' => [
                    'value' => 40,
                    'type' => 'integer',
                    'description' => 'Максимальная недельная нагрузка (часы)',
                ],
                'min_rest_hours' => [
                    'value' => 11,
                    'type' => 'integer',
                    'description' => 'Минимальный отдых между сменами (часы)',
                ],
                'no_morning_after_night' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Запрет утренней смены после ночной',
                ],
                'shift_hours_morning' => [
                    'value' => 6,
                    'type' => 'integer',
                    'description' => 'Продолжительность утренней смены (часы)',
                ],
                'shift_hours_day' => [
                    'value' => 8,
                    'type' => 'integer',
                    'description' => 'Продолжительность дневной смены (часы)',
                ],
                'shift_hours_night' => [
                    'value' => 7,
                    'type' => 'integer',
                    'description' => 'Продолжительность ночной смены (часы)',
                ],
            ],
            'priority' => [
                'enable_priority_system' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Система приоритетов «раньше подал → выше приоритет»',
                ],
                'allow_forced_assignment' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Разрешить вынужденное назначение сотрудников',
                ],
                'priority_want_shift' => [
                    'value' => 100,
                    'type' => 'integer',
                    'description' => 'Вес приоритета: Хочу эту смену',
                ],
                'priority_no_preference' => [
                    'value' => 50,
                    'type' => 'integer',
                    'description' => 'Вес приоритета: Без пожеланий',
                ],
            ],
            'notification' => [
                'notify_forced_assignment' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Уведомлять о вынужденном назначении',
                ],
                'notify_law_violation' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Уведомлять о нарушениях ТК РФ',
                ],
                'notify_schedule_ready' => [
                    'value' => true,
                    'type' => 'boolean',
                    'description' => 'Уведомлять о готовности графика',
                ],
            ],
        ];

        // Объединяем дефолтные настройки с существующими из БД
        foreach ($defaultSettings as $category => $categorySettings) {
            foreach ($categorySettings as $key => $default) {
                if (!isset($settingsByCategory[$category][$key])) {
                    $settingsByCategory[$category][$key] = $default;
                }
            }
        }

        // Преобразуем настройки в плоскую структуру для формы
        $settingsForForm = [];
        foreach ($settingsByCategory as $category => $categorySettings) {
            foreach ($categorySettings as $key => $setting) {
                $settingsForForm[$key] = $setting['value'];
            }
        }

        // Получаем текущий сезон
        $currentSeason = $settingsForForm['season'] ?? 'season';
        
        // Получаем полные требования с 3 измерениями
        $requirements = ShiftRequirement::where('season', $currentSeason)->get();
        
        // Создаем полную 3D структуру
        $staffRequirements3D = [];
        foreach ($requirements as $req) {
            if (!isset($staffRequirements3D[$req->role])) {
                $staffRequirements3D[$req->role] = [];
            }
            if (!isset($staffRequirements3D[$req->role][$req->shift_type])) {
                $staffRequirements3D[$req->role][$req->shift_type] = [];
            }
            $staffRequirements3D[$req->role][$req->shift_type][$req->day_type] = $req->min_staff;
        }

        // Получаем все возможные значения для формы
        $allRoles = ShiftRequirement::ROLES;
        $allShiftTypes = ShiftRequirement::SHIFT_TYPES;
        $allDayTypes = ShiftRequirement::DAY_TYPES;
        
        // Определяем доступные смены для каждой роли
        $roleShifts = [
            'cook' => ['day', 'night'],
            'waiter' => ['morning', 'day', 'night'],
            'hostess' => ['morning', 'day', 'night'],
            'bartender' => ['day', 'night'],
            'admin' => ['day', 'night'],
        ];

        // Определения для таблицы требований
        $seasons = ['season', 'offseason'];

        // Статистика
        $statistics = $this->getStatistics();

        $roles = ShiftRequirement::ROLES;
        $shiftTypes = $roleShifts;
        $dayTypes = ShiftRequirement::DAY_TYPES;
        $requirements_grouped = ShiftRequirement::getGroupedRequirements($currentSeason);

        return view('admin.algorithm.algorithm-settings', compact(
            'settingsForForm',
            'staffRequirements3D',
            'currentSeason',
            'allRoles',
            'allShiftTypes',
            'allDayTypes',
            'roleShifts',
            'seasons',
            'statistics',
            'roles',
            'shiftTypes',
            'dayTypes',
            'requirements_grouped'
        ));
    }

    public function update(Request $request)
    {
        // Валидация входящих данных
        $validated = $request->validate([
            'season' => 'sometimes|string|in:season,offseason',
            'balance_workload' => 'sometimes|boolean',
            'enable_shift_overlap' => 'sometimes|boolean',
            'auto_reassign_unfilled' => 'sometimes|boolean',
            'enforce_labor_law' => 'sometimes|boolean',
            'max_weekly_hours' => 'sometimes|integer|min:1|max:60',
            'min_rest_hours' => 'sometimes|integer|min:1|max:24',
            'no_morning_after_night' => 'sometimes|boolean',
            'shift_hours_morning' => 'sometimes|integer|min:1|max:12',
            'shift_hours_day' => 'sometimes|integer|min:1|max:12',
            'shift_hours_night' => 'sometimes|integer|min:1|max:12',
            'enable_priority_system' => 'sometimes|boolean',
            'allow_forced_assignment' => 'sometimes|boolean',
            'priority_want_shift' => 'sometimes|integer|min:0|max:1000',
            'priority_no_preference' => 'sometimes|integer|min:0|max:1000',
            'notify_forced_assignment' => 'sometimes|boolean',
            'notify_law_violation' => 'sometimes|boolean',
            'notify_schedule_ready' => 'sometimes|boolean',
            'cross_training' => 'sometimes|boolean',
        ]);

        // Определяем категории для каждой настройки
        $categories = [
            'season' => 'general',
            'balance_workload' => 'general',
            'enable_shift_overlap' => 'general',
            'auto_reassign_unfilled' => 'general',
            'enforce_labor_law' => 'labor_law',
            'max_weekly_hours' => 'labor_law',
            'min_rest_hours' => 'labor_law',
            'no_morning_after_night' => 'labor_law',
            'shift_hours_morning' => 'labor_law',
            'shift_hours_day' => 'labor_law',
            'shift_hours_night' => 'labor_law',
            'enable_priority_system' => 'priority',
            'allow_forced_assignment' => 'priority',
            'priority_want_shift' => 'priority',
            'priority_no_preference' => 'priority',
            'notify_forced_assignment' => 'notification',
            'notify_law_violation' => 'notification',
            'notify_schedule_ready' => 'notification',
            'cross_training' => 'general',
        ];

        // Обновляем настройки
        foreach ($validated as $key => $value) {
            if (isset($categories[$key])) {
                $this->updateSetting($key, $value, $categories[$key]);
            }
        }

        // Кэшируем настройки для быстрого доступа
        $this->cacheSettings();

        return redirect()->back()->with('success', 'Настройки алгоритма успешно обновлены!');
    }

    /**
     * Обновляет одну настройку
     */
    private function updateSetting(string $key, $value, string $category): void
    {
        // Находим существующую настройку для получения описания
        $existing = AlgorithmSetting::where('key', $key)->first();
        
        // Определяем тип значения
        $type = $this->detectType($value);
        
        AlgorithmSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->prepareValueForStorage($value, $type),
                'type' => $type,
                'category' => $category,
                'description' => $existing->description ?? $this->getDefaultDescription($key, $category),
            ]
        );
    }

    /**
     * Определяет тип значения для настройки
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
            return 'boolean';
        } elseif (is_numeric($value) && floor($value) == $value) {
            return 'integer';
        } elseif (is_numeric($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'json';
        } else {
            return 'string';
        }
    }

    /**
     * Подготавливает значение для хранения
     */
    private function prepareValueForStorage($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Получает описание по умолчанию для настройки
     */
    private function getDefaultDescription(string $key, string $category): string
    {
        $descriptions = [
            'general' => [
                'season' => 'Текущий сезон работы',
                'balance_workload' => 'Балансировка нагрузки между сотрудниками',
                'enable_shift_overlap' => 'Разрешить совмещение смен',
                'auto_reassign_unfilled' => 'Автоперераспределение при недокомплекте',
                'cross_training' => 'Разрешить работу в смежных должностях',
            ],
            'labor_law' => [
                'enforce_labor_law' => 'Проверка соответствия ТК РФ',
                'max_weekly_hours' => 'Максимальная недельная нагрузка (часы)',
                'min_rest_hours' => 'Минимальный отдых между сменами (часы)',
                'no_morning_after_night' => 'Запрет утренней смены после ночной',
                'shift_hours_morning' => 'Продолжительность утренней смены (часы)',
                'shift_hours_day' => 'Продолжительность дневной смены (часы)',
                'shift_hours_night' => 'Продолжительность ночной смены (часы)',
            ],
            'priority' => [
                'enable_priority_system' => 'Система приоритетов «раньше подал → выше приоритет»',
                'allow_forced_assignment' => 'Разрешить вынужденное назначение сотрудников',
                'priority_want_shift' => 'Вес приоритета: Хочу эту смену',
                'priority_no_preference' => 'Вес приоритета: Без пожеланий',
            ],
            'notification' => [
                'notify_forced_assignment' => 'Уведомлять о вынужденном назначении',
                'notify_law_violation' => 'Уведомлять о нарушениях ТК РФ',
                'notify_schedule_ready' => 'Уведомлять о готовности графика',
            ],
        ];

        return $descriptions[$category][$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Кэширует настройки для быстрого доступа
     */
    private function cacheSettings(): void
    {
        $settings = AlgorithmSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => AlgorithmSetting::castValue($setting->value, $setting->type)];
        })->toArray();

        cache()->put('algorithm_settings', $settings, now()->addHours(24));
    }

    /**
     * Получает статистику работы алгоритма
     */
    private function getStatistics(): array
    {
        // Пока заглушка
        return [
            'total_generations' => 0,
            'successful' => 0,
            'with_violations' => 0,
            'avg_time' => 0,
            'last_generation' => null,
        ];
    }
}