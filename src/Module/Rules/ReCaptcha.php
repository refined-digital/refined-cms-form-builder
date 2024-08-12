<?php

namespace RefinedDigital\FormBuilder\Module\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class ReCaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $value
        ];

        $response = Http::get('https://www.google.com/recaptcha/api/siteverify', $data);

        if (!($response->json()["success"] ?? false)) {
            $fail('The google recaptcha is required.');
        }
    }
}
