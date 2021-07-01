<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMobile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usermobiles', function (Blueprint $collection) {
            $collection->id();
            $collection->string('namaUser')->nullable();
            $collection->string('noTelpUser')->unique();
            $collection->string('emailUser')->unique();
            $collection->string('pinUser');
            $collection->string('nik');
            $collection->string('urlFoto');
            $collection->string('urlFotoKtp');
            $collection->string('urlFotoSelfieKtp');
            $collection->string('statusVerifikasi'); // 0. belum verifikasi 1. verifikasi gagal 2. verifikasi pending 3. verifikasi berhasil
            $collection->string('imei');
            $collection->boolean('flag');
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
        Schema::dropIfExists('usermobiles');
    }
}
