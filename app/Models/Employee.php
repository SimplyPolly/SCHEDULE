<?php

namespace App\Models;

use App\Notifications\EmployeeResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use Notifiable;

    const ROLES = [
        'cook' => 'Повар',
        'waiter' => 'Официант', 
        'hostess' => 'Хостес',
        'bartender' => 'Бармен',
        'admin' => 'Администратор'
    ];

    // Последовательности смен, которые можно совмещать
    const ALLOWED_SHIFT_COMBINATIONS = [
        ['morning', 'day'],   // Утро + День
        ['day', 'night'],     // День + Ночь
    ];

    // Для ролей с 2 сменами - можно работать обе смены
    const DOUBLE_SHIFT_ROLES = ['cook', 'bartender', 'admin'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'preferences_submitted_at',
        'phone',
        'telegram',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'preferences_submitted_at' => 'datetime',
    ];

    /**
     * Отправка письма для установки / сброса пароля c кастомным текстом.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        $this->notify(new EmployeeResetPassword($url));
    }

    public function hasSubmittedPreferences(): bool
    {
        return $this->preferences_submitted_at !== null;
    }

    // Определяем, у кого 2 длинные смены
    public function hasTwoShifts(): bool
    {
        return in_array($this->role, self::DOUBLE_SHIFT_ROLES);
    }

    // Может ли сотрудник работать две смены в день
    public function canWorkDoubleShift(): bool
    {
        return $this->hasTwoShifts();
    }

    // Получаем доступные типы смен для сотрудника
    public function getAvailableShiftTypes(): array
    {
        if ($this->hasTwoShifts()) {
            return ['day', 'night']; // Длинные смены: день (09:00-21:00) и ночь (19:00-09:00)
        }
        
        return ['morning', 'day', 'night']; // Короткие смены для официантов и хостес
    }

    // Проверяем, можно ли совместить две смены
    public static function canCombineShifts(string $shift1, string $shift2): bool
    {
        foreach (self::ALLOWED_SHIFT_COMBINATIONS as $combination) {
            if (in_array($shift1, $combination) && in_array($shift2, $combination)) {
                return true;
            }
        }
        return false;
    }

    public function preferences()
    {
        return $this->hasMany(ShiftPreference::class);
    }

    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    // Получить назначения сотрудника на конкретную дату
    public function getAssignmentsForDate($date)
    {
        return $this->assignments()
            ->where('date', $date)
            ->get();
    }

    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Scope для активных сотрудников
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}