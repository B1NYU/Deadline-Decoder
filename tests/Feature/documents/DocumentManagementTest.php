<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function instructionText(): string
{
    return 'Tugas besar: rancang ERD dengan minimal 8 entitas dan kumpulkan laporan PDF.';
}

beforeEach(function () {
    Storage::fake('local');
});

test('guests cannot add documents', function () {
    $project = Project::factory()->create();

    $this->post(route('projects.documents.store', $project), ['text' => instructionText()])
        ->assertRedirect(route('login'));

    expect(Document::query()->count())->toBe(0);
});

test('pasted text is stored privately and queued for processing', function () {
    Queue::fake();
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['text' => instructionText()])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.show', $project));

    $document = Document::query()->firstOrFail();

    expect($document->type)->toBe(DocumentType::Text)
        ->and($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->title)->toBe('Teks instruksi')
        ->and($document->sha256)->toBe(hash('sha256', instructionText()))
        ->and($document->storage_path)->toStartWith("documents/{$project->id}/");

    Storage::disk('local')->assertExists($document->storage_path);
    Queue::assertPushed(ProcessDocumentJob::class, fn (ProcessDocumentJob $job) => $job->documentId === $document->id);
});

test('pasted text is processed into chunks', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['text' => instructionText()]);

    $document = Document::query()->firstOrFail();

    expect($document->status)->toBe(DocumentStatus::Completed)
        ->and($document->chunks()->count())->toBe(1)
        ->and($document->chunks()->firstOrFail()->content)->toBe(instructionText());
});

test('an uploaded pdf is processed into chunks with page numbers', function () {
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->createWithContent(
        'instruksi.pdf',
        fakePdf(['Halaman satu tentang ERD.', 'Halaman dua tentang normalisasi.']),
    );

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['file' => $file])
        ->assertSessionHasNoErrors();

    $document = Document::query()->firstOrFail();

    expect($document->type)->toBe(DocumentType::Pdf)
        ->and($document->status)->toBe(DocumentStatus::Completed)
        ->and($document->title)->toBe('instruksi')
        ->and($document->page_count)->toBe(2)
        ->and($document->storage_path)->toEndWith('.pdf')
        ->and($document->storage_path)->not->toContain('instruksi')
        ->and($document->chunks()->orderBy('chunk_index')->pluck('page')->all())->toBe([1, 2]);
});

test('input that is missing or out of bounds is rejected', function (array $payload, string $field) {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), $payload)
        ->assertSessionHasErrors($field);

    expect(Document::query()->count())->toBe(0);
})->with([
    'empty payload' => [[], 'text'],
    'text too short' => [['text' => 'pendek'], 'text'],
    'text too long' => [['text' => str_repeat('a', 30001)], 'text'],
]);

test('an empty submission explains what to provide', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), [])
        ->assertSessionHasErrors([
            'text' => 'Tempel teks instruksi atau pilih berkas PDF.',
            'file' => 'Tempel teks instruksi atau pilih berkas PDF.',
        ]);
});

test('text and a file cannot be submitted together', function () {
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->createWithContent('instruksi.pdf', fakePdf(['Halo dunia.']));

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['text' => instructionText(), 'file' => $file])
        ->assertSessionHasErrors(['text' => 'Pilih salah satu saja: teks atau berkas PDF.']);

    expect(Document::query()->count())->toBe(0);
});

test('a file that is not a real pdf is rejected even if it is named pdf', function () {
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->createWithContent('palsu.pdf', 'ini bukan pdf');

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['file' => $file])
        ->assertSessionHasErrors(['file' => 'Berkas harus berupa PDF.']);

    expect(Document::query()->count())->toBe(0);
});

test('a pdf above the size limit is rejected', function () {
    config(['documents.max_pdf_kb' => 1]);
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->createWithContent('besar.pdf', fakePdf([str_repeat('abcd ', 600)]));

    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['file' => $file])
        ->assertSessionHasErrors(['file' => 'Ukuran PDF maksimal 1 KB.']);

    expect(Document::query()->count())->toBe(0);
});

test('the same document cannot be added twice to a project', function () {
    Queue::fake();
    $project = Project::factory()->create();

    $this->actingAs($project->user)->post(route('projects.documents.store', $project), ['text' => instructionText()]);
    $this->actingAs($project->user)
        ->post(route('projects.documents.store', $project), ['text' => instructionText()])
        ->assertSessionHasErrors(['text' => 'Dokumen yang sama sudah ada di proyek ini.']);

    expect($project->documents()->count())->toBe(1);
});

test('another user gets a 404 before any validation when adding a document', function (array $payload) {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('projects.documents.store', $project), $payload)
        ->assertNotFound();

    expect(Document::query()->count())->toBe(0);
})->with([
    'valid payload' => [['text' => instructionText()]],
    'invalid payload' => [[]],
]);

test('another user cannot view or delete a document', function () {
    $document = Document::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('projects.documents.show', [$document->project, $document]))
        ->assertNotFound();

    $this->actingAs($other)
        ->delete(route('projects.documents.destroy', [$document->project, $document]))
        ->assertNotFound();

    expect($document->fresh())->not->toBeNull();
});

test('a document cannot be reached through a different project', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for(Project::factory()->for($user))->create();
    $otherProject = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('projects.documents.show', [$otherProject, $document]))
        ->assertNotFound();
});

test('the owner can read the extracted text of a document', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Completed]);
    DocumentChunk::factory()->for($document)->create(['chunk_index' => 0, 'page' => 3, 'content' => 'Isi potongan.']);

    $this->actingAs($document->project->user)
        ->get(route('projects.documents.show', [$document->project, $document]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id)
            ->has('chunks', 1)
            ->where('chunks.0.page', 3)
            ->where('chunks.0.content', 'Isi potongan.'));
});

test('deleting a document removes its record, chunks, and stored file', function () {
    Queue::fake();
    $project = Project::factory()->create();

    $this->actingAs($project->user)->post(route('projects.documents.store', $project), ['text' => instructionText()]);
    $document = Document::query()->firstOrFail();
    DocumentChunk::factory()->for($document)->create();
    $path = $document->storage_path;

    $this->actingAs($project->user)
        ->delete(route('projects.documents.destroy', [$project, $document]))
        ->assertRedirect(route('projects.show', $project));

    expect($document->fresh())->toBeNull()
        ->and(DocumentChunk::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

test('deleting a project or a user removes the stored files', function () {
    Queue::fake();
    $user = User::factory()->create();
    $first = Project::factory()->for($user)->create();
    $second = Project::factory()->for($user)->create();

    foreach ([$first, $second] as $index => $project) {
        $this->actingAs($user)->post(route('projects.documents.store', $project), ['text' => instructionText().' '.$index]);
    }

    $firstPath = $first->documents()->firstOrFail()->storage_path;
    $secondPath = $second->documents()->firstOrFail()->storage_path;

    $first->delete();
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($secondPath);

    $user->delete();
    Storage::disk('local')->assertMissing($secondPath);
});

test('the project page lists only its own documents and the upload limits', function () {
    Queue::fake();
    $project = Project::factory()->create();
    Document::factory()->for($project)->create(['title' => 'Milik proyek ini']);
    Document::factory()->create(['title' => 'Milik proyek lain']);

    $this->actingAs($project->user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->has('documents', 1)
            ->where('documents.0.title', 'Milik proyek ini')
            ->where('documents.0.status', 'pending')
            ->has('limits'));
});
