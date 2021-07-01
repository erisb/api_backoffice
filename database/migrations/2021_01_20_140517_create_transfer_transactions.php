<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfertransactions', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idUserMobile');
            $collection->string('transferId');
            $collection->string('trxId');
            $collection->string('recipientBank');
            $collection->string('recipientAccount');
            $collection->string('recipientName');
            $collection->string('amount');
            $collection->string('note');
            $collection->string('statusTransfer');
            $collection->string('codeUnik');
            $collection->string('adminFee');
            $collection->string('spsBankCode');
            $collection->string('spsBank');
            $collection->string('nominal');
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
        Schema::dropIfExists('transfertransactions');
    }
}
