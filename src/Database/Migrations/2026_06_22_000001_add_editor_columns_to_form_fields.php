<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('form_fields', 'default_value')) {
                $table->text('default_value')->nullable()->after('placeholder');
            }
            if (!Schema::hasColumn('form_fields', 'error_message')) {
                $table->string('error_message')->nullable()->after('default_value');
            }
            if (!Schema::hasColumn('form_fields', 'include_in_email')) {
                $table->boolean('include_in_email')->default(1)->after('error_message');
            }
            // visible | hidden | disabled | readonly
            if (!Schema::hasColumn('form_fields', 'visibility')) {
                $table->string('visibility')->default('visible')->after('include_in_email');
            }
            // conditional logic JSON (see FormField::$casts)
            if (!Schema::hasColumn('form_fields', 'visibility_rules')) {
                $table->longText('visibility_rules')->nullable()->after('visibility');
            }
        });

        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'submit_text')) {
                $table->string('submit_text')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            foreach (['default_value', 'error_message', 'include_in_email', 'visibility', 'visibility_rules'] as $column) {
                if (Schema::hasColumn('form_fields', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'submit_text')) {
                $table->dropColumn('submit_text');
            }
        });
    }
};
