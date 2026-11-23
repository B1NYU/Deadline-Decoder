<?php

use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequestFailedException;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function geminiBody(string $text): array
{
    return [
        'candidates' => [[
            'content' => ['parts' => [
                ['text' => 'pikiran internal model', 'thought' => true],
                ['text' => $text],
            ]],
            'finishReason' => 'STOP',
        ]],
        'usageMetadata' => [
            'promptTokenCount' => 100,
            'candidatesTokenCount' => 40,
            'thoughtsTokenCount' => 12,
        ],
    ];
}

function askGemini(): void
{
    app(AiClient::class)->generateStructured('Sistem', 'Dokumen', ['type' => 'object']);
}

beforeEach(function () {
    Http::preventStrayRequests();

    config([
        'ai.default' => 'gemini',
        'ai.gemini.api_key' => 'kunci-rahasia-uji',
        'ai.gemini.model' => 'gemini-3.8-flash',
        'ai.gemini.thinking_level' => 'low',
        'ai.gemini.retry_backoff_ms' => [0, 0],
    ]);
});

test('it sends a structured request and returns the answer with usage', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiBody('{"title":"Uji"}'))]);

    $response = app(AiClient::class)->generateStructured('Sistem', 'Dokumen', ['type' => 'object']);

    expect($response->text)->toBe('{"title":"Uji"}')
        ->and($response->text)->not->toContain('pikiran internal model')
        ->and($response->model)->toBe('gemini-3.8-flash')
        ->and($response->inputTokens)->toBe(100)
        ->and($response->outputTokens)->toBe(40)
        ->and($response->thinkingTokens)->toBe(12)
        ->and($response->finishReason)->toBe('STOP');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'kunci-rahasia-uji')
        && ! str_contains($request->url(), 'kunci-rahasia-uji')
        && str_contains($request->url(), 'gemini-3.8-flash:generateContent')
        && $request['systemInstruction']['parts'][0]['text'] === 'Sistem'
        && $request['contents'][0]['parts'][0]['text'] === 'Dokumen'
        && $request['generationConfig']['responseFormat']['text']['schema'] === ['type' => 'object']
        && $request['generationConfig']['thinkingConfig']['thinkingLevel'] === 'low');
});

test('it lets the caller override the thinking level', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiBody('{}'))]);

    app(AiClient::class)->generateStructured('Sistem', 'Dokumen', ['type' => 'object'], 'high');

    Http::assertSent(fn (Request $request): bool => $request['generationConfig']['thinkingConfig']['thinkingLevel'] === 'high');
});

test('it retries a rate limited request and then succeeds', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push([], 429)
        ->push(geminiBody('{"ok":true}'))]);

    $response = app(AiClient::class)->generateStructured('Sistem', 'Dokumen', ['type' => 'object']);

    expect($response->text)->toBe('{"ok":true}');
    Http::assertSentCount(2);
});

test('it throws a retryable error when rate limiting persists', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota habis']], 429)]);

    expect(fn () => askGemini())->toThrow(function (AiRequestFailedException $exception) {
        expect($exception->retryable)->toBeTrue()
            ->and($exception->status)->toBe(429);
    });

    Http::assertSentCount(3);
});

test('it does not retry permanent client errors', function (int $status) {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'ditolak']], $status)]);

    expect(fn () => askGemini())->toThrow(function (AiRequestFailedException $exception) use ($status) {
        expect($exception->retryable)->toBeFalse()
            ->and($exception->status)->toBe($status);
    });

    Http::assertSentCount(1);
})->with([
    'bad request' => 400,
    'unauthorized' => 401,
    'forbidden' => 403,
    'not found' => 404,
]);

test('it reports a retryable failure when the connection fails', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::failedConnection()]);

    expect(fn () => askGemini())->toThrow(function (AiRequestFailedException $exception) {
        expect($exception->retryable)->toBeTrue();
    });
});

test('it throws when the model returns no text', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']])]);

    expect(fn () => askGemini())->toThrow(AiRequestFailedException::class, 'SAFETY');
});

test('it refuses to call the provider without an api key', function () {
    config(['ai.gemini.api_key' => null]);

    expect(fn () => askGemini())->toThrow(AiRequestFailedException::class, 'GEMINI_API_KEY');

    Http::assertNothingSent();
});

test('error messages never contain the api key', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'schema ditolak']], 400)]);

    try {
        askGemini();
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())->toContain('schema ditolak')
            ->and($exception->getMessage())->not->toContain('kunci-rahasia-uji');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

test('the container resolves the configured provider', function () {
    expect(app(AiClient::class))->toBeInstanceOf(GeminiClient::class);

    config(['ai.default' => 'tidak-ada']);

    expect(fn () => app(AiClient::class))->toThrow(InvalidArgumentException::class);
});
