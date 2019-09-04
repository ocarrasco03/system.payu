<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_system')->unsigned()->nullable();
            $table->integer('id_payer')->unsigned()->nullable();
            $table->string('id_reservation')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();

            $table->foreign('id_system')->references('id')->on('systems')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_payer')->references('id')->on('payer_data')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_info');
    }
}
