<?php

namespace App\Http\Controllers;

use App\Models\ShiftAssignment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends \Illuminate\Routing\Controller
{
    // Показать график
    public function index(Request $request)
    {
        $user = Auth::user();
        $periodStartDate = $request->has('period_start')
            ? Carbon::parse($request->period_start)->startOfWeek()
            : now()->startOfWeek();

        $periodEndDate = $periodStartDate->copy()->addDays(13);

        // Получаем назначения
        $assignments = ShiftAssignment::with('employee')
            ->whereBetween('date', [$periodStartDate, $periodEndDate])
            ->orderBy('date')
            ->orderBy('shift_type')
            ->get();

        // Формируем структуру для отображения
        $schedule = [];
        foreach ($assignments as $assignment) {
            $dateStr = $assignment->date instanceof \Carbon\Carbon
                ? $assignment->date->format('Y-m-d')
                : $assignment->date;

            $role = $assignment->employee->role;
            $shiftType = $assignment->shift_type;

            if (!isset($schedule[$dateStr][$role][$shiftType])) {
                $schedule[$dateStr][$role][$shiftType] = [];
            }

            $schedule[$dateStr][$role][$shiftType][] = [
                'name' => $assignment->employee->name,
                'employee_id' => $assignment->employee->id
            ];
        }

        // Для админа — общий график всех ролей
        if ($user->role === 'admin') {
            $title = 'Общий график';
        } else {
            // Для сотрудника — только его роль
            $roleNameGenitive = match ($user->role) {
                'cook' => 'поваров',
                'waiter' => 'официантов',
                'hostess' => 'хостес',
                'bartender' => 'барменов',
                'admin' => 'администраторов',
                default => $user->role,
            };
            $title = "График {$roleNameGenitive}";
        }

        // Подготовка данных для навигации по периодам
        $prevPeriodStart = $periodStartDate->copy()->subDays(14)->format('Y-m-d');
        $nextPeriodStart = $periodStartDate->copy()->addDays(14)->format('Y-m-d');
        $currentPeriodStart = now()->startOfWeek()->format('Y-m-d');

        // Формируем список дат для отображения в таблице 
        $dates = [];
        $currentDateIterator = $periodStartDate->copy();
        for ($i = 0; $i < 14; $i++) {
            $dates[] = $currentDateIterator->format('Y-m-d');
            $currentDateIterator->addDay();
        }

        return view('dashboard', compact(
            'schedule', 
            'assignments',
            'title',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'dates'
        ));
    }

    // Личный график сотрудника
    public function personal(Request $request)
    {
        $user = Auth::user();

        $periodStartDate = $request->has('period_start')
            ? Carbon::parse($request->period_start)->startOfWeek()
            : now()->startOfWeek();

        $periodEndDate = $periodStartDate->copy()->addDays(13);

        $assignments = ShiftAssignment::with('employee')
            ->whereBetween('date', [$periodStartDate, $periodEndDate])
            ->where('employee_id', $user->id)
            ->orderBy('date')
            ->orderBy('shift_type')
            ->get();

        // Формируем структуру для отображения
        $schedule = [];
        foreach ($assignments as $assignment) {
            $dateStr = $assignment->date instanceof Carbon
                ? $assignment->date->format('Y-m-d')
                : $assignment->date;

            $role = $assignment->employee->role;
            $shiftType = $assignment->shift_type;

            if (!isset($schedule[$dateStr][$role][$shiftType])) {
                $schedule[$dateStr][$role][$shiftType] = [];
            }

            $schedule[$dateStr][$role][$shiftType][] = [
                'name' => $assignment->employee->name,
                'employee_id' => $assignment->employee->id
            ];
        }

        $title = 'Мой график';

        // Навигация
        $prevPeriodStart = $periodStartDate->copy()->subDays(14)->format('Y-m-d');
        $nextPeriodStart = $periodStartDate->copy()->addDays(14)->format('Y-m-d');
        $currentPeriodStart = now()->startOfWeek()->format('Y-m-d');

        // Даты
        $dates = [];
        $currentDateIterator = $periodStartDate->copy();
        for ($i = 0; $i < 14; $i++) {
            $dates[] = $currentDateIterator->format('Y-m-d');
            $currentDateIterator->addDay();
        }

        return view('dashboard', compact(
            'schedule',
            'assignments',
            'title',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'dates'
        ));
    }

    // График по роли 
    public function byRole(Request $request)
    {
        $user = Auth::user();

        $periodStartDate = $request->has('period_start')
            ? Carbon::parse($request->period_start)->startOfWeek()
            : now()->startOfWeek();

        $periodEndDate = $periodStartDate->copy()->addDays(13);

        $assignments = ShiftAssignment::with('employee')
            ->whereBetween('date', [$periodStartDate, $periodEndDate])
            ->whereHas('employee', fn($q) => $q->where('role', $user->role))
            ->orderBy('date')
            ->orderBy('shift_type')
            ->get();

        // Формируем структуру для отображения
        $schedule = [];
        foreach ($assignments as $assignment) {
            $dateStr = $assignment->date instanceof Carbon
                ? $assignment->date->format('Y-m-d')
                : $assignment->date;

            $role = $assignment->employee->role;
            $shiftType = $assignment->shift_type;

            if (!isset($schedule[$dateStr][$role][$shiftType])) {
                $schedule[$dateStr][$role][$shiftType] = [];
            }

            $schedule[$dateStr][$role][$shiftType][] = [
                'name' => $assignment->employee->name,
                'employee_id' => $assignment->employee->id
            ];
        }

        $roleNameGenitive = match ($user->role) {
            'cook' => 'поваров',
            'waiter' => 'официантов',
            'hostess' => 'хостес',
            'bartender' => 'барменов',
            'admin' => 'администраторов',
            default => $user->role,
        };
        $title = "График {$roleNameGenitive}";

        // Навигация
        $prevPeriodStart = $periodStartDate->copy()->subDays(14)->format('Y-m-d');
        $nextPeriodStart = $periodStartDate->copy()->addDays(14)->format('Y-m-d');
        $currentPeriodStart = now()->startOfWeek()->format('Y-m-d');

        // Даты
        $dates = [];
        $currentDateIterator = $periodStartDate->copy();
        for ($i = 0; $i < 14; $i++) {
            $dates[] = $currentDateIterator->format('Y-m-d');
            $currentDateIterator->addDay();
        }

        return view('dashboard', compact(
            'schedule',
            'assignments',
            'title',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'dates'
        ));
    }

    // Запустить генерацию (только для админа)
    public function generate(Request $request)
    {
        // Получаем дату начала периода из запроса или используем текущую неделю
        $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
        $startDate = Carbon::parse($startDateInput)->startOfWeek();

        // Вызываем Artisan-команду с указанной датой
        Artisan::call('schedule:generate', [
            'start_date' => $startDate->format('Y-m-d')
        ]);

        return redirect()->back()
            ->with('success', 'График успешно сгенерирован с учетом приоритета пожеланий!');
    }

    // Форма ручного редактирования смены (только админ)
    public function edit(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $roles = array_keys(Employee::ROLES);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'role' => ['required', 'string', 'in:' . implode(',', $roles)],
            'shift' => ['required', 'string', 'in:morning,day,night'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $role = $validated['role'];
        $shift = $validated['shift'];

        // Список активных сотрудников этой роли
        $employees = Employee::active()
            ->byRole($role)
            ->orderBy('name')
            ->get();

        // Уже назначенные сотрудники на эту дату/смену/роль
        $assignedEmployeeIds = ShiftAssignment::where('date', $date)
            ->where('shift_type', $shift)
            ->whereHas('employee', fn($q) => $q->where('role', $role))
            ->pluck('employee_id')
            ->toArray();

        $roleName = Employee::ROLES[$role] ?? $role;

        $redirectTo = $request->input('redirect_to', url()->previous());

        return view('schedule.edit', compact(
            'date',
            'role',
            'shift',
            'employees',
            'assignedEmployeeIds',
            'roleName',
            'redirectTo'
        ));
    }

    // Сохранение ручных правок смены (только админ)
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $roles = array_keys(Employee::ROLES);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'role' => ['required', 'string', 'in:' . implode(',', $roles)],
            'shift' => ['required', 'string', 'in:morning,day,night'],
            'employees' => ['nullable', 'array'],
            'employees.*' => ['integer', 'exists:employees,id'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $role = $validated['role'];
        $shift = $validated['shift'];
        $employeeIds = $validated['employees'] ?? [];

        // Удаляем старые назначения по этой дате/роли/смене
        ShiftAssignment::where('date', $date)
            ->where('shift_type', $shift)
            ->whereHas('employee', fn($q) => $q->where('role', $role))
            ->delete();

        // Создаем новые назначения
        foreach ($employeeIds as $employeeId) {
            ShiftAssignment::create([
                'employee_id' => $employeeId,
                'date' => $date,
                'shift_type' => $shift,
                'is_approved' => true,
            ]);
        }

        $redirectTo = $validated['redirect_to'] ?? route('dashboard', [
            'period_start' => Carbon::parse($date)->startOfWeek()->format('Y-m-d'),
        ]);

        return redirect($redirectTo)
            ->with('success', 'График успешно обновлён.');
    }
}
