<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StylistHistory extends Model
{
    protected $table = 'stylist_history';

    protected $fillable = [
        'user_id',
        'occasion',
        'weather',
        'style_preference',
        'selected_items',
        'recommendation_json',
        'status',
        'mode',
        'is_accepted',
    ];

    protected $casts = [
        'selected_items' => 'array',
        'recommendation_json' => 'array',
        'is_accepted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}