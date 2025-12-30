<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //        if (! preg_match('/^(?:\+?20|01)?(?:\d{11}|\d{13})$/', $value)) {
        //            $fail('The :attribute must be a valid egypt phone number.');
        //        }

        if (! preg_match('/^(\+?\d{0,4})?\d{7,10}$/', $value)) {
            $fail('The :attribute is not a valid phone number.');
        }
    }
}
