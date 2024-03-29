<?php

namespace RefinedDigital\FormBuilder\Database\Seeds;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(FieldTypeTableSeeder::class);
        $this->call(FormBuilderTableSeeder::class);
    }
}
