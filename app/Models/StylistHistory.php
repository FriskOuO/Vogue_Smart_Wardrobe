<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StylistHistory extends Model
{
    protected $table = 'stylist_history';

    protected $fillable = [
        'user_id',
        'occasion',
        'weather',
        'style_preference',
        'context_json',
        'selected_items',
        'recommendation_json',
        'status',
        'mode',
        'is_accepted',
        'feedback_status',
        'feedback_reason',
        'feedback_json',
        'feedback_submitted_at',
    ];

    protected $casts = [
        'selected_items' => 'array',
        'context_json' => 'array',
        'recommendation_json' => 'array',
        'feedback_json' => 'array',
        'is_accepted' => 'boolean',
        'feedback_submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outfitLogs(): HasMany
    {
        return $this->hasMany(OutfitLog::class, 'stylist_history_id');
    }
}
