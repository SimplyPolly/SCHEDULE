<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\AlgorithmSettingsController;
use App\Http\Controllers\Admin\StaffRequirementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Главная → сразу на вход
Route::redirect('/', '/login');

// Дашборд — основная страница после входа (только для авторизованных и подтверждённых)
Route::get('/dashboard', [ScheduleController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Все защищённые маршруты (требуют авторизации)
Route::middleware('auth')->group(function () {

    // === Графики ===
    Route::get('/schedule/role', [ScheduleController::class, 'byRole'])->name('schedule.role');
    Route::get('/schedule/personal', [ScheduleController::class, 'personal'])->name('schedule.personal');

    // === Пожелания ===
    Route::get('/preferences/calendar', [PreferenceController::class, 'calendar'])->name('preferences.calendar');
    Route::post('/preferences', [PreferenceController::class, 'store'])->name('preferences.store');
    Route::post('/preferences/submit', [PreferenceController::class, 'submit'])->name('preferences.submit');

    // === Профиль пользователя ===
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === АДМИН-ПАНЕЛЬ ===
    Route::middleware('admin')->group(function () {
        // Редактирование графика
        Route::get('/schedule/edit', [ScheduleController::class, 'edit'])->name('schedule.edit');
        Route::post('/schedule/update', [ScheduleController::class, 'update'])->name('schedule.update');

        // Генерация графика
        Route::post('/schedule/generate', [ScheduleController::class, 'generate'])
            ->name('schedule.generate');

        // Настройки алгоритма
        Route::get('/algorithm-settings', [AlgorithmSettingsController::class, 'index'])
            ->name('algorithm.settings');
        Route::post('/algorithm-settings', [AlgorithmSettingsController::class, 'update'])
            ->name('algorithm.settings.update');

        // Требования к штату
        Route::get('/staff-requirements', [StaffRequirementController::class, 'index'])
            ->name('staff-requirements.index');
        Route::post('/staff-requirements/update', [StaffRequirementController::class, 'update'])
            ->name('staff-requirements.update');
        Route::post('/staff-requirements/season-toggle', [StaffRequirementController::class, 'toggleSeason'])
            ->name('staff-requirements.toggle-season');

        // Управление сотрудниками
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('employees')->name('employees.')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->name('index');
                Route::get('/create', [EmployeeController::class, 'create'])->name('create');
                Route::post('/', [EmployeeController::class, 'store'])->name('store');
                Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
                Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
                Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
                Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
            });
        });
    });
});

// Маршруты аутентификации (login, register, password reset и т.д.)
require __DIR__ . '/auth.php';
