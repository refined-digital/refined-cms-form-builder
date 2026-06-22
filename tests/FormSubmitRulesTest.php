<?php

namespace RefinedDigital\FormBuilder\Tests;

use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use RefinedDigital\FormBuilder\Module\Http\Requests\FormSubmitRequest;

// smoke test: FormSubmitRequest::rules() asks each field's class for its rules
// (no field-type switch). asserts the loop produces the expected rule set so a
// future field-type change can't silently break validation.
class FormSubmitRulesTest extends TestCase
{
    /** build an unsaved FormField (the FieldType trait accessors work without a DB) */
    protected function field(int $id, int $typeId, bool $required, string $name): FormField
    {
        $field = new FormField;
        $field->id = $id;
        $field->form_field_type_id = $typeId;
        $field->required = $required ? 1 : 0;
        $field->name = $name;
        return $field;
    }

    protected function rulesFor(Collection $fields, bool $recaptcha = false): FormSubmitRequest
    {
        $form = (object) ['fields' => $fields, 'recaptcha' => $recaptcha];

        $request = new FormSubmitRequest;
        $route = new Route('POST', 'forms/{form}/submit', []);
        $route->bind(Request::create('/', 'POST'));
        $route->setParameter('form', $form);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_builds_per_field_rules_without_a_type_switch(): void
    {
        $fields = collect([
            $this->field(1, 8, true, 'Email'),                       // email
            $this->field(2, 11, true, 'Password'),                   // confirmed + sibling
            $this->field(3, 14, true, 'Country'),                    // not0
            $this->field(4, 1, false, 'Notes'),                      // not required -> gibberish only
            $this->field(5, 19, true, 'Static'),                     // skip_validation -> absent
        ]);

        $request = $this->rulesFor($fields);
        $rules = $request->rules();
        $messages = $request->messages();

        // email
        $this->assertSame(['required', 'email'], $rules['field1']);
        $this->assertSame('The Email must be a valid email address.', $messages['field1.email']);

        // password with confirmation + the synthetic sibling field
        $this->assertSame(['required', 'confirmed', 'min:5'], $rules['field2']);
        $this->assertSame(['required', 'min:5'], $rules['field2_confirmation']);
        $this->assertArrayHasKey('field2_confirmation.required', $messages);

        // country -> not0 (validator extension registered by core)
        $this->assertSame(['required', 'not0'], $rules['field3']);

        // non-required text still gets the gibberish rule, but no 'required'
        $this->assertArrayHasKey('field4', $rules);
        $this->assertContainsOnly('object', array_filter($rules['field4'], 'is_object'));

        // static field is skipped entirely
        $this->assertArrayNotHasKey('field5', $rules);

        // form-level honeypot is always present, recaptcha only when enabled
        $this->assertSame('honeypot', $rules['hname']);
        $this->assertArrayNotHasKey('_captcha', $rules);
    }

    public function test_array_field_validates_per_item(): void
    {
        $fields = collect([$this->field(9, 18, true, 'Files')]);     // multiple files

        $rules = $this->rulesFor($fields)->rules();

        $this->assertSame('required', $rules['field9']);
        $this->assertSame(['required', 'mimes:'.config('form-builder.accepted_mime_types')], $rules['field9.*']);
    }
}
