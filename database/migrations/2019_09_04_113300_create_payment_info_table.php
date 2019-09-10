<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_request_info')->unsigned()->nullable();
            $table->string('reference_code')->nullable();
            $table->string('description')->nullable();
            $table->double('value', 14, 2)->nullable();
            $table->string('currency', 5)->nullable();
            $table->string('payment_method')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->double('tax_value', 10, 2)->nullable();
            $table->double('tax_return_base', 10, 2)->nullable();
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
        Schema::dropIfExists('payment_info');
    }
}
