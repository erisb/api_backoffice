<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodAndTravel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foodandtravels', function (Blueprint $collection) {
            $collection->id();
            $collection->string('categoryId');
            $collection->string('titleFoodandTravel');
            $collection->string('contentFoodandTravel');
            $collection->string('imageFoodandTravel');
            $collection->string('totalViewerFoodandTravel');
            $collection->string('publishFoodandTravel');
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
        Schema::dropIfExists('foodandtravels');
    }
}
