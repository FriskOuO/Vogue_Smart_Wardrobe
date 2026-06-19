<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutfitLog extends Model
{
    protected $fillable = [
        'user_id',
        'stylist_history_id',
        'name',
        'logged_at',
        'occasion',
        'weather',
        'source',
        'selected_items',
        'item_ids',
        'item_count',
        'context_json',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'selected_items' => 'array',
        'item_ids' => 'array',
        'context_json' => 'array',
        'metadata' => 'array',
        'item_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stylistHistory(): BelongsTo
    {
        return $this->belongsTo(StylistHistory::class, 'stylist_history_id');
    }
}
