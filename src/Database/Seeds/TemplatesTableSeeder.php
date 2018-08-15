<?php

namespace RefinedDigital\Blog\Database\Seeds;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use DB;

class TemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            [
                'name'      => 'Forms',
                'source'    => 'forms',
                'active'    => 1,
                'has_forms' => 1,
            ],
        ];

        foreach($templates as $pos => $u) {
            $args = [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'position' => $pos,
            ];
            $data = array_merge($args, $u);
            DB::table('templates')->insert($data);
        }
    }
}
