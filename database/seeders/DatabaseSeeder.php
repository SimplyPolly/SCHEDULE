<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmployeeSeeder::class,
            ShiftRequirementSeeder::class,
            AlgorithmSettingsSeeder::class,
            ShiftPreferenceSeeder::class,
        ]);

        $this->command->info('✅ Все сидеры выполнены успешно!');
        $this->command->info('📊 Статистика:');
        $this->command->info('   - Сотрудники: 19 человек (2 админа, 6 поваров, 4 официанта, 4 хостес, 3 бармена)');
        $this->command->info('   - Требования: 48 записей (2 сезона × 24 комбинации)');
        $this->command->info('   - Настройки алгоритма: 8 параметров');
        $this->command->info('   - Предпочтения: ~70% сотрудников на ближайшие 14 дней');
        $this->command->info('👤 Тестовые логины (пароль: "password"):');
        $this->command->info('   - Администратор: admin1@restaurant.com');
        $this->command->info('   - Повар: cook1@restaurant.com');
        $this->command->info('   - Официант: waiter1@restaurant.com');
        $this->command->info('   - Хостес: hostess1@restaurant.com');
        $this->command->info('   - Бармен: bartender1@restaurant.com');
    }
}