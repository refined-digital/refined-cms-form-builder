<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_integrations')) {
            return;
        }

        Schema::create('form_integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('form_id')->unsigned();
            $table->string('integration_key');
            $table->boolean('enabled')->default(0);
            $table->boolean('send_email')->default(1);
            $table->json('config')->nullable();

            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            $table->unique(['form_id', 'integration_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_integrations');
    }
};
