<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostelAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hostel_attendance', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('hostel_id')->index();
            $table->string('student_id')->index();
            $table->date('attendance_date');
            $table->time('attendance_time');
            $table->string('academic_year', 10)->index();
            $table->tinyInteger('semester')->index();
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
        Schema::dropIfExists('hostel_attendance');
    }
}
