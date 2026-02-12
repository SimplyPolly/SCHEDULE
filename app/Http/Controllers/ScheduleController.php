<?php

namespace App\Http\Controllers;

use App\Models\ShiftAssignment;
use App\Models\Employee;
use App\Models\ShiftRequirement;
use App\Models\AlgorithmSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
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

        // Проверяем текущие нехватки для администратора
        $shortagesInfo = $user->role === 'admin' ? $this->getCurrentShortagesInfo($periodStartDate, $periodEndDate) : null;

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
            'dates',
            'shortagesInfo'
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

        // Проверяем текущие нехватки для администратора
        $shortagesInfo = $user->role === 'admin' ? $this->getCurrentShortagesInfo($periodStartDate, $periodEndDate) : null;

        return view('dashboard', compact(
            'schedule',
            'assignments',
            'title',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'dates',
            'shortagesInfo'
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

        // Проверяем текущие нехватки для администратора
        $shortagesInfo = $user->role === 'admin' ? $this->getCurrentShortagesInfo($periodStartDate, $periodEndDate) : null;

        return view('dashboard', compact(
            'schedule',
            'assignments',
            'title',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'dates',
            'shortagesInfo'
        ));
    }

    // Запустить генерацию (только для админа)
    public function generate(Request $request)
    {
        // Проверяем, есть ли уже график для этого периода
        $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
        $startDate = Carbon::parse($startDateInput)->startOfWeek();
        $endDate = $startDate->copy()->addDays(13);

        // Проверяем, есть ли уже назначения для этого периода
        $existingAssignments = ShiftAssignment::whereBetween('date', [$startDate, $endDate])->count();

        if ($existingAssignments > 0) {
            // Если график уже существует, просто редиректим
            session()->flash('warning', 'График уже сгенерирован. Используйте ручное редактирование для внесения изменений.');
            return redirect()->route('dashboard', ['period_start' => $startDate->format('Y-m-d')]);
        }

        try {
            // Очищаем старый файл с нехватками
            $this->clearOldShortageFiles();

            // Вызываем Artisan-команду с указанной датой
            $exitCode = Artisan::call('schedule:generate', [
                'start_date' => $startDate->format('Y-m-d')
            ]);

            // Проверяем наличие нехваток
            $warningMessage = $this->checkForShortages($startDate);

            if ($warningMessage) {
                session()->flash('warning', $warningMessage);
            }

            if ($exitCode === 0) {
                session()->flash('success', 'График успешно сгенерирован!');
                session()->flash('generation_message', 'График сгенерирован с учетом пожеланий сотрудников.');
            } else {
                session()->flash('success', 'График сгенерирован, но есть проблемы с укомплектованностью.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Ошибка генерации графика: ' . $e->getMessage());
        }

        return redirect()->route('dashboard', ['period_start' => $startDate->format('Y-m-d')]);
    }

    // Вспомогательные методы - добавь их в этот же контроллер
    private function clearOldShortageFiles(): void
    {
        $filePath = storage_path('logs/schedule_shortages.json');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function checkForShortages(Carbon $startDate): ?string
    {
        $filePath = storage_path('logs/schedule_shortages.json');

        if (!file_exists($filePath)) {
            return null;
        }

        $shortageData = json_decode(file_get_contents($filePath), true);

        if (empty($shortageData['shortages'])) {
            return null;
        }

        return $this->formatShortageWarning($shortageData['shortages']);
    }

    private function formatShortageWarning(array $shortages): string
    {
        $total = count($shortages);

        $dates = [];
        $roles = [];

        foreach ($shortages as $shortage) {
            $date = Carbon::parse($shortage['date'])->format('d.m');
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }

            $role = match ($shortage['role']) {
                'cook' => 'поваров',
                'waiter' => 'официантов',
                'bartender' => 'барменов',
                'hostess' => 'хостес',
                default => $shortage['role'],
            };

            if (!in_array($role, $roles)) {
                $roles[] = $role;
            }
        }

        $datesStr = implode(', ', array_slice($dates, 0, 3));
        if (count($dates) > 3) {
            $datesStr .= ' и ещё ' . (count($dates) - 3) . ' дней';
        }

        $roleStr = implode(', ', $roles);

        return "⚠️ Обнаружены неукомплектованные смены: {$total} смен за {$datesStr} ({$roleStr})";
    }

    // Новый метод: Получить информацию о текущих нехватках для периода
    private function getCurrentShortagesInfo(Carbon $periodStart, Carbon $periodEnd): ?array
    {
        $roles = ['cook', 'waiter', 'hostess', 'bartender', 'admin'];
        $shortageShifts = [];
        $totalShortages = 0;

        $season = AlgorithmSetting::getValue('current_season', 'season');

        Log::info("=== Начало проверки нехваток с {$periodStart->format('Y-m-d')} по {$periodEnd->format('Y-m-d')} ===");

        foreach ($roles as $role) {
            $availableShifts = $this->getShiftTypesForRole($role);

            foreach ($availableShifts as $shift) {
                // Получаем все назначения для этой роли и смены в периоде
                $assignments = ShiftAssignment::with('employee')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->where('shift_type', $shift)
                    ->whereHas('employee', fn($q) => $q->where('role', $role))
                    ->get();

                // Группируем по дате, приводя дату к строковому формату Y-m-d
                $assignmentsByDate = [];
                foreach ($assignments as $assignment) {
                    $dateKey = $assignment->date instanceof Carbon
                        ? $assignment->date->format('Y-m-d')
                        : Carbon::parse($assignment->date)->format('Y-m-d');

                    if (!isset($assignmentsByDate[$dateKey])) {
                        $assignmentsByDate[$dateKey] = [];
                    }
                    $assignmentsByDate[$dateKey][] = $assignment;
                }

                // Проверяем каждый день в периоде
                $currentDate = $periodStart->copy();
                while ($currentDate <= $periodEnd) {
                    $dateStr = $currentDate->format('Y-m-d');

                    // Получаем минимальное количество из ShiftRequirement
                    $dayType = $this->getDayType($currentDate);
                    $requiredMin = ShiftRequirement::getMinStaff($dayType, $shift, $role, $season);

                    // Если смена вообще не требуется (requiredMin = 0 или null), пропускаем
                    if ($requiredMin > 0) {
                        $assignedCount = isset($assignmentsByDate[$dateStr]) ? count($assignmentsByDate[$dateStr]) : 0;

                        Log::info("Требования для {$role} на {$shift} {$dateStr}: требуется {$requiredMin}, назначено {$assignedCount}");

                        if ($assignedCount < $requiredMin) {
                            $shortageShifts[] = [
                                'date' => $dateStr,
                                'role' => $role,
                                'shift' => $shift,
                                'assigned' => $assignedCount,
                                'expected' => $requiredMin,
                                'missing' => $requiredMin - $assignedCount
                            ];

                            $totalShortages += ($requiredMin - $assignedCount);
                        }
                    }

                    $currentDate->addDay();
                }
            }
        }

        Log::info("=== Конец проверки нехваток ===");
        Log::info("Найдено смен с нехватками: " . count($shortageShifts));
        Log::info("Общее количество недостающих сотрудников: {$totalShortages}");

        if (empty($shortageShifts)) {
            return null;
        }

        return [
            'shortages' => $shortageShifts,
            'total_shifts' => count($shortageShifts),
            'total_shortages' => $totalShortages,
            'message' => $this->formatCurrentShortages($shortageShifts, $totalShortages)
        ];
    }

    // Обновленный метод форматирования
    private function formatCurrentShortages(array $shortageShifts, int $totalShortages): string
    {
        $totalShifts = count($shortageShifts);

        $dates = [];
        $roles = [];

        foreach ($shortageShifts as $shortage) {
            $date = Carbon::parse($shortage['date'])->format('d.m');
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }

            $role = match ($shortage['role']) {
                'cook' => 'поваров',
                'waiter' => 'официантов',
                'bartender' => 'барменов',
                'hostess' => 'хостес',
                'admin' => 'администраторов',
                default => $shortage['role'],
            };

            if (!in_array($role, $roles)) {
                $roles[] = $role;
            }
        }

        $datesStr = implode(', ', array_slice($dates, 0, 3));
        if (count($dates) > 3) {
            $datesStr .= ' и ещё ' . (count($dates) - 3) . ' дней';
        }

        $roleStr = implode(', ', $roles);

        return "Обнаружены неукомплектованные смены: {$totalShifts} смен (недостаёт {$totalShortages} сотрудников) за {$datesStr} ({$roleStr})";
    }

    // Вспомогательные методы для определения типов смен и дней
    private function getShiftTypesForRole(string $role): array
    {
        if (in_array($role, ['waiter', 'hostess'])) {
            return ['morning', 'day', 'night'];
        }
        return ['day', 'night'];
    }

    private function getDayType(Carbon $date): string
    {
        $dayOfWeek = $date->dayOfWeek;

        if ($dayOfWeek === 5) {
            return 'holiday';
        } elseif (in_array($dayOfWeek, [0, 6])) {
            return 'weekend';
        } else {
            return 'weekday';
        }
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
