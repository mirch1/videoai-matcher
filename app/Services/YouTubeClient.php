<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class YouTubeClient
{
    protected string $apiKey;
    private const CACHE_TTL_HOURS = 6;
    private const RATE_LIMIT_MAX  = 50;
    private const RATE_LIMIT_KEY  = 'youtube_rate_limit';

    public function __construct()
    {
        $this->apiKey = config('services.youtube.key', env('YOUTUBE_API_KEY', ''));
    }

    public function searchVideos(string $productName): array
    {
        $cacheKey = 'yt_search_' . md5($productName);

        if (Cache::has($cacheKey)) {
            Log::info("YouTube cache hit for: {$productName}");
            return Cache::get($cacheKey);
        }

        $this->enforceRateLimit();

        $response = Http::timeout(10)
            ->get('https://www.googleapis.com/youtube/v3/search', [
                'key'        => $this->apiKey,
                'q'          => $productName . ' official trailer',
                'part'       => 'snippet',
                'type'       => 'video',
                'maxResults' => 5,
                'safeSearch' => 'none',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('YouTube API error: ' . $response->status());
        }

        $items = $response->json('items', []);
        Cache::put($cacheKey, $items, now()->addHours(self::CACHE_TTL_HOURS));

        return $items;
    }

    private function enforceRateLimit(): void
    {
        $current = Cache::get(self::RATE_LIMIT_KEY, 0);

        if ($current >= self::RATE_LIMIT_MAX) {
            throw new RuntimeException('YouTube rate limit reached. Max ' . self::RATE_LIMIT_MAX . ' requests/minute.');
        }

        Cache::put(self::RATE_LIMIT_KEY, $current + 1, now()->addMinute());
    }
}
