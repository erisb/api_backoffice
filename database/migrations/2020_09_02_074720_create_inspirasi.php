<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspirasi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspirations', function (Blueprint $collection) {
            $collection->id();
            $collection->id('categoryId');
            $collection->string('contentInspiration');
            $collection->string('sourceInspiration');
            $collection->string('imageInspiration');
            $collection->string('statusInspiration');
            $collection->string('meaningInspiration');
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
        Schema::dropIfExists('inspirations');
    }
}
