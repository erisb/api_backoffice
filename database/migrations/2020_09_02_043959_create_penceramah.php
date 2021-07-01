<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenceramah extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lecturers', function (Blueprint $collection) {
            $collection->id();
            $collection->string('lecturerName');
            $collection->string('lecturerAddress');
            $collection->string('lecturerPhoto');
            $collection->string('lecturerDesc');
            $collection->string('lecturerDateofBirth');
            $collection->string('lecturerTelp');
            $collection->string('lecturerEmail');
            $collection->string('lecturerAlmamater');
            $collection->string('lecturerSosmed');
            $collection->string('lecturerStatus');
            $collection->string('lecturerGallery1');
            $collection->string('lecturerGallery2');
            $collection->string('lecturerGallery3');
            $collection->string('lecturerGallery4');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lecturers');
    }
}
