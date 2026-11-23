<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    private const CHUNK_DISPLAY_LIMIT = 300;

    /**
     * Store a pasted text or an uploaded PDF and queue it for processing.
     */
    public function store(StoreDocumentRequest $request, Project $project): RedirectResponse
    {
        $file = $request->file('file');

        $attributes = $file instanceof UploadedFile
            ? $this->pdfAttributes($request, $project, $file)
            : $this->textAttributes($request, $project);

        $document = $project->documents()->create($attributes + ['status' => DocumentStatus::Pending]);

        ProcessDocumentJob::dispatch($document->id)->afterCommit();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dokumen diterima dan sedang diproses.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show the extracted text of a document so the user can check it against the original.
     */
    public function show(Project $project, Document $document): Response
    {
        Gate::authorize('view', $document);

        return Inertia::render('documents/show', [
            'project' => ['id' => $project->id, 'title' => $project->title],
            'document' => (new DocumentResource($document->loadCount('chunks')))->resolve(),
            'chunks' => $document->chunks()
                ->orderBy('chunk_index')
                ->limit(self::CHUNK_DISPLAY_LIMIT)
                ->get()
                ->map(fn (DocumentChunk $chunk): array => [
                    'id' => $chunk->id,
                    'chunk_index' => $chunk->chunk_index,
                    'page' => $chunk->page,
                    'section' => $chunk->section,
                    'content' => $chunk->content,
                ])
                ->all(),
        ]);
    }

    /**
     * Delete a document and its stored file.
     */
    public function destroy(Project $project, Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dokumen dihapus.')]);

        return to_route('projects.show', $project);
    }

    /**
     * @return array<string, mixed>
     */
    private function pdfAttributes(StoreDocumentRequest $request, Project $project, UploadedFile $file): array
    {
        $hash = (string) hash_file('sha256', (string) $file->getRealPath());
        $this->ensureNotDuplicate($project, $hash, 'file');

        $title = $request->string('title')->trim()->toString();

        if ($title === '') {
            $title = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 150, '');
        }

        $path = $file->storeAs(
            "documents/{$project->id}",
            Str::uuid()->toString().'.pdf',
            ['disk' => config('documents.disk')],
        );

        abort_if($path === false, 500, 'Berkas tidak dapat disimpan.');

        return [
            'type' => DocumentType::Pdf,
            'title' => $title !== '' ? $title : 'Dokumen PDF',
            'storage_path' => $path,
            'mime' => 'application/pdf',
            'size_bytes' => (int) $file->getSize(),
            'sha256' => $hash,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function textAttributes(StoreDocumentRequest $request, Project $project): array
    {
        $text = $request->string('text')->trim()->toString();
        $hash = hash('sha256', $text);
        $this->ensureNotDuplicate($project, $hash, 'text');

        $title = $request->string('title')->trim()->toString();
        $path = "documents/{$project->id}/".Str::uuid()->toString().'.txt';

        Document::disk()->put($path, $text);

        return [
            'type' => DocumentType::Text,
            'title' => $title !== '' ? $title : 'Teks instruksi',
            'storage_path' => $path,
            'mime' => 'text/plain',
            'size_bytes' => strlen($text),
            'sha256' => $hash,
        ];
    }

    private function ensureNotDuplicate(Project $project, string $hash, string $field): void
    {
        if ($project->documents()->where('sha256', $hash)->exists()) {
            throw ValidationException::withMessages([$field => 'Dokumen yang sama sudah ada di proyek ini.']);
        }
    }
}
