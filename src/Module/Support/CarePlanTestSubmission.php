<?php

namespace RefinedDigital\FormBuilder\Module\Support;

use Illuminate\Support\Arr;

/**
 * Recognises a monthly care plan form test: a submission that uses the configured
 * test email in any field. It is validated like any other, but sends nothing,
 * stores nothing and is not counted as an enquiry.
 */
class CarePlanTestSubmission
{
    /** Whether any submitted value is exactly the configured test email. */
    public static function matches(array $input): bool
    {
        $testEmail = strtolower(trim((string) config('form-builder.care_plan_enquiries.test_email')));

        if ($testEmail === '') {
            return false;
        }

        foreach (Arr::flatten($input) as $value) {
            if (is_string($value) && strtolower(trim($value)) === $testEmail) {
                return true;
            }
        }

        return false;
    }
}
