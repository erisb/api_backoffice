<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackofficeUserTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backofficeusertokens', function (Blueprint $collection) {
            $collection->id();
            $collection->string('idUserBackOffice')->unique();
            $collection->string('token');
            $collection->date('expired');
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
        Schema::dropIfExists('backofficeusertokens');
    }
}
