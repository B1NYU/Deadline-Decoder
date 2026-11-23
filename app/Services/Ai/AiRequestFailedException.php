<?php

namespace App\Services\Ai;

use RuntimeException;
use Throwable;

class AiRequestFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly ?int $status = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Structured context added to the log entry when the exception is reported.
     *
     * @return array{status: int|null, retryable: bool}
     */
    public function context(): array
    {
        return ['status' => $this->status, 'retryable' => $this->retryable];
    }
}
