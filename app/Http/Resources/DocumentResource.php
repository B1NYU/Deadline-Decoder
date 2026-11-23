<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'error_message' => $this->error_message,
            'page_count' => $this->page_count,
            'chunk_count' => $this->whenCounted('chunks'),
            'char_count' => $this->char_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
