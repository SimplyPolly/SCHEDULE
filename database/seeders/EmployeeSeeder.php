<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        Employee::truncate();

        $employees = [];

        // Администраторы — 2 (длинные смены)
        for ($i = 1; $i <= 2; $i++) {
            $employees[] = $this->makeEmployee('admin', "Администратор $i", $i);
        }

        // Повара — 6 (длинные смены)
        for ($i = 1; $i <= 6; $i++) {
            $employees[] = $this->makeEmployee('cook', "Повар $i", $i);
        }

        // Официанты — 4 (короткие смены)
        for ($i = 1; $i <= 4; $i++) {
            $employees[] = $this->makeEmployee('waiter', "Официант $i", $i);
        }

        // Хостес — 4 (короткие смены)
        for ($i = 1; $i <= 4; $i++) {
            $employees[] = $this->makeEmployee('hostess', "Хостес $i", $i);
        }

        // Бармены — 3 (короткие смены)
        for ($i = 1; $i <= 3; $i++) {
            $employees[] = $this->makeEmployee('bartender', "Бармен $i", $i);
        }

        Employee::insert($employees);

        $this->command->info('✅ Сотрудники созданы!');
        $this->command->info('   - Повары и администраторы: длинные смены (день 09:00-21:00, ночь 19:00-09:00)');
        $this->command->info('   - Официанты, хостес, бармены: короткие смены (утро 09:00-17:00, день 13:00-21:00, ночь 19:00-03:00)');
    }

    private function makeEmployee(string $role, string $name, int $index): array
    {
        $phone = '+7 (999) ' . str_pad(100 + $index, 6, '0', STR_PAD_LEFT);
        $telegram = '@' . strtolower($role) . $index;

        return [
            'name' => $name,
            'email' => strtolower($role) . $index . '@restaurant.com',
            'password' => Hash::make('password'),
            'phone' => $phone,
            'telegram' => $telegram,
            'role' => $role,
            'is_active' => true,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
