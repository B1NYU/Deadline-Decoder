<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiClient implements AiClient
{
    /**
     * Google's REST examples use the upper-case enum value. If the API ever
     * rejects it with a 400, try 'application/json' here.
     */
    private const JSON_MIME_TYPE = 'APPLICATION_JSON';

    /**
     * @param  list<int>  $retryBackoffMs  one entry per retry, so count + 1 attempts in total
     */
    public function __construct(
        private ?string $apiKey,
        private string $model,
        private string $baseUrl,
        private string $defaultThinkingLevel,
        private int $connectTimeout,
        private int $timeout,
        private int $maxOutputTokens,
        private array $retryBackoffMs,
    ) {}

    public static function fromConfig(): self
    {
        $apiKey = config('ai.gemini.api_key');

        return new self(
            apiKey: is_string($apiKey) && $apiKey !== '' ? $apiKey : null,
            model: (string) config('ai.gemini.model'),
            baseUrl: (string) config('ai.gemini.base_url'),
            defaultThinkingLevel: (string) config('ai.gemini.thinking_level'),
            connectTimeout: (int) config('ai.gemini.connect_timeout'),
            timeout: (int) config('ai.gemini.timeout'),
            maxOutputTokens: (int) config('ai.gemini.max_output_tokens'),
            retryBackoffMs: array_values(array_map(intval(...), (array) config('ai.gemini.retry_backoff_ms'))),
        );
    }

    public function generateStructured(
        string $systemPrompt,
        string $userContent,
        array $schema,
        ?string $thinkingLevel = null,
    ): AiResponse {
        if ($this->apiKey === null) {
            throw new AiRequestFailedException('GEMINI_API_KEY belum diisi di file .env.');
        }

        $startedAt = hrtime(true);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->acceptJson()
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->retry(
                    $this->retryBackoffMs,
                    0,
                    fn (Throwable $exception): bool => $this->isRetryable($exception),
                    throw: false,
                )
                ->post(
                    "/models/{$this->model}:generateContent",
                    $this->payload($systemPrompt, $userContent, $schema, $thinkingLevel ?? $this->defaultThinkingLevel),
                );
        } catch (ConnectionException $exception) {
            throw new AiRequestFailedException(
                'Tidak dapat terhubung ke layanan AI.',
                retryable: true,
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            throw $this->failureFor($response);
        }

        return $this->toAiResponse($response, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function payload(string $systemPrompt, string $userContent, array $schema, string $thinkingLevel): array
    {
        return [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $userContent]]]],
            'generationConfig' => [
                'maxOutputTokens' => $this->maxOutputTokens,
                'thinkingConfig' => ['thinkingLevel' => $thinkingLevel],
                'responseFormat' => [
                    'text' => [
                        'mimeType' => self::JSON_MIME_TYPE,
                        'schema' => $schema,
                    ],
                ],
            ],
        ];
    }

    private function isRetryable(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->response->status() === 429 || $exception->response->serverError());
    }

    private function failureFor(Response $response): AiRequestFailedException
    {
        $status = $response->status();
        $detail = $response->json('error.message');
        $detail = is_string($detail) ? Str::limit($detail, 300) : null;

        $message = match (true) {
            $status === 429 => 'Batas kuota atau laju layanan AI tercapai. Coba lagi beberapa saat.',
            $status === 401, $status === 403 => 'API key ditolak oleh layanan AI. Periksa GEMINI_API_KEY di file .env.',
            $status >= 500 => 'Layanan AI sedang bermasalah.',
            default => 'Permintaan ke layanan AI ditolak.',
        };

        if ($detail !== null && $status !== 401 && $status !== 403) {
            $message .= " Detail: {$detail}";
        }

        return new AiRequestFailedException(
            $message,
            retryable: $status === 429 || $status >= 500,
            status: $status,
        );
    }

    private function toAiResponse(Response $response, int $latencyMs): AiResponse
    {
        $data = $response->json();
        $parts = data_get($data, 'candidates.0.content.parts', []);
        $text = '';

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (! is_array($part) || ($part['thought'] ?? false) === true) {
                    continue;
                }

                if (isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        $finishReason = data_get($data, 'candidates.0.finishReason');
        $finishReason = is_string($finishReason) ? $finishReason : null;

        if (trim($text) === '') {
            $blockReason = data_get($data, 'promptFeedback.blockReason');
            $reason = is_string($blockReason) ? $blockReason : ($finishReason ?? 'tidak diketahui');

            throw new AiRequestFailedException("Layanan AI tidak mengembalikan teks (alasan: {$reason}).");
        }

        return new AiResponse(
            text: $text,
            model: $this->model,
            inputTokens: (int) data_get($data, 'usageMetadata.promptTokenCount', 0),
            outputTokens: (int) data_get($data, 'usageMetadata.candidatesTokenCount', 0),
            thinkingTokens: (int) data_get($data, 'usageMetadata.thoughtsTokenCount', 0),
            latencyMs: $latencyMs,
            finishReason: $finishReason,
        );
    }
}
