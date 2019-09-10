<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionResponseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_response', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_request_info')->unsigned()->nullable();
            $table->string('id_order')->nullable();
            $table->string('id_transaction')->nullable();
            $table->string('status')->nullable();
            $table->string('response_code')->nullable();
            $table->string('pending_reason')->nullable();
            $table->string('url_payment_recipt_html')->nullable();
            $table->string('url_payment_recipt_pdf')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('trazability_code')->nullable();
            $table->string('global_update')->nullable();
            $table->timestamps();

            $table->foreign('id_request_info')->references('id')->on('request_info')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_response');
    }
}
