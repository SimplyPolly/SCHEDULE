<?php

namespace Database\Seeders;

use App\Models\AlgorithmSetting;
use App\Models\ShiftRequirement;
use Illuminate\Database\Seeder;

class AlgorithmSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Инициализируем настройки алгоритма
        AlgorithmSetting::initializeDefaults();

        // 2. Инициализируем требования для сезона
        $this->seedSeasonRequirements('season');

        // 3. Инициализируем требования для межсезонья
        $this->seedSeasonRequirements('offseason');

        $this->command->info('Настройки алгоритма и требования к штату инициализированы');
    }

    private function seedSeasonRequirements(string $season): void
    {
        $requirements = [
            // Повара (сезон)
            ['role' => 'cook', 'shift_type' => 'day', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 4 : 3],
            ['role' => 'cook', 'shift_type' => 'day', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 4 : 3],
            ['role' => 'cook', 'shift_type' => 'day', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 5 : 4],
            ['role' => 'cook', 'shift_type' => 'night', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'cook', 'shift_type' => 'night', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 4 : 3],
            ['role' => 'cook', 'shift_type' => 'night', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 4 : 3],

            // Официанты
            ['role' => 'waiter', 'shift_type' => 'morning', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 2 : 2],
            ['role' => 'waiter', 'shift_type' => 'morning', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'waiter', 'shift_type' => 'morning', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'waiter', 'shift_type' => 'day', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'waiter', 'shift_type' => 'day', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 4 : 3],
            ['role' => 'waiter', 'shift_type' => 'day', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 5 : 3],
            ['role' => 'waiter', 'shift_type' => 'night', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 2 : 2],
            ['role' => 'waiter', 'shift_type' => 'night', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'waiter', 'shift_type' => 'night', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 4 : 3],

            // Хостес
            ['role' => 'hostess', 'shift_type' => 'morning', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'hostess', 'shift_type' => 'morning', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 2 : 1],
            ['role' => 'hostess', 'shift_type' => 'morning', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 2 : 1],
            ['role' => 'hostess', 'shift_type' => 'day', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 2 : 2],
            ['role' => 'hostess', 'shift_type' => 'day', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'hostess', 'shift_type' => 'day', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'hostess', 'shift_type' => 'night', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'hostess', 'shift_type' => 'night', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 2 : 1],
            ['role' => 'hostess', 'shift_type' => 'night', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 2 : 1],

            // Бармены
            ['role' => 'bartender', 'shift_type' => 'day', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 2 : 2],
            ['role' => 'bartender', 'shift_type' => 'day', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'bartender', 'shift_type' => 'day', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'bartender', 'shift_type' => 'night', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 2 : 1],
            ['role' => 'bartender', 'shift_type' => 'night', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 3 : 2],
            ['role' => 'bartender', 'shift_type' => 'night', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 4 : 2],

            // Администраторы
            ['role' => 'admin', 'shift_type' => 'day', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'admin', 'shift_type' => 'day', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'admin', 'shift_type' => 'day', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'admin', 'shift_type' => 'night', 'day_type' => 'weekday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'admin', 'shift_type' => 'night', 'day_type' => 'holiday', 'min_staff' => $season === 'season' ? 1 : 1],
            ['role' => 'admin', 'shift_type' => 'night', 'day_type' => 'weekend', 'min_staff' => $season === 'season' ? 1 : 1],
        ];

        foreach ($requirements as $requirement) {
            ShiftRequirement::updateOrCreate(
                [
                    'season' => $season,
                    'role' => $requirement['role'],
                    'shift_type' => $requirement['shift_type'],
                    'day_type' => $requirement['day_type']
                ],
                [
                    'min_staff' => $requirement['min_staff']
                ]
            );
        }
    }
}
