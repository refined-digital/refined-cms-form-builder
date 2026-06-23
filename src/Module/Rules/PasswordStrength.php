<?php

namespace RefinedDigital\FormBuilder\Module\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RefinedDigital\FormBuilder\Module\Support\PasswordRules;

/**
 * Server-authoritative strong-password check. Validates against the enabled
 * rules in config('form-builder.password') via the shared PasswordRules helper,
 * so it never drifts from the front-end checklist. Applied to password fields
 * that have opted into 'strong password' (replacing the default min rule).
 */
class PasswordStrength implements ValidationRule
{
    public function __construct(
        protected ?string $label = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return; // 'required' handles empties
        }

        $failure = PasswordRules::firstFailure($value);
        if ($failure !== null) {
            $name = $this->label ?? 'password';
            $fail("The {$name} is not strong enough — {$failure}.");
        }
    }
}
