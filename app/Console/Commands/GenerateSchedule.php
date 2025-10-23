<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use App\Models\ShiftRequirement;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateSchedule extends Command
{
    protected $signature = 'schedule:generate {start_date?}';
    protected $description = 'Генерирует двухнедельный график смен для всех ролей';

    public function handle()
    {
        // ШАГ 1: Определяем период генерации (2 недели)
        $startDate = $this->argument('start_date')
            ? Carbon::parse($this->argument('start_date'))
            : now()->startOfDay();

        $startDate = $startDate->startOfWeek(); // Начинаем с понедельника
        $endDate = $startDate->copy()->addDays(13); // 14 дней = 2 недели

        $this->info("🎯 Генерация графика с {$startDate->format('d.m.Y')} по {$endDate->format('d.m.Y')}");
        $this->info("Учет приоритета: РАНЬШЕ подал заявку → ВЫШЕ приоритет");
        $this->info("Проверка совместимости смен: утро+день или день+ночь");

        // Очищаем старые назначения на этот период (чтобы перегенерировать заново)
        ShiftAssignment::whereBetween('date', [$startDate, $endDate])->delete();

        // ШАГ 2: Обрабатываем каждую роль отдельно
        $roles = ['cook', 'waiter', 'hostess', 'bartender', 'admin'];

        $totalAssignments = 0;
        foreach ($roles as $role) {
            $this->info("\n--- Обработка роли: {$role} ---");
            // ШАГ 3: Генерируем график для конкретной роли
            $assignmentsCount = $this->generateForRole($role, $startDate, $endDate);
            $totalAssignments += $assignmentsCount;
            $this->info("Назначено смен: {$assignmentsCount}");
        }

        $this->info("\n✅ Генерация завершена! Всего назначений: {$totalAssignments}");
    }

    /**
     * ОСНОВНОЙ АЛГОРИТМ: Генерация графика для одной роли
     */
    private function generateForRole(string $role, Carbon $startDate, Carbon $endDate): int
    {
        // Получаем всех активных сотрудников этой роли
        $employees = Employee::where('role', $role)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            $this->warn("⚠️ Нет активных сотрудников для роли: {$role}");
            return 0;
        }

        $assignmentsCount = 0;
        // Счетчик смен для балансировки нагрузки
        $shiftCounts = array_fill_keys($employees->pluck('id')->toArray(), 0);

        // ШАГ 4: Перебираем КАЖДЫЙ ДЕНЬ в периоде
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dayType = $this->getDayType($current);

            // Определяем какие типы смен доступны для этой роли
            $shiftTypes = $this->getShiftTypesForRole($role);

            // Сортируем смены по времени: утро → день → ночь
            $shiftTypes = $this->sortShiftsByTime($shiftTypes);

            // ШАГ 5: Получаем ВСЕ пожелания на этот день заранее
            $preferences = $this->getPrioritizedPreferences($employees, $current);

            // ШАГ 6: Перебираем КАЖДУЮ СМЕНУ в этом дне
            foreach ($shiftTypes as $shiftType) {
                // Получаем минимальное количество сотрудников для этой смены
                $minStaff = ShiftRequirement::getMinStaff($dayType, $shiftType, $role);

                if ($minStaff <= 0) {
                    continue;
                }

                $this->info("  📅 {$current->format('d.m.Y')} {$shiftType}: требуется {$minStaff} чел.");

                // ШАГ 7: Фильтруем кандидатов - кто МОЖЕТ работать в эту смену
                $candidates = $this->getCandidatesForShift($employees, $preferences, $shiftType, $current);

                if ($candidates->isEmpty()) {
                    $this->warn("    ❌ Нет доступных кандидатов");
                    continue;
                }

                // ШАГ 8: СОРТИРУЕМ кандидатов по приоритету
                $sortedCandidates = $this->sortCandidatesByPriority(
                    $candidates,
                    $preferences,
                    $shiftType,
                    $shiftCounts
                );

                // ШАГ 9: Назначаем сотрудников на смену
                $assignedCount = 0;
                foreach ($sortedCandidates as $employee) {
                    if ($assignedCount >= $minStaff) {
                        break;
                    }

                    // Проверяем можно ли назначить эту смену
                    if (!$this->canAssignShift($employee, $current, $shiftType)) {
                        continue;
                    }

                    try {
                        // СОЗДАЕМ назначение смены
                        ShiftAssignment::create([
                            'employee_id' => $employee->id,
                            'date' => $current->toDateString(),
                            'shift_type' => $shiftType,
                            'is_approved' => false,
                        ]);

                        // Обновляем счетчики
                        $shiftCounts[$employee->id]++;
                        $assignedCount++;
                        $assignmentsCount++;

                        // Логируем результат с информацией о пожелании
                        $preference = $preferences[$employee->id] ?? null;
                        $preferenceType = $preference ? $preference->type : 'нет пожелания';
                        $this->info("    ✅ {$employee->name} [{$preferenceType}]");
                    } catch (\Exception $e) {
                        $this->error("    ❌ Ошибка назначения {$employee->name}: {$e->getMessage()}");
                    }
                }

                // Отчет по укомплектованности смены
                if ($assignedCount < $minStaff) {
                    $this->warn("    ⚠️ Недостаточно: нужно {$minStaff}, назначено {$assignedCount}");
                } else {
                    $this->info("    👍 Укомплектовано: {$assignedCount}/{$minStaff}");
                }
            }

            $current->addDay();
        }

        return $assignmentsCount;
    }

    /**
     * ПРОВЕРКА СОВМЕСТИМОСТИ: Можно ли назначить сотруднику эту смену?
     */
    private function canAssignShift(Employee $employee, Carbon $date, string $newShiftType): bool
    {
        // Получаем уже назначенные смены на эту дату
        $existingAssignments = $employee->getAssignmentsForDate($date->toDateString());

        if ($existingAssignments->isEmpty()) {
            return true;
        }

        // Для ролей с 3 сменами проверяем совместимость
        if (!$employee->canWorkDoubleShift()) {
            // Особое правило: ночную смену можно назначить после дневной
            if ($newShiftType === 'night') {
                $existingShift = $existingAssignments->first()->shift_type;
                return $existingShift === 'day';
            }

            // Для других смен проверяем стандартную совместимость
            $existingShift = $existingAssignments->first()->shift_type;
            return Employee::canCombineShifts($existingShift, $newShiftType);
        }

        // Для ролей с 2 сменами - можно работать обе смены
        return $existingAssignments->count() < 2;
    }

    /**
     * СОРТИРОВКА СМЕН ПО ВРЕМЕНИ: утро → день → ночь
     */
    private function sortShiftsByTime(array $shiftTypes): array
    {
        $shiftOrder = ['morning' => 1, 'day' => 2, 'night' => 3];

        usort($shiftTypes, function ($a, $b) use ($shiftOrder) {
            return ($shiftOrder[$a] ?? 99) <=> ($shiftOrder[$b] ?? 99);
        });

        return $shiftTypes;
    }

    /**
     * ПОЛУЧЕНИЕ ПОЖЕЛАНИЙ С ПРИОРИТЕТОМ: сортируем по времени подачи
     */
    private function getPrioritizedPreferences(Collection $employees, Carbon $date): array
    {
        $preferences = ShiftPreference::with('employee')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('date', $date->toDateString())
            ->orderBy('submitted_at', 'asc')
            ->get()
            ->keyBy('employee_id') // Используем keyBy вместо groupBy для упрощения
            ->toArray();

        return $preferences;
    }

    /**
     * ФИЛЬТРАЦИЯ КАНДИДАТОВ: кто МОЖЕТ работать в эту смену
     */
    private function getCandidatesForShift(Collection $employees, array $preferences, string $shiftType, Carbon $date): Collection
    {
        return $employees->filter(function ($employee) use ($preferences, $shiftType, $date) {
            $preference = $preferences[$employee->id] ?? null;

            // ПРОВЕРКА 1: Учитываем пожелания сотрудника
            if ($preference) {
                $prefType = $preference['type'];
                
                // Если сотрудник запросил выходной - не доступен
                if ($prefType === 'day_off') {
                    return false;
                }
                
                // Если сотрудник хочет избегать эту смену - не доступен
                if ($prefType === "avoid_{$shiftType}") {
                    return false;
                }
                
                // ВАЖНОЕ ИСПРАВЛЕНИЕ: Если сотрудник хочет конкретную смену - доступен ТОЛЬКО для нее
                if (str_starts_with($prefType, 'prefer_')) {
                    $desiredShift = str_replace('prefer_', '', $prefType);
                    if ($desiredShift !== $shiftType) {
                        return false; // Не доступен для других смен
                    }
                }
            }

            // ПРОВЕРКА 2: Смотрим текущие назначения на этот день
            $existingAssignments = $employee->getAssignmentsForDate($date->toDateString());

            if ($existingAssignments->isEmpty()) {
                return true;
            }

            // ПРОВЕРКА 3: Для ролей с 3 сменами проверяем совместимость
            if (!$employee->canWorkDoubleShift()) {
                $existingShift = $existingAssignments->first()->shift_type;
                return Employee::canCombineShifts($existingShift, $shiftType);
            }

            // ПРОВЕРКА 4: Для ролей с 2 сменами - можно работать обе
            return $existingAssignments->count() < 2;
        });
    }

    /**
     * СОРТИРОВКА КАНДИДАТОВ ПО ПРИОРИТЕТУ - УПРОЩЕННАЯ ВЕРСИЯ
     */
    private function sortCandidatesByPriority(Collection $candidates, array $preferences, string $shiftType, array $shiftCounts): Collection
    {
        return $candidates->sortBy(function ($employee) use ($preferences, $shiftType, $shiftCounts) {
            $preference = $preferences[$employee->id] ?? null;
            
            // ПРИОРИТЕТ 1: Сотрудники с пожеланием на ЭТУ смену (самый высокий)
            if ($preference) {
                $prefType = $preference['type'];
                if (str_starts_with($prefType, 'prefer_')) {
                    $desiredShift = str_replace('prefer_', '', $prefType);
                    if ($desiredShift === $shiftType) {
                        // Раньше подал заявку = выше приоритет (меньшее число)
                        return Carbon::parse($preference['submitted_at'])->timestamp;
                    }
                }
            }
            
            // ПРИОРИТЕТ 2: Сотрудники без пожеланий (средний)
            if (!$preference) {
                return 2000000000 + $shiftCounts[$employee->id] * 10000 + mt_rand(0, 999);
            }
            
            // ПРИОРИТЕТ 3: Сотрудники с другими пожеланиями (низкий)
            return 3000000000 + $shiftCounts[$employee->id] * 10000;
        });
    }

    /**
     * ОПРЕДЕЛЕНИЕ ТИПОВ СМЕН ДЛЯ РОЛИ
     */
    private function getShiftTypesForRole(string $role): array
    {
        if (in_array($role, ['cook', 'bartender', 'admin'])) {
            return ['day', 'night'];
        }
        return ['morning', 'day', 'night'];
    }

    /**
     * ОПРЕДЕЛЕНИЕ ТИПА ДНЯ: будний или выходной
     */
    private function getDayType(Carbon $date): string
    {
        return in_array($date->dayOfWeek, [0, 6]) ? 'weekend' : 'weekday';
    }
}