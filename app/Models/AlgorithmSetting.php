<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlgorithmSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description', 'category'];

    // Категории настроек
    const CATEGORIES = [
        'general' => 'Основные',
        'labor_law' => 'ТК РФ',
        'priority' => 'Приоритеты',
        'notification' => 'Уведомления',
    ];

    // Типы значений
    const TYPES = [
        'boolean' => 'Да/Нет',
        'integer' => 'Число',
        'float' => 'Дробное число',
        'string' => 'Текст',
        'json' => 'JSON',
    ];

    /**
     * Получить значение настройки
     */
    public static function getValue(string $key, $default = null)
    {
        // Пробуем из кэша
        $settings = cache()->remember('algorithm_settings', 3600, function () {
            return self::all()->keyBy('key')->map(function ($setting) {
                return self::castValue($setting->value, $setting->type);
            });
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Установить значение настройки
     */
    public static function setValue(string $key, $value, string $category = 'general', ?string $description = null): void
    {
        $type = self::detectType($value);

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
                'category' => $category,
                'description' => $description ?? $key,
            ]
        );

        // Сбрасываем кэш
        cache()->forget('algorithm_settings');
    }

    /**
     * Приведение значения к нужному типу
     */
    /**
     * Приведение значения к нужному типу
     */
    public static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return (string) $value;
        }
    }

    /**
     * Подготовка значения для сохранения
     */
    private static function prepareValue($value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Определение типа значения
     */
    private static function detectType($value): string
    {
        if (is_bool($value) || in_array($value, ['0', '1', 'true', 'false'])) {
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
     * Получить все настройки по категориям
     */
    public static function getByCategories(): array
    {
        return self::all()
            ->groupBy('category')
            ->map(function ($settings) {
                return $settings->mapWithKeys(function ($setting) {
                    return [
                        $setting->key => [
                            'value' => self::castValue($setting->value, $setting->type),
                            'type' => $setting->type,
                            'description' => $setting->description,
                        ]
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Инициализировать настройки по умолчанию
     */
    public static function initializeDefaults(): void
    {
        $defaults = [
            // Основные
            [
                'key' => 'current_season',
                'value' => 'season',
                'type' => 'string',
                'description' => 'Текущий сезон работы',
                'category' => 'general',
            ],
            [
                'key' => 'balance_workload',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Балансировка нагрузки между сотрудниками',
                'category' => 'general',
            ],

            // ТК РФ
            [
                'key' => 'enforce_labor_law',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Проверка соответствия ТК РФ',
                'category' => 'labor_law',
            ],
            [
                'key' => 'max_weekly_hours',
                'value' => '40',
                'type' => 'integer',
                'description' => 'Максимальная недельная нагрузка (часы)',
                'category' => 'labor_law',
            ],
            [
                'key' => 'min_rest_hours',
                'value' => '11',
                'type' => 'integer',
                'description' => 'Минимальный отдых между сменами (часы)',
                'category' => 'labor_law',
            ],
            [
                'key' => 'no_morning_after_night',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Запрет утренней смены после ночной',
                'category' => 'labor_law',
            ],

            // Приоритеты
            [
                'key' => 'enable_priority_system',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Система приоритетов «раньше подал → выше приоритет»',
                'category' => 'priority',
            ],
            [
                'key' => 'allow_forced_assignment',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Разрешить вынужденное назначение сотрудников',
                'category' => 'priority',
            ],

            // Уведомления
            [
                'key' => 'notify_forced_assignment',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Уведомлять о вынужденном назначении',
                'category' => 'notification',
            ],
            [
                'key' => 'notify_law_violation',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Уведомлять о нарушениях ТК РФ',
                'category' => 'notification',
            ],
        ];

        foreach ($defaults as $default) {
            self::updateOrCreate(
                ['key' => $default['key']],
                $default
            );
        }
    }
}
