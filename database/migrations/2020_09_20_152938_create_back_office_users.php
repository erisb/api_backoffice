<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackOfficeUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backofficeusers', function (Blueprint $collection) {
            $collection->id();
            $collection->string('namaUser')->nullable();
            $collection->string('emailUser')->unique();
            $collection->string('passwordUser');
            $collection->string('roleUser');
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
        Schema::dropIfExists('backofficeusers');
    }
}
