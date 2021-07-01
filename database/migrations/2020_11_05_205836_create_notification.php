<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $collection) {
            $collection->id();
            $collection->string('urlId');
            $collection->string('idUserMobile');
            $collection->string('title');
            $collection->string('description');
            $collection->integer('position');
            $collection->integer('type');
            $collection->integer('flag');
            $collection->integer('read');
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
        Schema::dropIfExists('notifications');
    }
}
