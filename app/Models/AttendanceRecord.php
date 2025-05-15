<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'entry_time',
        'exit_time',
        'status'
    ];
    
    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}