<?php

namespace App\DTOs;

class AiVerificationResult
{
    public function __construct(
        public readonly bool    $isMatch,
        public readonly ?string $selectedVideoId,
        public readonly float   $accuracy,
        public readonly string  $reason,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isMatch:         (bool) ($data['is_match'] ?? false),
            selectedVideoId: $data['selected_video_id'] ?? null,
            accuracy:        (float) ($data['accuracy'] ?? 0),
            reason:          $data['reason'] ?? '',
        );
    }
}
