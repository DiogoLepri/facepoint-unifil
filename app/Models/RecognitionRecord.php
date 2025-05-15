<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecognitionRecord extends Model
{
    protected $fillable = [
        'user_id',
        'face_descriptor',
        'capture_type',
    ];

    // Make sure JSON is properly handled
    protected $casts = [
        'face_descriptor' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}