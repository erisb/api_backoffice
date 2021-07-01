<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doas', function (Blueprint $collection) {
            $collection->id();
            $collection->string('prayerTitle');
            $collection->string('indonesianVersion');
            $collection->string('arabVersion');
            $collection->string('prayerSource');
            $collection->string('prayerStatus');
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
        Schema::dropIfExists('doas');
    }
}
