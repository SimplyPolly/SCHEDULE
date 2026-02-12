<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftRequirement;
use App\Models\AlgorithmSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffRequirementController extends Controller
{
    public function index()
    {
        // ИСПРАВЛЕНО: current_season → season
        $currentSeason = AlgorithmSetting::getValue('season', 'season');
        
        // Получаем требования в удобном формате для отображения
        $requirements = ShiftRequirement::getGroupedRequirements($currentSeason);
        
        // Определения для формы
        $roles = ShiftRequirement::ROLES;
        $shiftTypes = [
            'cook' => ['day', 'night'],
            'waiter' => ['morning', 'day', 'night'],
            'hostess' => ['morning', 'day', 'night'],
            'bartender' => ['day', 'night'],
            'admin' => ['day', 'night'],
        ];
        
        $dayTypes = ShiftRequirement::DAY_TYPES;
        $seasons = ShiftRequirement::SEASONS;
        
        return view('admin.staff-requirements', compact(
            'requirements',
            'roles',
            'shiftTypes',
            'dayTypes',
            'seasons',
            'currentSeason'
        ));
    }
    
    /**
     * ИСПРАВЛЕНО: Правильная обработка 3D массива из формы
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'requirements' => 'required|array',
            'requirements.*' => 'array', // role
            'requirements.*.*' => 'array', // shift
            'requirements.*.*.*' => 'integer|min:0|max:10', // day_type
        ]);

        // ИСПРАВЛЕНО: current_season → season
        $season = AlgorithmSetting::getValue('season', 'season');
        
        DB::transaction(function () use ($validated, $season) {
            foreach ($validated['requirements'] as $role => $shifts) {
                foreach ($shifts as $shiftType => $dayTypes) {
                    foreach ($dayTypes as $dayType => $minStaff) {
                        ShiftRequirement::updateOrCreate(
                            [
                                'season' => $season,
                                'role' => $role,
                                'shift_type' => $shiftType,
                                'day_type' => $dayType,
                            ],
                            [
                                'min_staff' => (int) $minStaff,
                            ]
                        );
                    }
                }
            }
        });
        
        return redirect()->back()->with('success', 'Требования к штату обновлены!');
    }
    
    /**
     * ИСПРАВЛЕНО: Переключение сезона
     */
    public function toggleSeason(Request $request)
    {
        $validated = $request->validate([
            'season' => 'required|in:season,offseason',
        ]);
        
        // ИСПРАВЛЕНО: current_season → season
        AlgorithmSetting::setValue('season', $validated['season'], 'general', 'Текущий сезон работы');
        
        return redirect()->back()->with('success', 
            "Сезон изменен на: " . ShiftRequirement::SEASONS[$validated['season']]);
    }
}