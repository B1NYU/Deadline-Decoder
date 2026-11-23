<?php

namespace App\Console\Commands;

use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequestFailedException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiProbeCommand extends Command
{
    private const PROMPT_VERSION = 'probe_v0';

    private const MAX_DOCUMENT_CHARS = 20000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:probe
        {--file=tests/Fixtures/documents/synthetic-basis-data.txt : Path to a synthetic instruction text file}
        {--thinking= : Thinking level override (low, medium, high)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send one synthetic instruction to the configured AI provider and log the experiment';

    /**
     * Execute the console command.
     */
    public function handle(AiClient $client): int
    {
        $thinking = $this->option('thinking');
        $thinking = is_string($thinking) && $thinking !== '' ? $thinking : null;

        if ($thinking !== null && ! in_array($thinking, ['low', 'medium', 'high'], true)) {
            $this->error('Level thinking harus low, medium, atau high ("minimal" tidak didukung model ini).');

            return self::FAILURE;
        }

        $file = $this->option('file');
        $path = $this->resolvePath(is_string($file) ? $file : '');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Berkas tidak ditemukan atau tidak dapat dibaca: {$path}");

            return self::FAILURE;
        }

        $document = (string) file_get_contents($path);

        if (trim($document) === '' || mb_strlen($document) > self::MAX_DOCUMENT_CHARS) {
            $this->error('Dokumen harus berisi teks dan maksimal '.self::MAX_DOCUMENT_CHARS.' karakter agar kuota tidak terbuang.');

            return self::FAILURE;
        }

        $promptPath = resource_path('prompts/'.self::PROMPT_VERSION.'/system.md');

        if (! is_file($promptPath)) {
            $this->error("System prompt tidak ditemukan: {$promptPath}");

            return self::FAILURE;
        }

        $record = [
            'at' => now()->toIso8601String(),
            'prompt_version' => self::PROMPT_VERSION,
            'model' => (string) config('ai.gemini.model'),
            'thinking_level' => $thinking ?? (string) config('ai.gemini.thinking_level'),
            'document_sha256' => substr(hash('sha256', $document), 0, 12),
            'document_chars' => mb_strlen($document),
        ];

        $this->line("Mengirim dokumen ke {$record['model']} (thinking: {$record['thinking_level']}). Bisa sampai 3 menit jika ada pengulangan, jangan ditutup...");

        try {
            $response = $client->generateStructured(
                (string) file_get_contents($promptPath),
                "<document>\n{$document}\n</document>",
                $this->schema(),
                $thinking,
            );
        } catch (AiRequestFailedException $exception) {
            $this->error('Panggilan AI gagal: '.$exception->getMessage());

            $this->logExperiment($record + [
                'json_valid' => false,
                'error_status' => $exception->status,
                'error' => Str::limit($exception->getMessage(), 300),
            ]);

            return self::FAILURE;
        }

        $decoded = json_decode($response->text, true);
        $jsonValid = is_array($decoded)
            && is_string($decoded['title'] ?? null)
            && is_string($decoded['summary'] ?? null)
            && is_array($decoded['requirements'] ?? null)
            && is_array($decoded['deadlines'] ?? null)
            && is_array($decoded['uncertainties'] ?? null);

        $quotesTotal = 0;
        $quotesVerified = 0;

        if ($jsonValid) {
            $normalizedDocument = $this->normalize($document);

            foreach ($decoded['requirements'] as $requirement) {
                $quote = is_array($requirement) ? ($requirement['evidence_quote'] ?? null) : null;

                if (! is_string($quote) || trim($quote) === '') {
                    continue;
                }

                $quotesTotal++;

                if (str_contains($normalizedDocument, $this->normalize($quote))) {
                    $quotesVerified++;
                }
            }
        }

        $this->logExperiment($record + [
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'thinking_tokens' => $response->thinkingTokens,
            'latency_ms' => $response->latencyMs,
            'finish_reason' => $response->finishReason,
            'json_valid' => $jsonValid,
            'quotes_total' => $quotesTotal,
            'quotes_verified' => $quotesVerified,
            'deadlines' => $jsonValid ? $decoded['deadlines'] : null,
        ]);

        $this->info("Model: {$response->model} | thinking: {$record['thinking_level']} | prompt: ".self::PROMPT_VERSION);
        $this->line("Token masuk {$response->inputTokens}, keluar {$response->outputTokens}, thinking {$response->thinkingTokens} | latensi {$response->latencyMs} ms | finish: ".($response->finishReason ?? '-'));

        if (! $jsonValid) {
            $this->error('Jawaban bukan JSON dengan bentuk yang diharapkan. Teks mentah:');
            $this->line(Str::limit($response->text, 1500));

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Judul: '.$decoded['title']);
        $this->line('Ringkasan: '.$decoded['summary']);

        $this->newLine();
        $this->line('Persyaratan:');

        foreach ($decoded['requirements'] as $requirement) {
            if (is_array($requirement)) {
                $this->line('  - '.($requirement['description'] ?? '?'));
            }
        }

        $this->newLine();
        $this->line('Tenggat:');

        foreach ($decoded['deadlines'] as $deadline) {
            if (is_array($deadline)) {
                $this->line(sprintf(
                    '  - %s | teks: %s | iso: %s | ambigu: %s',
                    $deadline['label'] ?? '?',
                    $deadline['date_text'] ?? '?',
                    $deadline['iso_date'] ?? 'null',
                    ($deadline['is_ambiguous'] ?? false) ? 'ya' : 'tidak',
                ));
            }
        }

        $this->newLine();
        $this->line('Perlu dikonfirmasi:');

        foreach ($decoded['uncertainties'] as $uncertainty) {
            if (is_string($uncertainty)) {
                $this->line('  - '.$uncertainty);
            }
        }

        $this->newLine();
        $this->info("Kutipan terverifikasi: {$quotesVerified} dari {$quotesTotal}");

        return self::SUCCESS;
    }

    private function resolvePath(string $file): string
    {
        $isAbsolute = str_starts_with($file, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1;

        return $isAbsolute ? $file : base_path($file);
    }

    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function logExperiment(array $record): void
    {
        $path = (string) config('ai.experiment_log');

        File::ensureDirectoryExists(dirname($path));
        File::append($path, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /**
     * Deliberately small: Google rejects schemas with too many states or branches.
     *
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Nama tugas atau proyek.'],
                'summary' => ['type' => 'string', 'description' => 'Ringkasan instruksi dalam 1-3 kalimat.'],
                'requirements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'description' => ['type' => 'string', 'description' => 'Persyaratan atau keluaran yang harus dipenuhi.'],
                            'evidence_quote' => ['type' => 'string', 'description' => 'Kutipan persis dari dokumen yang mendukung persyaratan ini.'],
                        ],
                        'required' => ['description', 'evidence_quote'],
                    ],
                ],
                'deadlines' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string', 'description' => 'Apa yang harus selesai atau dikumpulkan.'],
                            'date_text' => ['type' => 'string', 'description' => 'Teks tanggal atau waktu persis seperti di dokumen.'],
                            'iso_date' => ['type' => ['string', 'null'], 'description' => 'YYYY-MM-DD hanya jika tanggal lengkap dan tidak ambigu, selain itu null.'],
                            'is_ambiguous' => ['type' => 'boolean', 'description' => 'True jika tanggal tidak lengkap, bukan tanggal kalender, atau ambigu.'],
                        ],
                        'required' => ['label', 'date_text', 'iso_date', 'is_ambiguous'],
                    ],
                ],
                'uncertainties' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Hal penting yang tidak ada di dokumen atau perlu dikonfirmasi.',
                ],
            ],
            'required' => ['title', 'summary', 'requirements', 'deadlines', 'uncertainties'],
        ];
    }
}
