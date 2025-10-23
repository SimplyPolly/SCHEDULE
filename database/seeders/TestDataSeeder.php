<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\ShiftPreference;
use App\Models\ShiftRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🎯 Создание тестовых данных...');

        // Очищаем данные
        ShiftPreference::truncate();
        Employee::truncate();
        ShiftRequirement::truncate();

        // Создаем тестовых сотрудников
        $employees = [
            // Администраторы
            ['name' => 'Админ Тестовый', 'email' => 'admin@test.com', 'role' => 'admin', 'password' => 'password'],
            
            // Повара
            ['name' => 'Повар Иван', 'email' => 'cook1@test.com', 'role' => 'cook', 'password' => 'password'],
            ['name' => 'Повар Петр', 'email' => 'cook2@test.com', 'role' => 'cook', 'password' => 'password'],
            ['name' => 'Повар Мария', 'email' => 'cook3@test.com', 'role' => 'cook', 'password' => 'password'],
            
            // Официанты
            ['name' => 'Официант Анна', 'email' => 'waiter1@test.com', 'role' => 'waiter', 'password' => 'password'],
            ['name' => 'Официант Дмитрий', 'email' => 'waiter2@test.com', 'role' => 'waiter', 'password' => 'password'],
            
            // Хостес
            ['name' => 'Хостес Елена', 'email' => 'hostess1@test.com', 'role' => 'hostess', 'password' => 'password'],
            
            // Бармены
            ['name' => 'Бармен Алексей', 'email' => 'bartender1@test.com', 'role' => 'bartender', 'password' => 'password'],
        ];

        foreach ($employees as $employee) {
            Employee::create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'password' => Hash::make($employee['password']),
                'role' => $employee['role'],
                'is_active' => true,
            ]);
        }

        // Создаем тестовые требования
        $this->createTestRequirements();

        // Создаем тестовые пожелания
        $this->createTestPreferences();

        $this->command->info('✅ Тестовые данные созданы!');
        $this->command->info('👤 Логины для тестирования:');
        $this->command->info('   - Админ: admin@test.com / password');
        $this->command->info('   - Повар: cook1@test.com / password');
        $this->command->info('   - Официант: waiter1@test.com / password');
    }

    private function createTestRequirements()
    {
        $requirements = [
            // Будни
            ['weekday', 'day', 'cook', 2],
            ['weekday', 'night', 'cook', 1],
            ['weekday', 'day', 'bartender', 1],
            ['weekday', 'night', 'bartender', 1],
            ['weekday', 'day', 'admin', 1],
            ['weekday', 'night', 'admin', 1],
            ['weekday', 'morning', 'waiter', 1],
            ['weekday', 'day', 'waiter', 2],
            ['weekday', 'night', 'waiter', 1],
            ['weekday', 'morning', 'hostess', 1],
            ['weekday', 'day', 'hostess', 1],
            ['weekday', 'night', 'hostess', 1],

            // Выходные
            ['weekend', 'day', 'cook', 2],
            ['weekend', 'night', 'cook', 2],
            ['weekend', 'day', 'bartender', 1],
            ['weekend', 'night', 'bartender', 1],
            ['weekend', 'day', 'admin', 1],
            ['weekend', 'night', 'admin', 1],
            ['weekend', 'morning', 'waiter', 2],
            ['weekend', 'day', 'waiter', 2],
            ['weekend', 'night', 'waiter', 2],
            ['weekend', 'morning', 'hostess', 1],
            ['weekend', 'day', 'hostess', 1],
            ['weekend', 'night', 'hostess', 1],
        ];

        foreach ($requirements as $req) {
            ShiftRequirement::create([
                'day_type' => $req[0],
                'shift_type' => $req[1],
                'role' => $req[2],
                'min_staff' => $req[3],
            ]);
        }
    }

    private function createTestPreferences()
    {
        $startDate = now()->startOfWeek();
        
        // Повар Иван хочет выходные
        $this->createPreference('cook1@test.com', $startDate->copy()->addDays(5), 'day_off'); // Суббота
        $this->createPreference('cook1@test.com', $startDate->copy()->addDays(6), 'day_off'); // Воскресенье

        // Повар Петр хочет ночные смены
        $this->createPreference('cook2@test.com', $startDate->copy()->addDays(1), 'prefer_night'); // Вторник
        $this->createPreference('cook2@test.com', $startDate->copy()->addDays(2), 'prefer_night'); // Среда

        // Официант Анна избегает утренних смен
        $this->createPreference('waiter1@test.com', $startDate->copy()->addDays(0), 'avoid_morning'); // Понедельник
        $this->createPreference('waiter1@test.com', $startDate->copy()->addDays(1), 'avoid_morning'); // Вторник

        // Официант Дмитрий хочет дневные смены
        $this->createPreference('waiter2@test.com', $startDate->copy()->addDays(3), 'prefer_day'); // Четверг
        $this->createPreference('waiter2@test.com', $startDate->copy()->addDays(4), 'prefer_day'); // Пятница

        // Фиксируем пожелания для некоторых сотрудников
        Employee::whereIn('email', ['cook1@test.com', 'cook2@test.com'])->update([
            'preferences_submitted_at' => now()
        ]);
    }

    private function createPreference(string $email, Carbon $date, string $type)
    {
        $employee = Employee::where('email', $email)->first();
        
        if ($employee) {
            ShiftPreference::create([
                'employee_id' => $employee->id,
                'date' => $date->format('Y-m-d'),
                'type' => $type,
                'submitted_at' => now()->subMinutes(rand(1, 60)), // Разное время подачи
            ]);
        }
    }
}