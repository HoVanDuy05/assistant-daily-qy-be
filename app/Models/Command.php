<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $fillable = [
        'user_id',
        'raw_input',
        'parsed_actions',
        'status',
        'results',
        'executed_at',
    ];

    protected $casts = [
        'parsed_actions' => 'array',
        'results' => 'array',
        'executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
