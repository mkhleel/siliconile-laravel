<?php

declare(strict_types=1);

namespace Modules\Network\Exceptions;

use Exception;

/**
 * Exception thrown when connection to Mikrotik router fails.
 */
class MikrotikConnectionException extends Exception
{
    public function __construct(
        string $message = 'Failed to connect to Mikrotik router',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
