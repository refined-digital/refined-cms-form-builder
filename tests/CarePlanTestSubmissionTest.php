<?php

namespace RefinedDigital\FormBuilder\Tests;

use RefinedDigital\FormBuilder\Module\Support\CarePlanTestSubmission;

// a monthly form test must never reach the client, an integration or the enquiry count
class CarePlanTestSubmissionTest extends TestCase
{
    protected function useTestEmail(?string $email): void
    {
        config(['form-builder.care_plan_enquiries.test_email' => $email]);
    }

    public function test_a_submission_using_the_test_email_matches(): void
    {
        $this->useTestEmail('forms@refined.test');

        $this->assertTrue(CarePlanTestSubmission::matches(['name' => 'Test', 'field12' => 'forms@refined.test']));
    }

    public function test_matching_ignores_case_and_surrounding_spaces(): void
    {
        $this->useTestEmail('Forms@Refined.test');

        $this->assertTrue(CarePlanTestSubmission::matches(['field12' => ' forms@refined.TEST ']));
    }

    public function test_it_finds_the_email_in_nested_fields(): void
    {
        $this->useTestEmail('forms@refined.test');

        $this->assertTrue(CarePlanTestSubmission::matches(['fields' => ['contact' => ['forms@refined.test']]]));
    }

    public function test_a_normal_submission_does_not_match(): void
    {
        $this->useTestEmail('forms@refined.test');

        $this->assertFalse(CarePlanTestSubmission::matches(['field12' => 'client@example.test']));
        $this->assertFalse(CarePlanTestSubmission::matches(['message' => 'please email forms@refined.test']));
    }

    public function test_nothing_matches_when_no_test_email_is_set(): void
    {
        foreach ([null, '', '   '] as $email) {
            $this->useTestEmail($email);

            $this->assertFalse(CarePlanTestSubmission::matches(['field12' => '', 'other' => '   ']));
        }
    }

    public function test_uploaded_files_and_other_values_are_ignored(): void
    {
        $this->useTestEmail('forms@refined.test');

        $this->assertFalse(CarePlanTestSubmission::matches(['file' => new \stdClass, 'count' => 3, 'agree' => true]));
    }
}
