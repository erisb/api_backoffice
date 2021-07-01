<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUmrohPackage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('umrohpackages', function (Blueprint $collection) {
            $collection->id();
            $collection->string('id');
            $collection->string('image');
            $collection->string('name');
            $collection->string('description');
            $collection->string('travel_id');
            $collection->string('travel_name');
            $collection->string('travel_avatar');
            $collection->string('travel_umrah_permission');
            $collection->string('travel_description');
            $collection->string('travel_address');
            $collection->string('travel_pilgrims');
            $collection->string('travel_founded');
            $collection->string('stock');
            $collection->string('duration');
            $collection->string('departure_date');
            $collection->string('available_seat');
            $collection->string('original_price');
            $collection->string('reduced_price');
            $collection->string('discount');
            $collection->string('departure_from');
            $collection->string('transit');
            $collection->string('arrival_city');
            $collection->string('origin_arrival_city');
            $collection->string('departure_city');
            $collection->string('origin_departure_city');
            $collection->string('down_payment');
            $collection->json('rooms');
            $collection->json('airlines');
            $collection->json('hotels');
            $collection->json('itineraries');
            $collection->string('Is_change_package');
            $collection->string('notes');
            $collection->string('is_dummy');
            $collection->string('flag');
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
        Schema::dropIfExists('umrohpackages');
    }
}
