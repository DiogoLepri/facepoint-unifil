<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';
    
    protected $fillable = [
        'user_id',
        'entry_time',
        'exit_time',
        'status',
        'justification',
        'is_early',
        'is_late',
        'expected_time',
        'minutes_difference',
        'punch_type'
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'expected_time' => 'datetime',
        'is_early' => 'boolean',
        'is_late' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}