<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftPreference extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'date', 'type', 'submitted_at'];

    protected $casts = [
        'date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}