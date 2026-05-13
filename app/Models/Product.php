<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'denumire', 'categorie', 'youtube_url', 'youtube_video_id',
        'youtube_found_at', 'ai_verified', 'ai_accuracy', 'ai_explanation',
    ];

    protected $casts = [
        'ai_verified'      => 'boolean',
        'youtube_found_at' => 'datetime',
        'ai_accuracy'      => 'float',
    ];

    public function videoCandidates(): HasMany
    {
        return $this->hasMany(VideoCandidate::class)->latest();
    }
}
