<?php

namespace RefinedDigital\FormBuilder\Module\Rules;

use Illuminate\Contracts\Validation\Rule;

class ReCaptcha implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $captcha = new ReCaptcha(env('RECAPTCHA_SECRET'));
        $response = $captcha->verify($value, $_SERVER['REMOTE_ADDR']);
        return $response->isSuccess() ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You must confirm you are not a Robot.';
    }
}
