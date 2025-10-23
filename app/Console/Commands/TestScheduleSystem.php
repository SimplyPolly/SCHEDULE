<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use App\Models\ShiftRequirement;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestScheduleSystem extends Command
{
    protected $signature = 'schedule:test';
    protected $description = 'Тестирование системы генерации графиков';

    public function handle()
    {
        $this->info('🧪 ТЕСТИРОВАНИЕ СИСТЕМЫ ГЕНЕРАЦИИ ГРАФИКОВ');
        $this->info('=========================================');

        // Тест 1: Проверка данных
        $this->testDataValidation();

        // Тест 2: Генерация графика
        $this->testScheduleGeneration();

        // Тест 3: Проверка правил
        $this->testBusinessRules();

        // Тест 4: Проверка приоритетов
        $this->testPrioritySystem();

        $this->info('=========================================');
        $this->info('✅ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО!');
    }

    private function testDataValidation()
    {
        $this->info("\n📊 ТЕСТ 1: ПРОВЕРКА ДАННЫХ");
        
        $employees = Employee::count();
        $requirements = ShiftRequirement::count();
        $preferences = ShiftPreference::count();

        $this->info("   Сотрудники: {$employees}");
        $this->info("   Требования к сменам: {$requirements}");
        $this->info("   Пожелания: {$preferences}");

        if ($employees > 0 && $requirements > 0) {
            $this->info("   ✅ Данные загружены корректно");
        } else {
            $this->error("   ❌ Ошибка в данных");
        }
    }

    private function testScheduleGeneration()
    {
        $this->info("\n🎯 ТЕСТ 2: ГЕНЕРАЦИЯ ГРАФИКА");
        
        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->addDays(13);

        // Очищаем старые назначения
        ShiftAssignment::whereBetween('date', [$startDate, $endDate])->delete();

        // Запускаем генерацию
        $this->call('schedule:generate', ['start_date' => $startDate->format('Y-m-d')]);

        // Проверяем результат
        $assignments = ShiftAssignment::whereBetween('date', [$startDate, $endDate])->count();
        $this->info("   Назначено смен: {$assignments}");

        if ($assignments > 0) {
            $this->info("   ✅ График сгенерирован успешно");
        } else {
            $this->error("   ❌ Ошибка генерации графика");
        }

        // Проверяем распределение по ролям
        $this->info("\n   📈 РАСПРЕДЕЛЕНИЕ ПО РОЛЯМ:");
        $roles = ['cook', 'waiter', 'hostess', 'bartender', 'admin'];
        
        foreach ($roles as $role) {
            $count = ShiftAssignment::whereHas('employee', function($q) use ($role) {
                $q->where('role', $role);
            })->whereBetween('date', [$startDate, $endDate])->count();
            
            $this->info("      {$role}: {$count} смен");
        }
    }

    private function testBusinessRules()
    {
        $this->info("\n📋 ТЕСТ 3: ПРОВЕРКА БИЗНЕС-ПРАВИЛ");

        $startDate = now()->startOfWeek();
        $issues = [];

        // Проверка 1: Сотрудники не назначены на несовместимые смены
        $this->info("   🔍 Проверка совместимости смен...");
        
        $employees = Employee::with(['assignments' => function($q) use ($startDate) {
            $q->whereBetween('date', [$startDate, $startDate->copy()->addDays(6)]);
        }])->get();

        foreach ($employees as $employee) {
            $dailyAssignments = $employee->assignments->groupBy('date');
            
            foreach ($dailyAssignments as $date => $assignments) {
                if ($assignments->count() > 1) {
                    $shifts = $assignments->pluck('shift_type')->sort()->toArray();
                    
                    // Проверяем разрешенные комбинации
                    $allowedCombinations = [
                        ['morning', 'day'],
                        ['day', 'night']
                    ];

                    $isValid = false;
                    foreach ($allowedCombinations as $combination) {
                        if ($shifts == $combination) {
                            $isValid = true;
                            break;
                        }
                    }

                    if (!$isValid && $employee->hasTwoShifts()) {
                        // Для ролей с 2 сменами разрешены только день+ночь
                        if ($shifts == ['day', 'night']) {
                            $isValid = true;
                        }
                    }

                    if (!$isValid) {
                        $issues[] = "{$employee->name} ({$date}): несовместимые смены - " . implode(' + ', $shifts);
                    }
                }
            }
        }

        if (empty($issues)) {
            $this->info("   ✅ Все смены совместимы");
        } else {
            $this->error("   ❌ Найдены проблемы:");
            foreach ($issues as $issue) {
                $this->error("      - {$issue}");
            }
        }

        // Проверка 2: Учет пожеланий о выходных
        $this->info("   🔍 Проверка учета выходных...");
        $dayOffViolations = ShiftAssignment::whereHas('employee.preferences', function($q) {
            $q->where('type', 'day_off');
        })->count();

        if ($dayOffViolations == 0) {
            $this->info("   ✅ Выходные соблюдены");
        } else {
            $this->error("   ❌ Нарушения выходных: {$dayOffViolations}");
        }
    }

    private function testPrioritySystem()
    {
        $this->info("\n🎪 ТЕСТ 4: ПРОВЕРКА СИСТЕМЫ ПРИОРИТЕТОВ");

        // Проверяем конкретный случай с поварами
        $cook1 = Employee::where('email', 'cook1@test.com')->first();
        $cook2 = Employee::where('email', 'cook2@test.com')->first();

        if ($cook1 && $cook2) {
            $saturday = now()->startOfWeek()->addDays(5);
            
            $cook1Saturday = ShiftAssignment::where('employee_id', $cook1->id)
                ->where('date', $saturday->format('Y-m-d'))
                ->exists();

            $cook2Saturday = ShiftAssignment::where('employee_id', $cook2->id)
                ->where('date', $saturday->format('Y-m-d'))
                ->exists();

            $this->info("   Суббота ({$saturday->format('d.m')}):");
            $this->info("      Повар Иван (выходной): " . ($cook1Saturday ? '❌ НАЗНАЧЕН' : '✅ СВОБОДЕН'));
            $this->info("      Повар Петр (нет пожеланий): " . ($cook2Saturday ? '✅ НАЗНАЧЕН' : '❌ СВОБОДЕН'));

            if (!$cook1Saturday && $cook2Saturday) {
                $this->info("   ✅ Приоритет пожеланий работает корректно");
            } else {
                $this->error("   ❌ Проблема с приоритетом пожеланий");
            }
        }
    }
}