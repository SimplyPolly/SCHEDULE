<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['employee_id', 'date', 'shift_type', 'is_approved'];

    protected $casts = [
        'date' => 'date',
        'is_approved' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
