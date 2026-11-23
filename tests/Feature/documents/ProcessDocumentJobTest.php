<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function storedTestDocument(DocumentType $type, string $content): Document
{
    $path = 'documents/test/'.Str::uuid().($type === DocumentType::Pdf ? '.pdf' : '.txt');
    Storage::disk('local')->put($path, $content);

    return Document::factory()->create(['type' => $type, 'storage_path' => $path]);
}

beforeEach(function () {
    Storage::fake('local');
});

test('it turns a text document into numbered chunks and marks it completed', function () {
    $document = storedTestDocument(DocumentType::Text, "1. Ketentuan\nRancang ERD.\n\n2. Pengumpulan\nKumpulkan PDF.");

    ProcessDocumentJob::dispatchSync($document->id);

    $document->refresh();

    expect($document->status)->toBe(DocumentStatus::Completed)
        ->and($document->error_message)->toBeNull()
        ->and($document->page_count)->toBeNull()
        ->and($document->char_count)->toBe($document->chunks()->get()->sum(fn ($chunk) => mb_strlen($chunk->content)))
        ->and($document->chunks()->orderBy('chunk_index')->pluck('chunk_index')->all())->toBe([0]);
});

test('it records page numbers for a pdf', function () {
    $document = storedTestDocument(DocumentType::Pdf, fakePdf(['Halaman satu.', 'Halaman dua.']));

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Completed)
        ->and($document->page_count)->toBe(2)
        ->and($document->chunks()->orderBy('chunk_index')->pluck('page')->all())->toBe([1, 2]);
});

test('it fails a pdf without extractable text and mentions that ocr is unavailable', function () {
    $document = storedTestDocument(DocumentType::Pdf, fakePdf(['']));

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toContain('OCR')
        ->and($document->chunks()->count())->toBe(0);
});

test('it fails a corrupted pdf with a readable message', function () {
    $document = storedTestDocument(DocumentType::Pdf, 'bukan pdf sama sekali');

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toContain('tidak dapat dibaca');
});

test('it fails a pdf with more pages than allowed', function () {
    config(['documents.max_pdf_pages' => 1]);
    $document = storedTestDocument(DocumentType::Pdf, fakePdf(['Satu.', 'Dua.']));

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toContain('1 halaman');
});

test('it fails when the source file is missing', function () {
    $document = Document::factory()->create(['storage_path' => 'documents/tidak/ada.txt']);

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toContain('Berkas sumber tidak ditemukan');
});

test('it fails a text document that contains no readable text', function () {
    $document = storedTestDocument(DocumentType::Text, "\n\n   \n");

    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toBe('Dokumen tidak berisi teks yang dapat dibaca.');
});

test('processing the same document twice does not duplicate chunks', function () {
    $document = storedTestDocument(DocumentType::Text, 'Rancang ERD dengan minimal 8 entitas.');

    ProcessDocumentJob::dispatchSync($document->id);
    $firstCount = $document->chunks()->count();
    ProcessDocumentJob::dispatchSync($document->id);

    expect($document->chunks()->count())->toBe($firstCount);
});

test('it ignores a document that was deleted before the job ran', function () {
    expect(fn () => ProcessDocumentJob::dispatchSync(999999))->not->toThrow(Throwable::class);
});

test('an unexpected failure is recorded without leaking internal details', function () {
    $document = Document::factory()->create();

    (new ProcessDocumentJob($document->id))->failed(new RuntimeException('rahasia internal'));

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->not->toContain('rahasia internal');
});
