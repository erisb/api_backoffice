<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUmrohOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('umrohorders', function (Blueprint $collection) {
            $collection->id();
            $collection->string('orderId');
            $collection->string('packageId');
            $collection->string('orderCode');
            $collection->string('bookingCode');
            $collection->string('idUserMobile');
            $collection->string('room');
            $collection->string('totalPilgrims');
            $collection->json('listPilgrims');
            $collection->string('totalPrice');
            $collection->string('methodPayment');
            $collection->json('listPayment');
            $collection->integer('status');
            $collection->integer('flag');
            $collection->integer('isCancel');
            $collection->date('paidOffDate');
            $collection->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('umrohorders');
    }
}
