<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackOfficeUserLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backofficeuserlogs', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idUserBackOffice');
            $collection->string('modul');
            $collection->string('activity');
            $collection->string('status');
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
        Schema::dropIfExists('backofficeuserlogs');
    }
}
