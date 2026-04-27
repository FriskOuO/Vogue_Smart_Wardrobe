<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEmbedding extends Model
{
    protected $fillable = [
        'user_id',
        'clothing_id',
        'embedding_type',
        'source_type',
        'source_text',
        'model',
        'vector_dimension',
        'embedding',
        'embedding_preview',
        'vector_provider',
        'vector_collection',
        'vector_point_id',
        'vector_stored',
        'status',
        'mode',
        'degraded_reason',
        'raw_result',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'embedding' => 'array',
        'embedding_preview' => 'array',
        'raw_result' => 'array',
        'vector_stored' => 'boolean',
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