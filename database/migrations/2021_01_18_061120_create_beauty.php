<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBeauty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beauty', function (Blueprint $collection) {
            $collection->id();
            $collection->string('categoryId');
            $collection->string('titleBeauty');
            $collection->string('contentBeauty');
            $collection->string('imageBeauty');
            $collection->string('totalViewerBeauty');
            $collection->string('publishBeauty');
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
        Schema::dropIfExists('beauty');
    }
}
