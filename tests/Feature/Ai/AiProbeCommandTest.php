<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

function probeLogPath(): string
{
    return sys_get_temp_dir().DIRECTORY_SEPARATOR.'deadline-decoder-ai-probe-test.jsonl';
}

/**
 * @return array<string, mixed>
 */
function probeLastRecord(): array
{
    $lines = array_filter(explode(PHP_EOL, (string) file_get_contents(probeLogPath())));

    return json_decode((string) end($lines), true);
}

beforeEach(function () {
    Http::preventStrayRequests();
    File::delete(probeLogPath());

    config([
        'ai.default' => 'gemini',
        'ai.gemini.api_key' => 'kunci-rahasia-uji',
        'ai.gemini.retry_backoff_ms' => [0, 0],
        'ai.experiment_log' => probeLogPath(),
    ]);
});

afterEach(function () {
    File::delete(probeLogPath());
});

test('a successful probe verifies quotes against the document and logs the experiment', function () {
    $answer = [
        'title' => 'Tugas Besar Basis Data',
        'summary' => 'Merancang dan membangun basis data.',
        'requirements' => [
            ['description' => 'ERD minimal 8 entitas', 'evidence_quote' => 'Rancang ERD dengan minimal 8 entitas'],
            ['description' => 'Wajib Laravel', 'evidence_quote' => 'Wajib memakai framework Laravel'],
        ],
        'deadlines' => [
            ['label' => 'Laporan akhir', 'date_text' => 'Jumat, 11 Desember 2026 pukul 23.59 WIB', 'iso_date' => '2026-12-11', 'is_ambiguous' => false],
        ],
        'uncertainties' => ['Durasi demo tidak disebutkan.'],
    ];

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => json_encode($answer)]]], 'finishReason' => 'STOP']],
        'usageMetadata' => ['promptTokenCount' => 500, 'candidatesTokenCount' => 200, 'thoughtsTokenCount' => 30],
    ])]);

    $this->artisan('ai:probe')
        ->expectsOutputToContain('Kutipan terverifikasi: 1 dari 2')
        ->assertSuccessful();

    expect(probeLastRecord())
        ->toMatchArray([
            'prompt_version' => 'probe_v0',
            'json_valid' => true,
            'quotes_total' => 2,
            'quotes_verified' => 1,
            'input_tokens' => 500,
        ])
        ->not->toHaveKey('api_key');
});

test('a failed provider call is reported and logged with its status', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);

    $this->artisan('ai:probe')->assertFailed();

    expect(probeLastRecord())->toMatchArray(['json_valid' => false, 'error_status' => 429]);
});

test('an answer that is not the expected json shape fails the probe', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => '{"title":"saja"}']]]]],
    ])]);

    $this->artisan('ai:probe')->assertFailed();

    expect(probeLastRecord()['json_valid'])->toBeFalse();
});

test('a missing document is rejected before any request is sent', function () {
    $this->artisan('ai:probe', ['--file' => 'tidak-ada.txt'])->assertFailed();

    Http::assertNothingSent();
});

test('an unsupported thinking level is rejected before any request is sent', function () {
    $this->artisan('ai:probe', ['--thinking' => 'minimal'])->assertFailed();

    Http::assertNothingSent();
});
