<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shift_preferences')->truncate();

        // Получаем всех сотрудников
        $employees = DB::table('employees')->select('id', 'role')->get();
        
        if ($employees->isEmpty()) {
            $this->command->warn('⚠️  Нет сотрудников для создания предпочтений. Сначала запустите EmployeeSeeder.');
            return;
        }

        $preferences = [];
        $now = now();
        
        // Создаем предпочтения на ближайшие 2 недели
        for ($dayOffset = 1; $dayOffset <= 14; $dayOffset++) {
            $date = $now->copy()->addDays($dayOffset);
            $dayOfWeek = $date->dayOfWeek; // 0-воскресенье, 1-понедельник...
            $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
            $dayType = $isWeekend ? 'weekend' : 'weekday';

            foreach ($employees as $employee) {
                // Случайным образом создаем предпочтения (примерно 70% сотрудников в день)
                if (rand(1, 100) <= 70) {
                    $preferenceType = $this->generatePreferenceType($employee->role, $dayType, $dayOffset);
                    
                    // Разное время подачи (чем раньше дата, тем раньше подача)
                    $submittedAt = $now->copy()->subDays(rand(0, $dayOffset))->subHours(rand(1, 12));
                    
                    $preferences[] = [
                        'employee_id' => $employee->id,
                        'date' => $date->format('Y-m-d'),
                        'type' => $preferenceType,
                        'submitted_at' => $submittedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    // Ограничиваем количество предпочтений (максимум 20 записей за раз)
                    if (count($preferences) >= 500) {
                        DB::table('shift_preferences')->insert($preferences);
                        $preferences = [];
                    }
                }
            }
        }

        // Вставляем оставшиеся предпочтения
        if (!empty($preferences)) {
            DB::table('shift_preferences')->insert($preferences);
        }

        $totalPreferences = DB::table('shift_preferences')->count();
        
        $this->command->info("✅ Предпочтения по сменам созданы!");
        $this->command->info("   - Период: ближайшие 14 дней");
        $this->command->info("   - Всего предпочтений: $totalPreferences");
        $this->command->info("   - Система приоритетов: чем раньше submitted_at, тем выше приоритет");
    }

    private function generatePreferenceType(string $role, string $dayType, int $dayOffset): string
    {
        // Распределение типов предпочтений
        $preferenceTypes = [
            'day_off' => 30,          // 30% - выходной
            'prefer_morning' => 15,   // 15% - предпочитаю утро
            'prefer_day' => 15,       // 15% - предпочитаю день
            'prefer_night' => 10,     // 10% - предпочитаю ночь
            'avoid_morning' => 10,    // 10% - избегаю утро
            'avoid_day' => 10,        // 10% - избегаю день
            'avoid_night' => 10,      // 10% - избегаю ночь
        ];

        // Корректируем в зависимости от роли
        if ($role === 'cook') {
            // Повара чаще работают днем и ночью
            $preferenceTypes['prefer_day'] += 10;
            $preferenceTypes['prefer_night'] += 5;
            $preferenceTypes['day_off'] -= 15;
        } elseif ($role === 'admin') {
            // Администраторы чаще работают днем
            $preferenceTypes['prefer_day'] += 20;
            $preferenceTypes['avoid_night'] += 10;
            $preferenceTypes['day_off'] -= 10;
        }

        // На выходных чаще хотят выходной
        if ($dayType === 'weekend') {
            $preferenceTypes['day_off'] += 20;
            $preferenceTypes['prefer_morning'] -= 5;
            $preferenceTypes['prefer_day'] -= 5;
            $preferenceTypes['prefer_night'] -= 5;
        }

        // Выбираем случайный тип на основе весов
        $totalWeight = array_sum($preferenceTypes);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($preferenceTypes as $type => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $type;
            }
        }

        return 'day_off'; // fallback
    }
}