<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntertainment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entertainments', function (Blueprint $collection) {
            $collection->id();
            $collection->string('categoryId');
            $collection->string('titleEntertainment');
            $collection->string('contentEntertainment');
            $collection->string('imageEntertainment');
            $collection->string('totalViewerEntertainment');
            $collection->string('publishEntertainment');
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
        Schema::dropIfExists('entertainments');
    }
}
