<?php

namespace App\Services\Ai;

final readonly class AiResponse
{
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public int $thinkingTokens,
        public int $latencyMs,
        public ?string $finishReason,
    ) {}
}
