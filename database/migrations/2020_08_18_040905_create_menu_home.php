<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuHome extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menuhomes', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idMenu')->unique();
            $collection->string('judulMenu');
            $collection->string('gambarMenu');
            $collection->string('statusMenu');
            $collection->string('roleMenu');
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
        Schema::dropIfExists('menuhomes');
    }
}
