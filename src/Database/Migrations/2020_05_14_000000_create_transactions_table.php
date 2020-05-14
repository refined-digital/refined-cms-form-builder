<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_payment_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('form_id')->unsigned();
            $table->integer('type_id');
            $table->string('type_details');
            $table->string('transaction_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();

            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
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
        Schema::drop('form_payment_transactions');
    }
}
