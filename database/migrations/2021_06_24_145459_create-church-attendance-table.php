<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChurchAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('church_attendance', function (Blueprint $table) {
            $table->id()->index();
            $table->string('id_number');
            $table->tinyInteger('type');
            $table->date('attendance_date');
            $table->time('attendance_time');
            $table->string('academic_year', 10);
            $table->tinyInteger('semester');
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
        Schema::dropIfExists('church_attendance');
    }
}
