<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('forms', 'recaptcha')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->boolean('recaptcha')->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('forms', 'recaptcha')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->boolean('recaptcha')->default(1)->change();
            });
        }
    }
};
