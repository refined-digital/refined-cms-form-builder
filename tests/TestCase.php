<?php

namespace RefinedDigital\FormBuilder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RefinedDigital\FormBuilder\Module\Providers\FormBuilderServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [FormBuilderServiceProvider::class];
    }
}
