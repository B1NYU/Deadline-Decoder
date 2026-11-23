<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $project_id
 * @property DocumentType $type
 * @property string $title
 * @property string|null $storage_path
 * @property string|null $mime
 * @property int|null $size_bytes
 * @property string $sha256
 * @property DocumentStatus $status
 * @property string|null $error_message
 * @property int|null $page_count
 * @property int|null $char_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type', 'title', 'storage_path', 'mime', 'size_bytes', 'sha256',
    'status', 'error_message', 'page_count', 'char_count',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * Remove the stored source file together with the record.
     */
    protected static function booted(): void
    {
        static::deleting(function (Document $document): void {
            if ($document->storage_path !== null) {
                static::disk()->delete($document->storage_path);
            }
        });
    }

    /**
     * The private disk that holds source files.
     */
    public static function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('documents.disk'));

        return $disk;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<DocumentChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
