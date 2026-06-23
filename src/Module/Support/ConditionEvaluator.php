<?php

namespace RefinedDigital\FormBuilder\Module\Support;

/**
 * Server-side mirror of resources/js/front-end/conditions.js. Decides whether a
 * field is effectively hidden by its conditional-logic rules given the submitted
 * data, so hidden-but-required fields don't block submission.
 */
class ConditionEvaluator
{
    /**
     * Returns true when the field should be treated as hidden (skip validation).
     *
     * @param object $field    the FormField (reads visibility_rules)
     * @param array  $data     submitted request data (keyed by field<id>)
     */
    public static function isHidden(mixed $field, array $data): bool
    {
        $rules = $field->visibility_rules ?? null;
        if (is_string($rules)) {
            $rules = json_decode($rules, true);
        }
        if (!is_array($rules) || empty($rules['rules'])) {
            return false;
        }

        $matched = self::evalGroup($rules, $data);
        $action = $rules['action'] ?? 'show';

        // 'show': hidden when NOT matched. 'hide': hidden when matched.
        // enable/disable never hide the field (it stays in the DOM).
        return match ($action) {
            'show' => !$matched,
            'hide' => $matched,
            default => false,
        };
    }

    protected static function evalGroup(array $config, array $data): bool
    {
        $rules = array_filter($config['rules'] ?? [], fn ($r) => isset($r['field']));
        if (!$rules) {
            return true;
        }

        $results = array_map(fn ($r) => self::evalRule($r, $data), $rules);

        return ($config['logic'] ?? 'and') === 'or'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    protected static function evalRule(array $rule, array $data): bool
    {
        $name = 'field'.$rule['field'];
        $raw = $data[$name] ?? '';
        $value = is_array($raw) ? implode(',', $raw) : (string) $raw;
        $target = (string) ($rule['value'] ?? '');

        return match ($rule['operator'] ?? '') {
            'equals' => $value === $target,
            'not_equals' => $value !== $target,
            'contains' => $target !== '' && str_contains(strtolower($value), strtolower($target)),
            'empty' => $value === '',
            'not_empty' => $value !== '',
            'checked' => $value !== '',
            'unchecked' => $value === '',
            'gt' => is_numeric($value) && is_numeric($target) && (float) $value > (float) $target,
            'lt' => is_numeric($value) && is_numeric($target) && (float) $value < (float) $target,
            default => false,
        };
    }
}
