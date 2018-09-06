<?php

namespace RefinedDigital\FormBuilder\Database\Seeds;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FormBuilderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // first, install the table
        \DB::table('forms')->insert([
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
            'active'        => 1,
            'position'      => 0,
            'form_action'   => 1,
            'recaptcha'     => 1,
            'name'          => 'Contact Us',
            'subject'       => 'Contact Us',
            'email_to'      => 'matthias@refineddigital.co.nz',
            'message'       => '<p>You have a new contact form submission</p><p>[[fields]]</p>',
            'confirmation'  => '<p>Thanks for contacting us.</p><p>We will be in touch soon</p>',
        ]);

        // now insert the fields
        $fields = [
            ['form_id' => 1, 'form_field_type_id' => 1, 'active' => 1, 'show_label' => 1, 'position' => 0, 'name' => 'Name',    'required' => 1],
            ['form_id' => 1, 'form_field_type_id' => 8, 'active' => 1, 'show_label' => 1, 'position' => 1, 'name' => 'Email',   'required' => 1],
            ['form_id' => 1, 'form_field_type_id' => 9, 'active' => 1, 'show_label' => 1, 'position' => 2, 'name' => 'Phone',   'required' => 1],
            ['form_id' => 1, 'form_field_type_id' => 2, 'active' => 1, 'show_label' => 1, 'position' => 3, 'name' => 'Message', 'required' => 1],
        ];

        foreach($fields as $field) {
            $field['created_at'] = Carbon::now();
            $field['updated_at'] = Carbon::now();
            \DB::table('form_fields')->insert($field);
        }
    }
}
