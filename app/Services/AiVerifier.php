<?php

namespace App\Services;

use App\DTOs\AiVerificationResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiVerifier
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey  = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.groq.com/openai/v1');
        $this->model   = config('services.openai.model', 'llama-3.3-70b-versatile');
    }

    public function verify(string $productName, string $category, array $candidates): AiVerificationResult
    {
        $candidateList = collect($candidates)
            ->map(fn($c, $i) =>
                ($i + 1) . ". Title: {$c['title']}, Channel: {$c['channel']}, Description: {$c['description']}"
            )
            ->implode("\n");

        $prompt = <<<EOT
You are a video game trailer matcher. Given a product and video candidates, return ONLY valid JSON, no extra text.

Product: "{$productName}" (Category: {$category})

Candidates:
{$candidateList}

Return JSON with this exact structure:
{
  "is_match": true,
  "selected_video_id": "VIDEO_ID_HERE",
  "accuracy": 85,
  "reason": "2-4 sentence explanation of matching signals used."
}

Rules:
- is_match must be true only if accuracy >= 75
- If no good match exists, return is_match: false, selected_video_id: null
- accuracy is a number from 0 to 100
- reason must mention specific signals: title match, channel name, keywords found, etc.
EOT;

        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->post($this->baseUrl . '/chat/completions', [
                'model'       => $this->model,
                'temperature' => 0.1,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a strict JSON-only responder. Never add markdown, code blocks, or extra text. Return raw JSON only.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('AI API error: ' . $response->status() . ' — ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (empty($content)) {
            throw new RuntimeException('Empty response from AI API');
        }

        $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
        $content = preg_replace('/\s*```$/', '', $content);

        $decoded = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON from AI: ' . json_last_error_msg() . ' — Raw: ' . $content);
        }

        if (!array_key_exists('is_match', $decoded) || !array_key_exists('accuracy', $decoded)) {
            throw new RuntimeException('AI response missing required fields: ' . $content);
        }

        return AiVerificationResult::fromArray($decoded);
    }
}
