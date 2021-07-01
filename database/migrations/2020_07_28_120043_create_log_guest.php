<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogGuest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logguests', function (Blueprint $collection) {
            $collection->id();
            $collection->string('ipAddress');
            $collection->string('jenisHP');
            $collection->string('jenisOS');
            $collection->string('imei');
            $collection->string('lokasi');
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
        Schema::dropIfExists('logguests');
    }
}
