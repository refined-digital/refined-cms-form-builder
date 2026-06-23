<?php

namespace RefinedDigital\FormBuilder\Module\Support;

/**
 * Single source of truth for the strong-password rules. Turns
 * config('form-builder.password') into a flat list of enabled rules that the
 * server validator, the blade checklist, and the front-end all consume, so the
 * three never drift. Each rule is:
 *   ['key' => string, 'label' => string, 'type' => 'length'|'regex',
 *    'pattern' => ?string, 'min' => ?int, 'max' => ?int]
 */
class PasswordRules
{
    /** The enabled rules, normalised, in display order. */
    public static function active(): array
    {
        $cfg = config('form-builder.password', []);
        $rules = [];

        $minLen = $cfg['min_length'] ?? null;
        if (($minLen['enabled'] ?? false)) {
            $value = (int) ($minLen['value'] ?? 8);
            $rules[] = [
                'key'   => 'min_length',
                'type'  => 'length',
                'min'   => $value,
                'label' => self::label($minLen['label'] ?? 'At least :value characters', $value),
            ];
        }

        $maxLen = $cfg['max_length'] ?? null;
        if (($maxLen['enabled'] ?? false)) {
            $value = (int) ($maxLen['value'] ?? 64);
            $rules[] = [
                'key'   => 'max_length',
                'type'  => 'length',
                'max'   => $value,
                'label' => self::label($maxLen['label'] ?? 'No more than :value characters', $value),
            ];
        }

        foreach (['uppercase', 'lowercase', 'number', 'special'] as $key) {
            $rule = $cfg[$key] ?? null;
            if (($rule['enabled'] ?? false) && !empty($rule['pattern'])) {
                $rules[] = [
                    'key'     => $key,
                    'type'    => 'regex',
                    'pattern' => $rule['pattern'],
                    'label'   => $rule['label'] ?? ucfirst($key),
                ];
            }
        }

        $noSpaces = $cfg['no_spaces'] ?? null;
        if (($noSpaces['enabled'] ?? false)) {
            $rules[] = [
                'key'     => 'no_spaces',
                'type'    => 'regex',
                'pattern' => '^\S*$',
                'label'   => $noSpaces['label'] ?? 'No spaces',
            ];
        }

        foreach (($cfg['custom'] ?? []) as $i => $rule) {
            if (($rule['enabled'] ?? false) && !empty($rule['pattern'])) {
                $rules[] = [
                    'key'     => 'custom_'.$i,
                    'type'    => 'regex',
                    'pattern' => $rule['pattern'],
                    'label'   => $rule['label'] ?? 'Meets the requirement',
                ];
            }
        }

        return $rules;
    }

    /** Test a value against one normalised rule. */
    public static function passes(array $rule, string $value): bool
    {
        if ($rule['type'] === 'length') {
            $len = mb_strlen($value);
            if (isset($rule['min']) && $len < $rule['min']) {
                return false;
            }
            if (isset($rule['max']) && $len > $rule['max']) {
                return false;
            }
            return true;
        }

        // regex rule — the config patterns are JS-flavour, valid as PCRE too
        return (bool) preg_match('/'.$rule['pattern'].'/', $value);
    }

    /** The first failing rule's label for a value, or null if all pass. */
    public static function firstFailure(string $value): ?string
    {
        foreach (self::active() as $rule) {
            if (!self::passes($rule, $value)) {
                return $rule['label'];
            }
        }
        return null;
    }

    /** Whether any strong-password rule is configured/enabled at all. */
    public static function enabled(): bool
    {
        return count(self::active()) > 0;
    }

    protected static function label(string $template, $value): string
    {
        return str_replace(':value', (string) $value, $template);
    }
}
