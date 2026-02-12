<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRequirement extends Model
{
    use HasFactory;

    protected $fillable = ['season', 'day_type', 'shift_type', 'role', 'min_staff'];

    // Типы смен
    const SHIFT_TYPES = [
        'morning' => 'Утро (09:00-15:00)',
        'day' => 'День (13:00-19:00)',
        'night' => 'Ночь (18:00-01:00)'
    ];

    // Сезоны
    const SEASONS = [
        'season' => 'Сезон',
        'offseason' => 'Межсезонье',
    ];

    // Роли
    const ROLES = [
        'cook' => 'Повар',
        'waiter' => 'Официант',
        'hostess' => 'Хостес',
        'bartender' => 'Бармен',
        'admin' => 'Администратор',
    ];

    // Типы дней
    const DAY_TYPES = [
        'weekday' => 'Будни (Пн-Чт)',
        'holiday' => 'Пятница',
        'weekend' => 'Выходные (Сб-Вс)',
    ];

    /**
     * Получить минимальный штат с учетом сезона
     * ТОЛЬКО ИЗ БАЗЫ ДАННЫХ - никаких вшитых значений!
     */
    public static function getMinStaff(string $dayType, string $shiftType, string $role, ?string $season = null): int
    {
        // Если сезон не указан, берем текущий из настроек
        if (!$season) {
            $season = AlgorithmSetting::getValue('season', 'season'); // ИСПРАВЛЕНО: current_season → season
        }

        // Ищем в БД
        $req = self::where('season', $season)
            ->where('day_type', $dayType)
            ->where('shift_type', $shiftType)
            ->where('role', $role)
            ->first();
        
        // ✅ ТОЛЬКО из БД. Если нет записи - возвращаем 0 (смена не требуется)
        // Это заставит пользователя создать требования через веб-интерфейс
        return $req ? (int) $req->min_staff : 0;
    }

    /**
     * УДАЛЕН МЕТОД getDefaultMinStaff() - больше никаких вшитых требований!
     */

    /**
     * Получить все требования для сезона
     */
    public static function getForSeason(string $season): array
    {
        return self::where('season', $season)
            ->get()
            ->groupBy(['role', 'shift_type', 'day_type'])
            ->map(function ($shifts) {
                return $shifts->groupBy('day_type')->map(function ($days) {
                    return $days->first()->min_staff;
                });
            })
            ->toArray();
    }

    /**
     * Получить сгруппированные требования для формы
     */
    public static function getGroupedRequirements(string $season): array
    {
        return self::where('season', $season)
            ->get()
            ->groupBy(['role'])
            ->map(function ($roles) {
                return $roles->groupBy(['shift_type'])
                    ->map(function ($shifts) {
                        return $shifts->keyBy('day_type')
                            ->map(function ($item) {
                                return $item->min_staff;
                            });
                    });
            })
            ->toArray();
    }

    /**
     * Аксессоры для красивых названий
     */
    public function getSeasonNameAttribute(): string
    {
        return self::SEASONS[$this->season] ?? $this->season;
    }

    public function getShiftTypeNameAttribute(): string
    {
        return self::SHIFT_TYPES[$this->shift_type] ?? $this->shift_type;
    }

    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getDayTypeNameAttribute(): string
    {
        return self::DAY_TYPES[$this->day_type] ?? $this->day_type;
    }
}