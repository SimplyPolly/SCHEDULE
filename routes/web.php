<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\Admin\EmployeeController;
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

    // === Генерация графика (только для админов) ===
    Route::post('/schedule/generate', [ScheduleController::class, 'generate'])
        ->middleware('admin')
        ->name('schedule.generate');

    // === Профиль пользователя ===
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === АДМИН-ПАНЕЛЬ: Сотрудники ===
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

// Маршруты аутентификации (login, register, password reset и т.д.)
require __DIR__ . '/auth.php';