<?php

namespace App\Services\Documents;

use RuntimeException;

/**
 * A document cannot be turned into text. The message is safe to show to the user.
 */
class DocumentExtractionException extends RuntimeException {}
