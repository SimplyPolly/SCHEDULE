<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use Illuminate\Console\Command;

class CheckTestResult extends Command
{
    protected $signature = 'test:check-result';
    protected $description = 'Проверка результата теста';

    public function handle()
    {
        $employee = Employee::where('role', 'waiter')->first();
        $assignment = ShiftAssignment::with('employee')
            ->where('employee_id', $employee->id)
            ->where('date', '2024-10-27')
            ->first();

        if ($assignment && $assignment->shift_type === 'day') {
            $this->info("🎉 ТЕСТ ПРОЙДЕН УСПЕШНО!");
            $this->info("✅ ПОЖЕЛАНИЕ УЧТЕНО!");
            $this->info("👤 " . $assignment->employee->name);
            $this->info("📅 " . $assignment->date);
            $this->info("⏰ " . $assignment->shift_type);
            $this->info("📝 ВЫВОД: Система корректно учитывает пожелания сотрудников!");
        } else {
            $this->error("❌ ТЕСТ НЕ ПРОЙДЕН");
            if ($assignment) {
                $this->error("   Получил: " . $assignment->shift_type . " (хотел day)");
            } else {
                $this->error("   Назначение не найдено");
            }
            
            // Проверим кто получил дневные смены
            $this->info("🔍 Кто получил дневные смены:");
            $dayAssignments = ShiftAssignment::with('employee')
                ->where('date', '2024-10-27')
                ->where('shift_type', 'day')
                ->get();
            
            foreach ($dayAssignments as $assign) {
                $pref = ShiftPreference::where('employee_id', $assign->employee_id)
                    ->where('date', '2024-10-27')
                    ->first();
                $prefType = $pref ? $pref->type : 'нет пожелания';
                $this->info("   - " . $assign->employee->name . " (" . $assign->employee->role . ") [{$prefType}]");
            }
        }
        
        return Command::SUCCESS;
    }
}