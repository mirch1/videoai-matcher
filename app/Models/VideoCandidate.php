<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCandidate extends Model
{
    protected $fillable = [
        'product_id', 'video_id', 'title', 'channel',
        'published_at', 'description_snippet', 'raw_payload',
    ];

    protected $casts = [
        'raw_payload'  => 'array',
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
