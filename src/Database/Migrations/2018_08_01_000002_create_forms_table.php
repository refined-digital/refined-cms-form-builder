<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('forms', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('active')->default(1);
            $table->integer('position');
            $table->integer('form_action')->default(1);
            $table->boolean('recaptcha')->default(1);
            $table->boolean('send_as_plain_text')->default(0);
            $table->string('name')->nullable();
            $table->string('subject')->nullable();
            $table->string('email_to')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('callback')->nullable();
            $table->string('model')->nullable();
            $table->longText('message')->nullable();
            $table->longText('confirmation')->nullable();
            $table->string('redirect_page')->nullable();
            $table->boolean('receipt')->default(0);
            $table->longText('receipt_subject')->nullable();
            $table->longText('receipt_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('forms');
    }
}
