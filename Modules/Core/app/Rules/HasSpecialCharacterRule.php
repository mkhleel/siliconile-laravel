<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HasSpecialCharacterRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/[@!#$%^&*()_+<>?:"{}|,.~`]/', $value)) {
            $fail('The :attribute must have at least one special character.');
        }

    }
}
