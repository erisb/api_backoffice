<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymentcodes', function (Blueprint $table) {
            $table->id();            
            $table->uuid('idMasjid');
            $table->string('paymentType');
            $table->string('paymentCode');
            $table->string('paymentFor');
            $table->integer('nominal');
            $table->string('channel');
            $table->string('deskripsi');
            $table->string('expired');
            $table->string('trxId');
            $table->string('status');
            $table->string('idUserMobile');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paymentcodes');
    }
}
