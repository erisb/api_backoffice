<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logtransactions', function (Blueprint $collection) {
            $collection->id();
            $collection->string('bookingCode');
            $collection->string('idUserMobile');
            $collection->string('totalPrice');
            $collection->integer('paymentStatus');
            $collection->string('description');
            $collection->integer('flag');
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
        Schema::dropIfExists('logtransactions');
    }
}
