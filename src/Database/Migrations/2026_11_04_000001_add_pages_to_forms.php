<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'use_pages')) {
                $table->tinyInteger('use_pages')->default(0);
            }
            if (!Schema::hasColumn('forms', 'autosave')) {
                $table->tinyInteger('autosave')->default(0);
            }
        });

        if (!Schema::hasTable('form_pages')) {
            Schema::create('form_pages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('form_id');
                $table->string('name');
                $table->integer('position')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('form_fields', 'form_page_id')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->unsignedInteger('form_page_id')->nullable();

                $table->foreign('form_page_id')->references('id')->on('form_pages')->nullOnDelete();
            });
        }

        // a fresh install seeds every type (heading last, id 24) after migrating, so only add it to a seeded table
        if (DB::table('form_field_types')->exists()) {
            DB::table('form_field_types')->insertOrIgnore([
                'id' => 24,
                'name' => 'Heading',
                'position' => 23,
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('form_field_types')->where('id', 24)->delete();

        if (Schema::hasColumn('form_fields', 'form_page_id')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->dropForeign(['form_page_id']);
                $table->dropColumn('form_page_id');
            });
        }

        Schema::dropIfExists('form_pages');

        Schema::table('forms', function (Blueprint $table) {
            foreach (['use_pages', 'autosave'] as $column) {
                if (Schema::hasColumn('forms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
