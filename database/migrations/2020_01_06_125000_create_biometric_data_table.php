<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBiometricDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('biometric', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_number');
            $table->tinyInteger('type');
            $table->string('template_key');
            $table->longText('finger_one');
            $table->longText('finger_two');
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
        Schema::dropIfExists('biometric');
    }
}
