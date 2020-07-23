<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormFieldsDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('form_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('form_id')->unsigned();
            $table->integer('form_field_type_id')->unsigned();
            $table->boolean('active')->default(1);
            $table->boolean('show_label')->default(1);
            $table->integer('position');
            $table->string('name');
            $table->boolean('required')->default(0);
            $table->string('placeholder')->nullable();
            $table->text('data')->nullable();
            $table->string('custom_field_class')->nullable();
            $table->string('store_in')->nullable();
            $table->text('note')->nullable();
            $table->boolean('note_position')->default(0);
            $table->boolean('label_position')->default(1);
            $table->boolean('autocomplete')->default(0);
            $table->text('custom_class')->nullable();
            $table->string('merge_field')->nullable();
            $table->string('hidden_field_value')->nullable();

            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            $table->foreign('form_field_type_id')->references('id')->on('form_field_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('form_fields');
    }
}
