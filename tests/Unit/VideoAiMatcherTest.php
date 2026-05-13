<?php

namespace Tests\Unit;

use App\DTOs\AiVerificationResult;
use App\Jobs\SearchYoutubeAndVerifyJob;
use App\Models\Product;
use App\Services\AiVerifier;
use App\Services\YouTubeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoAiMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_youtube_query_appends_trailer_keyword(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [[
                    'id'      => ['videoId' => 'abc123'],
                    'snippet' => [
                        'title'        => 'Elden Ring Official Trailer',
                        'channelTitle' => 'FromSoftware',
                        'description'  => 'The official trailer.',
                        'publishedAt'  => '2021-06-10T00:00:00Z',
                    ],
                ]],
            ], 200),
        ]);

        $client = new YouTubeClient();
        $items  = $client->searchVideos('Elden Ring');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'trailer');
        });

        $this->assertNotEmpty($items);
        $this->assertEquals('abc123', $items[0]['id']['videoId']);
    }

    public function test_youtube_client_throws_on_api_error(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['error' => 'Forbidden'], 403),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/YouTube API error/');

        $client = new YouTubeClient();
        $client->searchVideos('Some Game');
    }

    public function test_ai_verifier_parses_valid_json_response(): void
    {
        Http::fake([
            'groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_match'          => true,
                            'selected_video_id' => 'xyz999',
                            'accuracy'          => 92,
                            'reason'            => 'Title matches exactly.',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $verifier = new AiVerifier();
        $result   = $verifier->verify('Cyberpunk 2077', 'RPG', [
            ['video_id' => 'xyz999', 'title' => 'Cyberpunk 2077 Trailer', 'channel' => 'CDPR', 'description' => ''],
        ]);

        $this->assertInstanceOf(AiVerificationResult::class, $result);
        $this->assertTrue($result->isMatch);
        $this->assertEquals('xyz999', $result->selectedVideoId);
        $this->assertEquals(92, $result->accuracy);
    }

    public function test_product_not_updated_when_ai_rejects_match(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => [[
                'id'      => ['videoId' => 'bad001'],
                'snippet' => [
                    'title'        => 'Unrelated Video',
                    'channelTitle' => 'RandomChannel',
                    'description'  => '',
                    'publishedAt'  => '2020-01-01T00:00:00Z',
                ],
            ]]], 200),
            'groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_match'          => false,
                            'selected_video_id' => null,
                            'accuracy'          => 10,
                            'reason'            => 'No match found.',
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $product = Product::factory()->create(['denumire' => 'Unknown Game', 'categorie' => 'PC Digital']);

        $job = new SearchYoutubeAndVerifyJob($product);
        $job->handle(new YouTubeClient(), new AiVerifier());

        $this->assertNull($product->fresh()->youtube_video_id);
        $this->assertFalse((bool) $product->fresh()->ai_verified);
    }

    public function test_job_updates_product_correctly_on_ai_match(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => [[
                'id'      => ['videoId' => 'trailer001'],
                'snippet' => [
                    'title'        => 'Hades Official Trailer',
                    'channelTitle' => 'SupergiantGames',
                    'description'  => 'Official trailer.',
                    'publishedAt'  => '2020-09-17T00:00:00Z',
                ],
            ]]], 200),
        ]);

        $aiMock = $this->createMock(AiVerifier::class);
        $aiMock->method('verify')->willReturn(new AiVerificationResult(
            isMatch:         true,
            selectedVideoId: 'trailer001',
            accuracy:        95,
            reason:          'Exact product name match.',
        ));

        $product = Product::factory()->create(['denumire' => 'Hades', 'categorie' => 'PC Digital']);

        $job = new SearchYoutubeAndVerifyJob($product);
        $job->handle(new YouTubeClient(), $aiMock);

        $this->assertEquals('trailer001', $product->fresh()->youtube_video_id);
        $this->assertTrue((bool) $product->fresh()->ai_verified);
        $this->assertEquals(95, $product->fresh()->ai_accuracy);
    }
}
