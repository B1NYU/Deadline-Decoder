<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Services\Documents\DocumentExtractionException;
use App\Services\Documents\PdfTextExtractor;
use App\Services\Documents\TextChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Keep the timeout below the queue's retry_after (90 seconds by default).
     */
    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(public int $documentId) {}

    public function handle(): void
    {
        $document = Document::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        $document->update(['status' => DocumentStatus::Processing, 'error_message' => null]);

        try {
            $chunks = (new TextChunker((int) config('documents.chunk_max_chars')))
                ->chunk($this->segmentsFor($document));

            if ($chunks === []) {
                throw new DocumentExtractionException('Dokumen tidak berisi teks yang dapat dibaca.');
            }
        } catch (DocumentExtractionException $exception) {
            $document->update([
                'status' => DocumentStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            return;
        }

        DB::transaction(function () use ($document, $chunks): void {
            $document->chunks()->delete();
            $document->chunks()->createMany($chunks);

            $document->update([
                'status' => DocumentStatus::Completed,
                'error_message' => null,
                'page_count' => $document->type === DocumentType::Pdf
                    ? collect($chunks)->pluck('page')->filter()->unique()->count()
                    : null,
                'char_count' => collect($chunks)->sum(fn (array $chunk): int => mb_strlen($chunk['content'])),
            ]);
        });
    }

    /**
     * Called once all attempts are used up after an unexpected error.
     */
    public function failed(?Throwable $exception): void
    {
        Document::query()->whereKey($this->documentId)->update([
            'status' => DocumentStatus::Failed->value,
            'error_message' => 'Pemrosesan dokumen gagal secara tak terduga. Coba unggah ulang.',
        ]);
    }

    /**
     * @return list<array{page: int|null, text: string}>
     *
     * @throws DocumentExtractionException
     */
    private function segmentsFor(Document $document): array
    {
        $disk = Document::disk();

        if ($document->storage_path === null || ! $disk->exists($document->storage_path)) {
            throw new DocumentExtractionException('Berkas sumber tidak ditemukan. Unggah ulang dokumen ini.');
        }

        if ($document->type === DocumentType::Pdf) {
            return (new PdfTextExtractor((int) config('documents.max_pdf_pages')))
                ->extract($disk->path($document->storage_path));
        }

        return [['page' => null, 'text' => (string) $disk->get($document->storage_path)]];
    }
}
