<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $collection) {
            $collection->id();
            $collection->string('categoryId');
            $collection->string('titleNews');
            $collection->string('contentNews');
            $collection->string('imageNews');
            $collection->string('totalViewerNews');
            $collection->string('publishNews');
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
        Schema::dropIfExists('news');
    }
}
