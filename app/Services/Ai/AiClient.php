<?php

namespace App\Services\Ai;

interface AiClient
{
    /**
     * Ask the model for a JSON answer that follows the given schema.
     *
     * @param  array<string, mixed>  $schema  JSON Schema the answer must follow
     *
     * @throws AiRequestFailedException
     */
    public function generateStructured(
        string $systemPrompt,
        string $userContent,
        array $schema,
        ?string $thinkingLevel = null,
    ): AiResponse;
}
