<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTopup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactiontopup', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idUserMobile');
            $collection->string('trnsactionId');
            $collection->string('refId');
            $collection->string('noReferensi');
            $collection->string('hp');
            $collection->string('codeTopup');
            $collection->string('operatorTopup');
            $collection->string('nominalTopup');
            $collection->string('priceTopup');
            $collection->string('typeTopup');
            $collection->string('messageTopup');
            $collection->string('balanceTopup');
            $collection->string('detailTopup');
            $collection->string('masaAktif');
            $collection->string('serialNumber');
            $collection->string('statusTopup');
            $collection->string('trName'); //Bill Account Name
            $collection->string('period'); //Bill period
            $collection->string('admin'); //Admin fee
            $collection->string('sellingPrice'); //Bill Selling Price
            $collection->json('desc'); //Bill Desc
            $collection->string('datetime'); //Mobile Pulsa Type ( Pasca / Pra )
            $collection->string('mpType'); //Mobile Pulsa Type ( Pasca / Pra )
            $collection->string('spsBank'); //Mobile Pulsa Type ( Pasca / Pra )
            $collection->string('codeUnik'); //Mobile Pulsa Type ( Pasca / Pra )
            $collection->string('totalTransfer'); //Mobile Pulsa Type ( Pasca / Pra )
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
        Schema::dropIfExists('transactiontopup');
    }
}
