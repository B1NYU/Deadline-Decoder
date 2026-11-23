<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => DocumentType::Text,
            'title' => fake()->sentence(3),
            'storage_path' => null,
            'mime' => 'text/plain',
            'size_bytes' => 100,
            'sha256' => hash('sha256', fake()->unique()->uuid()),
            'status' => DocumentStatus::Pending,
        ];
    }

    /**
     * Indicate that the document is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Pdf,
            'mime' => 'application/pdf',
        ]);
    }
}
