<?php

namespace RefinedDigital\FormBuilder\Database\Seeds;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FieldTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $levels = [
            'Text',
            'Textarea',
            'Select',
            'Radio',
            'Checkbox',
            'Single Checkbox',
            'Number',
            'Email',
            'Tel',
            'Password',
            'Password with Confirmation',
            'Hidden',
            'YesNo Select',
            'Country Select',
            'Date',
            'Date Time',
            'File',
            'Multiple Files',
            'Static',

        ];

        foreach($levels as $pos => $level):
            \DB::table('form_field_types')->insert([
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'active'    => 1,
                'position'  => $pos,
                'name'      => $level
            ]);
        endforeach;
    }
}
