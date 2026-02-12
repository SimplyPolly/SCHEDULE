<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftRequirementSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shift_requirements')->truncate();

        $requirements = [];
        
        // Для каждого сезона создаем требования
        $seasons = ['season', 'offseason'];
        
        foreach ($seasons as $season) {
            // Коэффициент для межсезонья (меньше персонала)
            $seasonMultiplier = ($season === 'offseason') ? 0.7 : 1.0;
            
            // БУДНИЕ ДНИ 
            $requirements = array_merge($requirements, [
                // ПОВАРЫ 6 сотрудников 
                $this->makeRule($season, 'weekday', 'day', 'cook', max(1, round(3 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'night', 'cook', max(1, round(2 * $seasonMultiplier))),
                
                // БАРМЕНЫ 3 сотрудника  
                $this->makeRule($season, 'weekday', 'day', 'bartender', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'night', 'bartender', max(1, round(1 * $seasonMultiplier))),
                
                // АДМИНИСТРАТОРЫ 2 сотрудника
                $this->makeRule($season, 'weekday', 'day', 'admin', max(1, round(1 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'night', 'admin', max(1, round(1 * $seasonMultiplier))),
                
                // ОФИЦИАНТЫ 4 сотрудника
                $this->makeRule($season, 'weekday', 'morning', 'waiter', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'day', 'waiter', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'night', 'waiter', max(1, round(1 * $seasonMultiplier))),
                
                // ХОСТЕС 4 сотрудника
                $this->makeRule($season, 'weekday', 'morning', 'hostess', max(1, round(1 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'day', 'hostess', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekday', 'night', 'hostess', max(1, round(1 * $seasonMultiplier))),
                
                // ВЫХОДНЫЕ ДНИ 
                // ПОВАРЫ
                $this->makeRule($season, 'weekend', 'day', 'cook', max(1, round(3 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'night', 'cook', max(1, round(2 * $seasonMultiplier))),
                
                // БАРМЕНЫ
                $this->makeRule($season, 'weekend', 'day', 'bartender', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'night', 'bartender', max(1, round(1 * $seasonMultiplier))),
                
                // АДМИНИСТРАТОРЫ
                $this->makeRule($season, 'weekend', 'day', 'admin', max(1, round(1 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'night', 'admin', max(1, round(1 * $seasonMultiplier))),
                
                // ОФИЦИАНТЫ 
                $this->makeRule($season, 'weekend', 'morning', 'waiter', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'day', 'waiter', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'night', 'waiter', max(1, round(1 * $seasonMultiplier))),
                
                // ХОСТЕС 
                $this->makeRule($season, 'weekend', 'morning', 'hostess', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'day', 'hostess', max(1, round(2 * $seasonMultiplier))),
                $this->makeRule($season, 'weekend', 'night', 'hostess', max(1, round(1 * $seasonMultiplier))),
            ]);
        }

        // Массовая вставка
        DB::table('shift_requirements')->insert($requirements);

        $this->command->info('✅ Требования к сменам созданы!');
        $this->command->info('   - Сезон (пик): нормальный штат');
        $this->command->info('   - Межсезонье: штат уменьшен на 30%');
        $this->command->info('   - Всего записей: ' . count($requirements));
    }

    private function makeRule(string $season, string $dayType, string $shiftType, string $role, int $minStaff): array
    {
        return [
            'season' => $season,
            'day_type' => $dayType,
            'shift_type' => $shiftType,
            'role' => $role,
            'min_staff' => $minStaff,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}