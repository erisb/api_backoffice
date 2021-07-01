<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryQurans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historyqurans', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idQuran');
            $collection->string('imei');
            $collection->string('no');
            $collection->string('type');
            $collection->string('ayat_position');
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
        Schema::dropIfExists('historyqurans');
    }
}
