<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRequirement extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftRequirementFactory> */
    use HasFactory;

    protected $fillable = ['day_type', 'shift_type', 'role', 'min_staff'];

    // Новые типы смен
    const SHIFT_TYPES = [
        'morning' => 'Утро (09:00-17:00)',
        'day' => 'День (09:00-21:00)',
        'night' => 'Ночь (19:00-09:00)'
    ];

    public static function getMinStaff(string $dayType, string $shiftType, string $role): int
    {
        $req = self::where('day_type', $dayType)
            ->where('shift_type', $shiftType)
            ->where('role', $role)
            ->first();
        return $req ? $req->min_staff : 0;
    }

    public function getShiftTypeNameAttribute(): string
    {
        return self::SHIFT_TYPES[$this->shift_type] ?? $this->shift_type;
    }
}