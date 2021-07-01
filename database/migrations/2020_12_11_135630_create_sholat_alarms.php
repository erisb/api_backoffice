<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSholatAlarms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sholatalarms', function (Blueprint $collection) {
            $collection->id();
            $collection->string('imei');
            $collection->string('tglWaktuSholat');
            $collection->json('listWaktuSholat');
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
        Schema::dropIfExists('sholatalarms');
    }
}
