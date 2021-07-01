<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Category;

class CreateArtikel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $collection) {
            $collection->id();
            $collection->id('idKategori');
            $collection->string('articleTitle');
            $collection->string('articleContent');
            $collection->string('articleImage');
            $collection->string('articleAdmin');
            $collection->string('totalViewer');
            $collection->string('publish');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
