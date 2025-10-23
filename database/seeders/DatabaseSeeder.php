<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            EmployeeSeeder::class,
            ShiftRequirementSeeder::class,
        ]);

        $this->command->info('Все сиды выполнены успешно!');
        $this->command->info('Для входа используйте:');
        $this->command->info('   - Администратор: admin1@example.com / password');
        $this->command->info('   - Повар: cook1@example.com / password');
        $this->command->info('   - Официант: waiter1@example.com / password');
    }
}