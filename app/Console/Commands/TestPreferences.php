<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use Illuminate\Console\Command;

class TestPreferences extends Command
{
    protected $signature = 'test:preferences';
    protected $description = 'Тестирование системы пожеланий';

    public function handle()
    {
        // 1. Очистим все назначения на тестовую дату
        ShiftAssignment::where('date', '2024-10-27')->delete();
        $this->info("✅ Очищены назначения на 2024-10-27");

        // 2. Возьмем официанта для теста
        $employee = Employee::where('role', 'waiter')->first();
        $this->info("✅ Выбран сотрудник: " . $employee->name);

        // 3. Создадим пожелание на дневную смену
        $preference = new ShiftPreference();
        $preference->employee_id = $employee->id;
        $preference->date = '2024-10-27';
        $preference->type = 'prefer_day';
        $preference->submitted_at = now();
        $preference->save();
        $this->info("✅ Создано пожелание: prefer_day на 2024-10-27");

        // 4. Проверим что пожелание создано
        $checkPref = ShiftPreference::where('employee_id', $employee->id)->where('date', '2024-10-27')->first();
        $this->info("✅ Проверка пожелания: " . $checkPref->type);

        $this->info("🚀 Теперь запустите: php artisan schedule:generate 2024-10-27");
        
        return Command::SUCCESS;
    }
}