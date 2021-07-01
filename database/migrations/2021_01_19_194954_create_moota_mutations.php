<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMootaMutations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mootamutations', function (Blueprint $collection) {
            $collection->id();
            $collection->string('mutationId');
            $collection->string('bankId');
            $collection->integer('accountNumber');
            $collection->string('bankType');
            $collection->string('date');
            $collection->integer('amount');
            $collection->string('description');
            $collection->string('type');
            $collection->integer('balance');
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
        Schema::dropIfExists('mootamutations');
    }
}
