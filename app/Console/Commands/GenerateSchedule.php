<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use App\Models\ShiftRequirement;
use App\Models\AlgorithmSetting;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateSchedule extends Command
{
    protected $signature = 'schedule:generate {start_date?} {--season=}';
    protected $description = 'Генерирует двухнедельный график смен для всех ролей';

    private $shortages = [];

    public function handle()
    {
        $settings = $this->getAlgorithmSettings();

        $season = $this->option('season') ?? AlgorithmSetting::getValue('season', 'season');

        $startDate = $this->argument('start_date')
            ? Carbon::parse($this->argument('start_date'))
            : now()->startOfDay();

        $startDate = $startDate->startOfWeek();
        $endDate = $startDate->copy()->addDays(13);

        // ПОКАЗЫВАЕМ ТЕКУЩИЕ НАСТРОЙКИ
        $this->showCurrentSettings($settings);

        $this->info("🎯 Генерация графика с {$startDate->format('d.m.Y')} по {$endDate->format('d.m.Y')}");
        $this->info("Сезон: " . ($season === 'season' ? 'СЕЗОН' : 'МЕЖСЕЗОНЬЕ'));
        $this->info("Учет приоритета: РАНЬШЕ подал заявку → ВЫШЕ приоритет");

        if ($settings['enforce_labor_law']) {
            $this->info("Проверка ТК РФ: недельная норма {$settings['max_weekly_hours']}ч, отдых между сменами {$settings['min_rest_hours']}ч");
        } else {
            $this->warn("⚠️ Проверка ТК РФ ОТКЛЮЧЕНА");
        }

        ShiftAssignment::whereBetween('date', [$startDate, $endDate])->delete();

        $roles = ['cook', 'waiter', 'hostess', 'bartender', 'admin'];

        $totalAssignments = 0;
        $generationLog = [];

        foreach ($roles as $role) {
            $this->info("\n--- Обработка роли: {$role} ---");

            $assignmentsCount = $this->generateForRole($role, $startDate, $endDate, $season, $settings);
            $totalAssignments += $assignmentsCount;

            $generationLog[$role] = $assignmentsCount;
            $this->info("Назначено смен: {$assignmentsCount}");
        }

        $this->logGeneration($startDate, $endDate, $totalAssignments, $season, $settings);

        if (!empty($this->shortages)) {
            $this->warn("\n⚠️ ⚠️ ⚠️ ВНИМАНИЕ: Обнаружены неукомплектованные смены!");

            $groupedShortages = [];
            foreach ($this->shortages as $shortage) {
                $groupedShortages[$shortage['date']][] = $shortage;
            }

            foreach ($groupedShortages as $date => $shortagesList) {
                $dateFormatted = Carbon::parse($date)->format('d.m.Y');
                $this->warn("  📅 {$dateFormatted}:");
                foreach ($shortagesList as $shortage) {
                    $roleName = $this->getRoleName($shortage['role']);
                    $shiftName = $this->getShiftName($shortage['shift_type']);
                    $this->warn("    - {$roleName} на {$shiftName} ({$shortage['assigned']}/{$shortage['required']})");
                }
            }

            $this->saveShortagesToSession($startDate);
        }

        $this->info("\n✅ Генерация завершена! Всего назначений: {$totalAssignments}");
        $this->table(['Роль', 'Назначений'], collect($generationLog)->map(fn($count, $role) => [$role, $count]));

        if (!empty($this->shortages)) {
            $this->error("\n❌ График сгенерирован, но есть нехватки персонала!");
            return 1;
        }

        return 0;
    }

    /**
     * ПОКАЗЫВАЕТ ТЕКУЩИЕ НАСТРОЙКИ АЛГОРИТМА
     */
    private function showCurrentSettings(array $settings): void
    {
        $this->info("\n⚙️ ТЕКУЩИЕ НАСТРОЙКИ АЛГОРИТМА:");
        $this->info("┌────────────────────────────────────────────┐");
        $this->info("│ ОСНОВНЫЕ ПАРАМЕТРЫ                        │");
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ • Сезон: " . str_pad(($settings['season'] === 'season' ? 'СЕЗОН' : 'МЕЖСЕЗОНЬЕ'), 31) . "│");
        $this->info("│ • Балансировка нагрузки: " . str_pad(($settings['balance_workload'] ? 'ВКЛ' : 'ВЫКЛ'), 24) . "│");
        $this->info("│ • Совмещение смен: " . str_pad(($settings['enable_shift_overlap'] ? 'ВКЛ' : 'ВЫКЛ'), 30) . "│");
        $this->info("│ • Автоперераспределение: " . str_pad(($settings['auto_reassign_unfilled'] ? 'ВКЛ' : 'ВЫКЛ'), 26) . "│");
        $this->info("│ • Кросс-тренинг: " . str_pad(($settings['cross_training'] ?? false ? 'ВКЛ' : 'ВЫКЛ'), 32) . "│");
        
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ ТРУДОВОЙ КОДЕКС РФ                       │");
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ • Проверка ТК РФ: " . str_pad(($settings['enforce_labor_law'] ? 'ВКЛ' : 'ВЫКЛ'), 32) . "│");
        $this->info("│ • Макс. часов в неделю: " . str_pad($settings['max_weekly_hours'] . 'ч', 26, ' ', STR_PAD_LEFT) . "│");
        $this->info("│ • Мин. отдых между сменами: " . str_pad($settings['min_rest_hours'] . 'ч', 23, ' ', STR_PAD_LEFT) . "│");
        $this->info("│ • Запрет утра после ночи: " . str_pad(($settings['no_morning_after_night'] ? 'ДА' : 'НЕТ'), 25, ' ', STR_PAD_LEFT) . "│");
        
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ ПРОДОЛЖИТЕЛЬНОСТЬ СМЕН                  │");
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ • Утренняя смена: " . str_pad($settings['shift_hours_morning'] . 'ч', 31, ' ', STR_PAD_LEFT) . "│");
        $this->info("│ • Дневная смена: " . str_pad($settings['shift_hours_day'] . 'ч', 32, ' ', STR_PAD_LEFT) . "│");
        $this->info("│ • Ночная смена: " . str_pad($settings['shift_hours_night'] . 'ч', 33, ' ', STR_PAD_LEFT) . "│");
        
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ СИСТЕМА ПРИОРИТЕТОВ                    │");
        $this->info("├────────────────────────────────────────────┤");
        $this->info("│ • Система приоритетов: " . str_pad(($settings['enable_priority_system'] ? 'ВКЛ' : 'ВЫКЛ'), 28) . "│");
        $this->info("│ • Вынужденные назначения: " . str_pad(($settings['allow_forced_assignment'] ? 'РАЗРЕШЕНЫ' : 'ЗАПРЕЩЕНЫ'), 25) . "│");
        $this->info("│ • Вес: Хочу эту смену: " . str_pad($settings['priority_want_shift'], 28, ' ', STR_PAD_LEFT) . "│");
        $this->info("│ • Вес: Без пожеланий: " . str_pad($settings['priority_no_preference'], 29, ' ', STR_PAD_LEFT) . "│");
        
        $this->info("└────────────────────────────────────────────┘");
    }

    private function generateForRole(string $role, Carbon $startDate, Carbon $endDate, string $season, array $settings): int
    {
        $employees = Employee::where('role', $role)
            ->where('is_active', true)
            ->get();

        $assignmentsCount = 0;

        $weeklyHours = [];
        $employeeAssignments = [];
        $notGotPreferredShifts = [];

        if (!$employees->isEmpty()) {
            foreach ($employees as $employee) {
                $employeeAssignments[$employee->id] = [];
            }
        }

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayType = $this->getDayType($currentDate);

            $shiftTypes = $this->getShiftTypesForRole($role);
            $shiftTypes = $this->sortShiftsByTime($shiftTypes);

            $preferences = $employees->isEmpty() ? [] : $this->getPrioritizedPreferences($employees, $currentDate);

            foreach ($shiftTypes as $shiftType) {
                $minStaff = ShiftRequirement::getMinStaff($dayType, $shiftType, $role, $season);

                if ($minStaff <= 0) {
                    continue;
                }

                $this->info("  📅 {$currentDate->format('d.m.Y')} {$shiftType}: требуется {$minStaff} чел.");

                if ($employees->isEmpty()) {
                    $this->shortages[] = [
                        'date' => $currentDate->toDateString(),
                        'role' => $role,
                        'shift_type' => $shiftType,
                        'assigned' => 0,
                        'required' => $minStaff
                    ];
                    continue;
                }

                $candidatesByPreference = $this->groupCandidatesByPreference(
                    $employees,
                    $preferences,
                    $shiftType,
                    $currentDate,
                    $employeeAssignments,
                    $weeklyHours,
                    $settings
                );

                $assignedCount = $this->assignByPriority(
                    $candidatesByPreference,
                    $minStaff,
                    $currentDate,
                    $shiftType,
                    $employeeAssignments,
                    $weeklyHours,
                    $notGotPreferredShifts,
                    $preferences,
                    $settings
                );

                $assignmentsCount += $assignedCount;

                if ($assignedCount < $minStaff) {
                    $this->warn("    ⚠️ Недостаточно: нужно {$minStaff}, назначено {$assignedCount}");

                    $this->shortages[] = [
                        'date' => $currentDate->toDateString(),
                        'role' => $role,
                        'shift_type' => $shiftType,
                        'assigned' => $assignedCount,
                        'required' => $minStaff
                    ];
                } else {
                    $this->info("    👍 Укомплектовано: {$assignedCount}/{$minStaff}");
                }
            }

            $currentDate->addDay();
        }

        return $assignmentsCount;
    }

    private function filterCandidates(
        Employee $employee,
        string $shiftType,
        Carbon $date,
        array $employeeAssignments,
        array $weeklyHours,
        array $settings
    ): bool {
        if (!$settings['enforce_labor_law']) {
            return true;
        }

        $employeeId = $employee->id;

        // Проверка запрета утренней смены после ночной
        if ($settings['no_morning_after_night'] ?? true) {
            $previousDay = $date->copy()->subDay();
            if (isset($employeeAssignments[$employeeId][$previousDay->toDateString()])) {
                $previousShifts = $employeeAssignments[$employeeId][$previousDay->toDateString()];

                if (in_array('night', $previousShifts) && $shiftType === 'morning') {
                    return false;
                }
            }
        }

        // Проверка недельной нормы часов
        $weekStart = $date->copy()->startOfWeek();
        $currentWeekKey = $weekStart->toDateString();

        $shiftHours = $this->getShiftHours($shiftType, $employee->role);
        $currentWeekHours = $weeklyHours[$employeeId][$currentWeekKey] ?? 0;

        if (($currentWeekHours + $shiftHours) > $settings['max_weekly_hours']) {
            return false;
        }

        // Проверка на уже назначенную смену в этот день
        if (isset($employeeAssignments[$employeeId][$date->toDateString()])) {
            // Если совмещение смен разрешено, проверяем можно ли совмещать
            if ($settings['enable_shift_overlap'] ?? false) {
                $assignedShifts = $employeeAssignments[$employeeId][$date->toDateString()];
                
                // Проверяем можно ли совместить смены
                if (!$this->canCombineShifts($assignedShifts, $shiftType)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверяет можно ли совместить смены
     */
    private function canCombineShifts(array $assignedShifts, string $newShift): bool
    {
        $allowedCombinations = [
            ['morning', 'day'],
            ['day', 'night'],
        ];

        foreach ($assignedShifts as $assignedShift) {
            $combination = [$assignedShift, $newShift];
            sort($combination);
            
            if (!in_array($combination, $allowedCombinations)) {
                return false;
            }
        }

        return true;
    }

    private function groupCandidatesByPreference(
        Collection $employees,
        array $preferences,
        string $shiftType,
        Carbon $date,
        array &$employeeAssignments,
        array &$weeklyHours,
        array $settings
    ): array {
        $groups = [
            'want_this_shift' => [],
            'no_preference' => [],
            'want_day_off' => [],
        ];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $preference = $preferences[$employeeId] ?? null;

            if (!$this->filterCandidates($employee, $shiftType, $date, $employeeAssignments, $weeklyHours, $settings)) {
                continue;
            }

            if ($preference) {
                if ($preference['type'] === "prefer_{$shiftType}") {
                    $groups['want_this_shift'][$employeeId] = [
                        'employee' => $employee,
                        'submitted_at' => $preference['submitted_at'],
                        'preference_type' => $preference['type'],
                    ];
                } elseif ($preference['type'] === 'day_off') {
                    $groups['want_day_off'][$employeeId] = [
                        'employee' => $employee,
                        'submitted_at' => $preference['submitted_at'],
                        'preference_type' => $preference['type'],
                    ];
                }
            } else {
                $groups['no_preference'][$employeeId] = [
                    'employee' => $employee,
                    'submitted_at' => null,
                    'preference_type' => null,
                ];
            }
        }

        foreach ($groups as &$group) {
            uasort($group, function ($a, $b) {
                $timeA = $a['submitted_at'] ? Carbon::parse($a['submitted_at'])->timestamp : PHP_INT_MAX;
                $timeB = $b['submitted_at'] ? Carbon::parse($b['submitted_at'])->timestamp : PHP_INT_MAX;
                return $timeA <=> $timeB;
            });
        }

        return $groups;
    }

    private function assignByPriority(
        array $candidatesByPreference,
        int $minStaff,
        Carbon $date,
        string $shiftType,
        array &$employeeAssignments,
        array &$weeklyHours,
        array &$notGotPreferredShifts,
        array $preferences,
        array $settings
    ): int {
        $assignedCount = 0;
        $dateString = $date->toDateString();

        // 1. Назначаем тех, кто ХОЧЕТ эту смену
        foreach ($candidatesByPreference['want_this_shift'] as $employeeId => $candidate) {
            if ($assignedCount >= $minStaff) break;

            $employee = $candidate['employee'];

            if ($this->createAssignment($employee, $date, $shiftType, $employeeAssignments, $weeklyHours)) {
                $assignedCount++;
                $this->info("    ✅ {$employee->name} [хотел эту смену]");
            }
        }

        // Запоминаем тех, кто хотел смену, но не получил
        $remainingWantThisShift = array_slice($candidatesByPreference['want_this_shift'], $assignedCount);
        foreach ($remainingWantThisShift as $employeeId => $candidate) {
            $notGotPreferredShifts[$employeeId][$dateString] = [
                'employee' => $candidate['employee'],
                'desired_shift' => $shiftType,
                'priority' => 'high'
            ];
        }

        if ($assignedCount >= $minStaff) {
            return $assignedCount;
        }

        // 2. Назначаем тех, кто БЕЗ ПОЖЕЛАНИЙ
        foreach ($candidatesByPreference['no_preference'] as $employeeId => $candidate) {
            if ($assignedCount >= $minStaff) break;

            $employee = $candidate['employee'];

            if ($this->createAssignment($employee, $date, $shiftType, $employeeAssignments, $weeklyHours)) {
                $assignedCount++;
                $this->info("    👤 {$employee->name} [без пожеланий]");
            }
        }

        if ($assignedCount >= $minStaff) {
            return $assignedCount;
        }

        // 3. Если разрешены ВЫНУЖДЕННЫЕ НАЗНАЧЕНИЯ
        if ($settings['allow_forced_assignment']) {
            $stillNeeded = $minStaff - $assignedCount;

            if ($stillNeeded > 0) {
                $forcedCandidates = [];

                // Сначала те, кто не получил желаемую смену
                foreach ($notGotPreferredShifts as $employeeId => $shiftInfo) {
                    if (isset($candidatesByPreference['want_day_off'][$employeeId])) {
                        $forcedCandidates[] = [
                            'employee' => $candidatesByPreference['want_day_off'][$employeeId]['employee'],
                            'priority' => 1,
                            'submitted_at' => $candidatesByPreference['want_day_off'][$employeeId]['submitted_at'],
                        ];
                    }
                }

                // Потом те, кто хотел выходной
                foreach ($candidatesByPreference['want_day_off'] as $employeeId => $candidate) {
                    if (!isset($forcedCandidates[$employeeId])) {
                        $forcedCandidates[] = [
                            'employee' => $candidate['employee'],
                            'priority' => 2,
                            'submitted_at' => $candidate['submitted_at'],
                        ];
                    }
                }

                // Сортируем по приоритету и времени подачи
                usort($forcedCandidates, function ($a, $b) {
                    if ($a['priority'] === $b['priority']) {
                        $timeA = $a['submitted_at'] ? Carbon::parse($a['submitted_at'])->timestamp : PHP_INT_MAX;
                        $timeB = $b['submitted_at'] ? Carbon::parse($b['submitted_at'])->timestamp : PHP_INT_MAX;
                        return $timeA <=> $timeB;
                    }
                    return $a['priority'] <=> $b['priority'];
                });

                // Назначаем вынужденно
                foreach ($forcedCandidates as $candidate) {
                    if ($assignedCount >= $minStaff) break;

                    $employee = $candidate['employee'];

                    if ($this->createAssignment($employee, $date, $shiftType, $employeeAssignments, $weeklyHours)) {
                        $assignedCount++;
                        $priorityLabel = $candidate['priority'] === 1 ?
                            'не получил желанную смену' : 'хотел выходной';
                        $this->warn("    ⚠️ {$employee->name} [вынужденно, {$priorityLabel}]");
                    }
                }
            }
        }

        return $assignedCount;
    }

    private function createAssignment(
        Employee $employee,
        Carbon $date,
        string $shiftType,
        array &$employeeAssignments,
        array &$weeklyHours
    ): bool {
        try {
            ShiftAssignment::create([
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
                'shift_type' => $shiftType,
                'is_approved' => false,
            ]);

            $employeeId = $employee->id;
            $dateString = $date->toDateString();

            if (!isset($employeeAssignments[$employeeId][$dateString])) {
                $employeeAssignments[$employeeId][$dateString] = [];
            }
            $employeeAssignments[$employeeId][$dateString][] = $shiftType;

            $weekStart = $date->copy()->startOfWeek();
            $weekKey = $weekStart->toDateString();

            if (!isset($weeklyHours[$employeeId][$weekKey])) {
                $weeklyHours[$employeeId][$weekKey] = 0;
            }

            $shiftHours = $this->getShiftHours($shiftType, $employee->role);
            $weeklyHours[$employeeId][$weekKey] += $shiftHours;

            return true;
        } catch (\Exception $e) {
            $this->error("    ❌ Ошибка назначения {$employee->name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * ПОЛУЧАЕТ ВСЕ НАСТРОЙКИ АЛГОРИТМА ИЗ БАЗЫ ДАННЫХ
     */
    private function getAlgorithmSettings(): array
    {
        return [
            'season' => AlgorithmSetting::getValue('season', 'season'),
            'enforce_labor_law' => AlgorithmSetting::getValue('enforce_labor_law', true),
            'max_weekly_hours' => AlgorithmSetting::getValue('max_weekly_hours', 40),
            'min_rest_hours' => AlgorithmSetting::getValue('min_rest_hours', 11),
            'no_morning_after_night' => AlgorithmSetting::getValue('no_morning_after_night', true),
            'shift_hours_morning' => AlgorithmSetting::getValue('shift_hours_morning', 6),
            'shift_hours_day' => AlgorithmSetting::getValue('shift_hours_day', 8),
            'shift_hours_night' => AlgorithmSetting::getValue('shift_hours_night', 7),
            'enable_priority_system' => AlgorithmSetting::getValue('enable_priority_system', true),
            'allow_forced_assignment' => AlgorithmSetting::getValue('allow_forced_assignment', true),
            'balance_workload' => AlgorithmSetting::getValue('balance_workload', true),
            'enable_shift_overlap' => AlgorithmSetting::getValue('enable_shift_overlap', true),
            'auto_reassign_unfilled' => AlgorithmSetting::getValue('auto_reassign_unfilled', true),
            'cross_training' => AlgorithmSetting::getValue('cross_training', false),
            'priority_want_shift' => AlgorithmSetting::getValue('priority_want_shift', 100),
            'priority_no_preference' => AlgorithmSetting::getValue('priority_no_preference', 50),
            'notify_forced_assignment' => AlgorithmSetting::getValue('notify_forced_assignment', true),
            'notify_law_violation' => AlgorithmSetting::getValue('notify_law_violation', true),
            'notify_schedule_ready' => AlgorithmSetting::getValue('notify_schedule_ready', true),
        ];
    }

    private function logGeneration(
        Carbon $startDate,
        Carbon $endDate,
        int $totalAssignments,
        string $season,
        array $settings
    ): void {
        $this->info("\n📊 Статистика генерации:");
        $this->info("   • Период: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}");
        $this->info("   • Сезон: " . ($season === 'season' ? 'СЕЗОН' : 'МЕЖСЕЗОНЬЕ'));
        $this->info("   • Всего назначений: {$totalAssignments}");
        $this->info("   • Проверка ТК РФ: " . ($settings['enforce_labor_law'] ? 'ВКЛ' : 'ВЫКЛ'));
        $this->info("   • Вынужденные назначения: " . ($settings['allow_forced_assignment'] ? 'РАЗРЕШЕНЫ' : 'ЗАПРЕЩЕНЫ'));
        $this->info("   • Совмещение смен: " . ($settings['enable_shift_overlap'] ? 'ВКЛ' : 'ВЫКЛ'));
        $this->info("   • Кросс-тренинг: " . (($settings['cross_training'] ?? false) ? 'ВКЛ' : 'ВЫКЛ'));
    }

    private function getPrioritizedPreferences(Collection $employees, Carbon $date): array
    {
        $preferences = ShiftPreference::whereIn('employee_id', $employees->pluck('id'))
            ->where('date', $date->toDateString())
            ->orderBy('submitted_at', 'asc')
            ->get()
            ->keyBy('employee_id')
            ->toArray();

        return $preferences;
    }

    private function getShiftHours(string $shiftType, string $role): int
    {
        $customHours = AlgorithmSetting::getValue("shift_hours_{$shiftType}");

        if ($customHours !== null && is_numeric($customHours)) {
            return (int) $customHours;
        }

        return match ($shiftType) {
            'morning' => 6,
            'day' => 8,
            'night' => 7,
            default => 8,
        };
    }

    private function sortShiftsByTime(array $shiftTypes): array
    {
        $shiftOrder = ['morning' => 1, 'day' => 2, 'night' => 3];

        usort($shiftTypes, function ($a, $b) use ($shiftOrder) {
            return ($shiftOrder[$a] ?? 99) <=> ($shiftOrder[$b] ?? 99);
        });

        return $shiftTypes;
    }

    private function getShiftTypesForRole(string $role): array
    {
        if (in_array($role, ['waiter', 'hostess'])) {
            return ['morning', 'day', 'night'];
        }
        return ['day', 'night'];
    }

    private function getDayType(Carbon $date): string
    {
        $dayOfWeek = $date->dayOfWeek;

        if ($dayOfWeek === 5) {
            return 'holiday';
        } elseif (in_array($dayOfWeek, [0, 6])) {
            return 'weekend';
        } else {
            return 'weekday';
        }
    }

    private function saveShortagesToSession(Carbon $startDate): void
    {
        $shortageInfo = [
            'start_date' => $startDate->format('Y-m-d'),
            'shortages' => $this->shortages,
            'timestamp' => now()->toDateTimeString(),
            'shortage_count' => count($this->shortages),
            'warning_message' => $this->generateShortageMessage()
        ];

        $filePath = storage_path('logs/schedule_shortages.json');
        file_put_contents($filePath, json_encode($shortageInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function generateShortageMessage(): string
    {
        if (empty($this->shortages)) {
            return '';
        }

        $totalShortages = count($this->shortages);

        $dates = [];
        $roles = [];

        foreach ($this->shortages as $shortage) {
            $date = Carbon::parse($shortage['date'])->format('d.m');
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }

            $role = match ($shortage['role']) {
                'cook' => 'поваров',
                'waiter' => 'официантов',
                'bartender' => 'барменов',
                'hostess' => 'хостес',
                default => $shortage['role'],
            };

            if (!in_array($role, $roles)) {
                $roles[] = $role;
            }
        }

        $datesStr = implode(', ', array_slice($dates, 0, 3));
        if (count($dates) > 3) {
            $datesStr .= ' и ещё ' . (count($dates) - 3) . ' дней';
        }

        $roleStr = implode(', ', $roles);

        return "⚠️ Обнаружены неукомплектованные смены: {$totalShortages} смен за {$datesStr} ({$roleStr})";
    }

    private function getRoleName(string $role): string
    {
        return match ($role) {
            'cook' => 'Повара',
            'waiter' => 'Официанты',
            'hostess' => 'Хостес',
            'bartender' => 'Бармены',
            'admin' => 'Администраторы',
            default => $role,
        };
    }

    private function getShiftName(string $shiftType): string
    {
        return match ($shiftType) {
            'morning' => 'утреннюю смену',
            'day' => 'дневную смену',
            'night' => 'ночную смену',
            default => $shiftType,
        };
    }
}