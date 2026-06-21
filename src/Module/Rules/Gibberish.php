<?php

namespace RefinedDigital\FormBuilder\Module\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Server-authoritative gibberish (keyboard-mashing) heuristic. Mirrors the
 * front-end resources/js/front-end/gibberish.js. Applied to Text/Textarea fields
 * (unless the field opts out). Thresholds come from config('form-builder.gibberish').
 */
class Gibberish implements ValidationRule
{
    public function __construct(
        protected ?string $label = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        if ($this->looksLikeGibberish($value)) {
            $name = $this->label ?? 'This field';
            $fail("The {$name} looks like spam. Please enter a valid response.");
        }
    }

    public function looksLikeGibberish(string $value): bool
    {
        $cfg = config('form-builder.gibberish', []);
        $minLength = $cfg['min_length'] ?? 8;

        $v = trim($value);
        if (mb_strlen($v) < $minLength) {
            return false;
        }

        // multi-word: evaluate each long word
        if (preg_match('/\s/', $v) && count(preg_split('/\s+/', $v)) >= 2) {
            foreach (preg_split('/\s+/', $v) as $word) {
                if (mb_strlen($word) >= $minLength && $this->wordIsGibberish($word, $cfg)) {
                    return true;
                }
            }
            return false;
        }

        return $this->wordIsGibberish($v, $cfg);
    }

    protected function wordIsGibberish(string $word, array $cfg): bool
    {
        $minLength = $cfg['min_length'] ?? 8;
        $minVowelRatio = $cfg['min_vowel_ratio'] ?? 0.15;
        $maxConsonantRun = $cfg['max_consonant_run'] ?? 5;

        $letters = preg_replace('/[^a-z]/i', '', $word);
        if (strlen($letters) < $minLength) {
            return false;
        }

        $vowels = preg_match_all('/[aeiou]/i', $letters);
        if (($vowels / strlen($letters)) < $minVowelRatio) {
            return true;
        }

        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{'.($maxConsonantRun + 1).',}/i', $letters)) {
            return true;
        }

        // long run of a repeated char (aaaaaa)
        if (preg_match('/(.)\1{4,}/', $word)) {
            return true;
        }

        return false;
    }
}
