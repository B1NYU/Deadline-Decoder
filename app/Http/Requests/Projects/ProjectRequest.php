<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    /**
     * Ownership is enforced by ProjectPolicy in the controller; route middleware
     * already guarantees an authenticated, verified user.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul proyek wajib diisi.',
            'title.max' => 'Judul proyek maksimal 150 karakter.',
            'description.max' => 'Deskripsi maksimal 2000 karakter.',
        ];
    }
}
