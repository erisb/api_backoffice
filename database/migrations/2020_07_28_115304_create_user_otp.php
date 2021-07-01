<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOtp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userotps', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idUserMobile')->unique();
            $collection->string('kodeOTP');
            $collection->string('statusOTP');
            $collection->string('statusOTPByUser');
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
        Schema::dropIfExists('userotps');
    }
}
