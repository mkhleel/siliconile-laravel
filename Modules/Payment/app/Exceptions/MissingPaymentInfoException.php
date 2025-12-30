<?php

declare(strict_types=1);

namespace Modules\Payment\Exceptions;

use Exception;

class MissingPaymentInfoException extends Exception
{
    public function __construct(string $missingPaymentParameter, string $paymentProvider)
    {
        parent::__construct($missingPaymentParameter . ' is required to use ' . $paymentProvider);
    }
}
