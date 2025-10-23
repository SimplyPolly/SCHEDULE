<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftPreference;
use App\Models\ShiftRequirement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        // Удаляем старые назначения за 2 недели
        ShiftAssignment::whereBetween('date', [now(), now()->addDays(13)])->delete();

        $startDate = now()->startOfDay();
        $endDate = $startDate->copy()->addDays(13);

        $roles = ['cook', 'waiter', 'hostess', 'bartender', 'admin'];

        foreach ($roles as $role) {
            $this->generateForRole($role, $startDate, $endDate);
        }
    }

    private function generateForRole(string $role, Carbon $startDate, Carbon $endDate)
    {
        // Получаем всех активных сотрудников этой роли
        $employees = Employee::where('role', $role)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            Log::warning("Нет активных сотрудников для роли {$role}");
            return;
        }

        // Счётчик смен для балансировки
        $shiftCounts = array_fill_keys($employees->pluck('id')->toArray(), 0);

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dayType = $this->getDayType($current);
            $requiredShifts = ['morning', 'day', 'night'];

            foreach ($requiredShifts as $shiftType) {
                $minStaff = ShiftRequirement::where('day_type', $dayType)
                    ->where('shift_type', $shiftType)
                    ->where('role', $role)
                    ->value('min_staff') ?? 0;

                if ($minStaff <= 0) continue;

                // Получить пожелания на этот день
                $preferences = ShiftPreference::with('employee')
                    ->whereIn('employee_id', $employees->pluck('id'))
                    ->where('date', $current->toDateString())
                    ->orderBy('submitted_at')
                    ->get();

                // Разделим пожелания по типу
                $dayOffRequests = $preferences->where('type', 'day_off')->pluck('employee_id')->toArray();
                $preferShift = [];
                $avoidShift = [];

                foreach ($preferences as $pref) {
                    if ($pref->type === "prefer_{$shiftType}") {
                        $preferShift[] = $pref->employee_id;
                    } elseif ($pref->type === "avoid_{$shiftType}") {
                        $avoidShift[] = $pref->employee_id;
                    }
                }

                // Фильтруем кандидатов
                $candidates = $employees->filter(function ($emp) use ($dayOffRequests, $avoidShift) {
                    return !in_array($emp->id, $dayOffRequests) && !in_array($emp->id, $avoidShift);
                })->values();

                // Если недостаточно — берём всех (даже с avoid, но не day_off)
                if ($candidates->count() < $minStaff) {
                    $candidates = $employees->filter(function ($emp) use ($dayOffRequests) {
                        return !in_array($emp->id, $dayOffRequests);
                    })->values();
                }

                // Сортируем кандидатов:
                // 1. Кто хочет эту смену → выше приоритет
                // 2. Кто меньше всего работает → ниже значение shiftCounts
                $candidates = $candidates->sortByDesc(function ($emp) use ($preferShift) {
                    return in_array($emp->id, $preferShift) ? 1 : 0;
                })->sortBy(function ($emp) use ($shiftCounts) {
                    return $shiftCounts[$emp->id] ?? 0;
                });

                // Назначаем первых minStaff сотрудников
                $assigned = 0;
                foreach ($candidates as $emp) {
                    if ($assigned >= $minStaff) break;

                    ShiftAssignment::create([
                        'employee_id' => $emp->id,
                        'date' => $current->toDateString(),
                        'shift_type' => $shiftType,
                        'is_approved' => false,
                    ]);

                    $shiftCounts[$emp->id] = ($shiftCounts[$emp->id] ?? 0) + 1;
                    $assigned++;
                }

                // Логируем недостаток
                if ($assigned < $minStaff) {
                    Log::warning("⚠️ Недостаток персонала для {$role} в {$shiftType} {$current->toDateString()}: нужно {$minStaff}, назначено {$assigned}");
                }
            }

            $current->addDay();
        }
    }

    private function getDayType(Carbon $date): string
    {
        if (in_array($date->dayOfWeek, [0, 6])) { // Воскресенье = 0, Суббота = 6
            return 'weekend';
        }
        return 'weekday';
    }
}
