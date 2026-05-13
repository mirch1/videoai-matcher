<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\VideoCandidate;
use App\Services\AiVerifier;
use App\Services\YouTubeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SearchYoutubeAndVerifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(private Product $product) {}

    public function handle(YouTubeClient $youtube, AiVerifier $ai): void
    {
        Log::info("Job started for product #{$this->product->id}: {$this->product->denumire}");

        $items = $youtube->searchVideos($this->product->denumire);

        if (empty($items)) {
            Log::warning("No YouTube results for #{$this->product->id}");
            return;
        }

        $candidates = [];
        foreach ($items as $item) {
            $snippet = $item['snippet'];
            $videoId = $item['id']['videoId'];

            VideoCandidate::updateOrCreate(
                ['product_id' => $this->product->id, 'video_id' => $videoId],
                [
                    'title'               => $snippet['title'],
                    'channel'             => $snippet['channelTitle'],
                    'description_snippet' => substr($snippet['description'] ?? '', 0, 300),
                    'published_at'        => isset($snippet['publishedAt'])
                        ? date('Y-m-d H:i:s', strtotime($snippet['publishedAt']))
                        : null,
                    'raw_payload' => json_encode($item),
                ]
            );

            $candidates[] = [
                'video_id'    => $videoId,
                'title'       => $snippet['title'],
                'channel'     => $snippet['channelTitle'],
                'description' => substr($snippet['description'] ?? '', 0, 200),
            ];
        }

        $result = $ai->verify($this->product->denumire, $this->product->categorie, $candidates);

        if ($result->isMatch) {
            $this->product->update([
                'youtube_video_id' => $result->selectedVideoId,
                'youtube_url'      => 'https://www.youtube.com/watch?v=' . $result->selectedVideoId,
                'youtube_found_at' => now(),
                'ai_verified'      => true,
                'ai_accuracy'      => $result->accuracy,
                'ai_explanation'   => $result->reason,
            ]);
        } else {
            $this->product->update([
                'ai_verified'    => false,
                'ai_accuracy'    => $result->accuracy,
                'ai_explanation' => $result->reason,
            ]);
        }

        Log::info("Job completed for #{$this->product->id}, match: " . ($result->isMatch ? 'YES' : 'NO'));
    }
}
