<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('form_email_notifications')) {
            Schema::create('form_email_notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('form_id')->unsigned();
                $table->integer('position')->default(0);
                $table->boolean('active')->default(1);
                $table->string('name');
                $table->text('to')->nullable();
                $table->text('cc')->nullable();
                $table->text('bcc')->nullable();
                $table->string('reply_to')->nullable();
                $table->string('subject')->nullable();
                $table->longText('content')->nullable();

                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            });
        }

        Schema::table('forms', function (Blueprint $table) {
            // behaviour: message | redirect_page | redirect_url
            if (!Schema::hasColumn('forms', 'submit_action')) {
                $table->string('submit_action')->default('message')->after('submit_text');
            }
            if (!Schema::hasColumn('forms', 'redirect_url')) {
                $table->string('redirect_url')->nullable()->after('redirect_page');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_email_notifications');

        Schema::table('forms', function (Blueprint $table) {
            foreach (['submit_action', 'redirect_url'] as $column) {
                if (Schema::hasColumn('forms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
