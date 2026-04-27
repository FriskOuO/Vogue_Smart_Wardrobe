<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJob extends Model
{
    protected $fillable = [
        'user_id',
        'clothing_id',
        'job_type',
        'status',
        'mode',
        'request_id',
        'input_json',
        'result_json',
        'degraded_reason',
        'error_code',
        'error_message',
        'retry_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input_json' => 'array',
        'result_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clothing(): BelongsTo
    {
        return $this->belongsTo(Clothing::class, 'clothing_id');
    }
}