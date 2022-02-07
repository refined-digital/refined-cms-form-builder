<?php

namespace RefinedDigital\FormBuilder\Module\Contracts;

interface FormFieldInterface {

    public function __construct($field);

    public function render();

    public function formatData($request);

    public function getValidationRules();

}
