<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WearLog extends Model
{
    protected $fillable = [
        'user_id',
        'clothing_id',
        'worn_at',
        'context',
        'source',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'worn_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clothing(): BelongsTo
    {
        return $this->belongsTo(Clothing::class);
    }
}
