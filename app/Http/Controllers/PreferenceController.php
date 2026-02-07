<?php

namespace App\Http\Controllers;

use App\Models\ShiftPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PreferenceController extends \Illuminate\Routing\Controller
{
    public function calendar(Request $request)
    {
        /** @var \App\Models\Employee $user */
        $user = Auth::user();

        // Определяем 2-недельный период так же, как в графике:
        // от понедельника на 14 дней вперёд
        $periodStartDate = $request->has('period_start')
            ? Carbon::parse($request->period_start)->startOfWeek()
            : now()->startOfWeek();

        $periodEndDate = $periodStartDate->copy()->addDays(13);

        // Навигация по 2-недельным периодам (шаг 14 дней)
        $prevPeriodStart = $periodStartDate->copy()->subDays(14)->format('Y-m-d');
        $nextPeriodStart = $periodStartDate->copy()->addDays(14)->format('Y-m-d');
        $currentPeriodStart = now()->startOfWeek()->format('Y-m-d');

        // Дата генерации графика для этого периода — четверг перед началом периода
        // (период начинается с понедельника, генерация в предыдущий четверг).
        $generationDate = $periodStartDate->copy()->subDays(4)->startOfDay();
        $deadline = $generationDate; // до начала четверга можно подавать пожелания

        $now = now();
        $isDeadlinePassed = $now->greaterThanOrEqualTo($deadline);

        $calendarGrid = [];
        $current = $periodStartDate->copy();
        for ($week = 0; $week < 2; $week++) {
            $row = [];
            for ($day = 0; $day < 7; $day++) {
                $row[] = [
                    'date' => $current->format('Y-m-d'),
                    'day_number' => $current->format('j'),
                    'month_name' => $current->isoFormat('MMM'),
                    'weekday_short' => $current->isoFormat('ddd'),
                    'isPast' => $current->lt(now()->startOfDay()),
                ];
                $current->addDay();
            }
            $calendarGrid[] = $row;
        }

        $preferences = [];
        foreach (Auth::user()->preferences as $pref) {
            $preferences[$pref->date->format('Y-m-d')] = $pref;
        }
        $isSubmitted = $user->hasSubmittedPreferences();

        // Можно ли редактировать этот период: не истёк дедлайн и пользователь ещё не зафиксировал пожелания
        $canEdit = !$isSubmitted && !$isDeadlinePassed;

        return view('preferences.calendar', compact(
            'calendarGrid',
            'preferences',
            'isSubmitted',
            'canEdit',
            'periodStartDate',
            'periodEndDate',
            'prevPeriodStart',
            'nextPeriodStart',
            'currentPeriodStart',
            'generationDate',
            'deadline',
            'isDeadlinePassed'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:day_off,prefer_morning,prefer_day,prefer_night,avoid_morning,avoid_day,avoid_night',
            'period_start' => 'nullable|date',
        ]);

        /** @var \App\Models\Employee $user */
        $user = Auth::user();

        // Определяем период, для которого подаются пожелания
        $date = Carbon::parse($request->date)->startOfDay();
        $periodStartDate = $request->filled('period_start')
            ? Carbon::parse($request->period_start)->startOfWeek()
            : $date->copy()->startOfWeek();

        $periodEndDate = $periodStartDate->copy()->addDays(13);

        // Проверяем, что дата попадает в выбранный 2-недельный период
        if ($date->lt($periodStartDate) || $date->gt($periodEndDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Дата не входит в выбранный период для пожеланий.',
            ], 422);
        }

        // Дедлайн: до начала четверга перед началом периода
        $generationDate = $periodStartDate->copy()->subDays(4)->startOfDay();
        $deadline = $generationDate;

        if (now()->greaterThanOrEqualTo($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Дедлайн для подачи пожеланий на этот период уже прошёл.',
            ], 422);
        }

        if ($user->hasSubmittedPreferences()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы уже зафиксировали свои пожелания, редактирование недоступно.',
            ], 422);
        }

        $user->preferences()->updateOrCreate(
            ['date' => $request->date],
            ['type' => $request->type, 'submitted_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    public function submit(Request $request)
    {
        /** @var \App\Models\Employee $user */
        $user = Auth::user();

        // Период, который пользователь фиксирует (тот же, что в календаре)
        $request->validate([
            'period_start' => 'required|date',
        ]);

        $periodStartDate = Carbon::parse($request->period_start)->startOfWeek();
        $generationDate = $periodStartDate->copy()->subDays(4)->startOfDay();
        $deadline = $generationDate;

        if (now()->greaterThanOrEqualTo($deadline)) {
            return back()->withErrors([
                'msg' => 'Дедлайн для подтверждения пожеланий на этот период уже прошёл.',
            ]);
        }

        if ($user->hasSubmittedPreferences()) {
            return back()->withErrors(['msg' => 'Пожелания уже зафиксированы.']);
        }

        if (!$user->preferences()->exists()) {
            return back()->withErrors(['msg' => 'Сначала укажите хотя бы одно пожелание.']);
        }

        $user->update(['preferences_submitted_at' => now()]);

        return redirect()->route('preferences.calendar')
            ->with('success', 'Пожелания сохранены. Редактирование недоступно.');
    }
}
