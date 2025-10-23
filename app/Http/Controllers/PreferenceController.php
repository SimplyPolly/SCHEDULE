<?php

namespace App\Http\Controllers;

use App\Models\ShiftPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends \Illuminate\Routing\Controller
{
    public function calendar()
    {
        /** @var \App\Models\Employee $user */
        $user = Auth::user();

        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->addDays(13);

        $calendarGrid = [];
        $current = $startDate->copy();
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

        return view('preferences.calendar', compact('calendarGrid', 'preferences', 'isSubmitted'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:day_off,prefer_morning,prefer_day,prefer_night,avoid_morning,avoid_day,avoid_night',
        ]);

        /** @var \App\Models\Employee $user */
        $user = Auth::user();
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
