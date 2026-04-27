<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clothing extends Model
{
    use SoftDeletes;

    protected $table = 'clothes';

    protected $fillable = [
        'user_id',
        'name',
        'image_path',
        'image_url',
        'notes',
        'category',
        'subcategory',
        'color',
        'secondary_colors',
        'season',
        'occasion',
        'usage',
        'style_tags',
        'material_guess',
        'pattern',
        'brand',
        'price',
        'size',
        'wear_count',
        'last_worn_at',
        'ai_status',
        'ai_mode',
        'ai_confidence',
        'ai_raw_result',
        'ai_error_code',
        'ai_error_message',
    ];

    protected $casts = [
        'secondary_colors' => 'array',
        'season' => 'array',
        'occasion' => 'array',
        'usage' => 'array',
        'style_tags' => 'array',
        'ai_raw_result' => 'array',
        'last_worn_at' => 'datetime',
        'ai_confidence' => 'float',
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(AiEmbedding::class, 'clothing_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AiJob::class, 'clothing_id');
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }

        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }

        return null;
    }
}