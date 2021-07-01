<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackOfficeMenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backofficemenus', function (Blueprint $collection) {
            $collection->id();
            $collection->string('namaMenu');
            $collection->string('urlMenu');
            $collection->string('iconMenu');
            $collection->integer('noMenu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('backofficemenus');
    }
}
