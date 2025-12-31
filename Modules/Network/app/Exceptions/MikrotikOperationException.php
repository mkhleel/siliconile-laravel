<?php

declare(strict_types=1);

namespace Modules\Network\Exceptions;

use Exception;

/**
 * Exception thrown when a Mikrotik API operation fails.
 */
class MikrotikOperationException extends Exception
{
    public function __construct(
        string $message = 'Mikrotik operation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
