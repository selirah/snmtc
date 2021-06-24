<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_number');
            $table->string('token');
            $table->integer('expiry');
            $table->tinyInteger('is_expired');
            $table->tinyInteger('is_logged_in');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_history');
    }
}
