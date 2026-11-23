<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Authorize before validating so other users get a 404 instead of validation hints.
     */
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('project'));

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:150'],
            'text' => [
                'nullable', 'string', 'required_without:file', 'prohibits:file',
                'min:'.(int) config('documents.min_text_chars'),
                'max:'.(int) config('documents.max_text_chars'),
            ],
            'file' => [
                'nullable', 'required_without:text', 'file', 'mimes:pdf',
                'max:'.(int) config('documents.max_pdf_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Judul dokumen maksimal 150 karakter.',
            'text.required_without' => 'Tempel teks instruksi atau pilih berkas PDF.',
            'text.prohibits' => 'Pilih salah satu saja: teks atau berkas PDF.',
            'text.min' => 'Teks terlalu pendek. Minimal :min karakter.',
            'text.max' => 'Teks terlalu panjang. Maksimal :max karakter.',
            'file.required_without' => 'Tempel teks instruksi atau pilih berkas PDF.',
            'file.mimes' => 'Berkas harus berupa PDF.',
            'file.max' => 'Ukuran PDF maksimal :max KB.',
            'file.uploaded' => 'Unggahan gagal. Ukuran berkas mungkin melebihi batas server.',
        ];
    }
}
