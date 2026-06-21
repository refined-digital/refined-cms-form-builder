<?php

namespace RefinedDigital\FormBuilder\Module\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * reCAPTCHA v3 (invisible, score-based). Verifies success AND score >= threshold
 * AND the expected action. Threshold is config('form-builder.recaptcha_threshold').
 */
class ReCaptcha implements ValidationRule
{
    public function __construct(
        protected string $action = 'submit'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => env('RECAPTCHA_SECRET_KEY'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $body = $response->json();

        if (!($body['success'] ?? false)) {
            $fail('Robot detected. Please try again.');
            return;
        }

        $threshold = (float) config('form-builder.recaptcha_threshold', 0.5);
        if (isset($body['score']) && (float) $body['score'] < $threshold) {
            $fail('Your submission looks automated. Please try again.');
            return;
        }

        // v3 returns the action it was issued for; reject a mismatch
        if (isset($body['action']) && $this->action && $body['action'] !== $this->action) {
            $fail('Robot detected. Please try again.');
        }
    }
}
