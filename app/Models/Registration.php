<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Registration extends Model
{
    private $_db;

    public function __construct()
    {
        parent::__construct();
        $this->_db = DB::connection('mysql_ntc');
    }

    public function _currentSettings()
    {
        $state = $this->_db->table('current_settings')->limit(1)->first();
        return $state;
    }

    public function _getCourses($programmeId, $year, $semester)
    {
        $query = $this->_db->table('courses')->where('programme_id', $programmeId)
            ->where('year', $year)->where('sem', $semester)->get();
        return $query;
    }

    public function _getTrailedCourses($username, $programmeId)
    {
        $query = $this->_db->table('academicrecords AS a1')
            ->select(
                'a1.indexno',
                'a1.programme_id',
                'a1.course',
                'a1.code',
                'a1.coursename',
                'a1.credithrs',
                'a1.grade',
                'a1.mark',
                'a1.ne',
                'a1.yr',
                'a1.sem',
                'a1.acadyear'
            )->where('indexno', $username)
            ->where('programme_id', '=', $programmeId)
            ->where('trail', '=', 1)->whereIn('grade', ['F', '', ' '])
            ->whereIn('mark', ['DF', 'S'])->whereNotExists(function ($q) {
                $q->select(DB::raw('course,code'))
                    ->from('academicrecords AS a2')
                    ->whereRaw('a2.indexno = a1.indexno')
                    ->whereRaw('a2.programme_id = a1.programme_id')
                    ->whereRaw('a2.course = a1.course')
                    ->whereRaw('a2.code = a1.code')
                    ->whereRaw('a2.trail = 0');
            })->get();

        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _registrationCount($username, $programmeId, $academicYear, $year, $semester)
    {
        $query = $this->_db->table('academicrecords')->where('indexno', '=', $username)
            ->where('programme_id', '=', $programmeId)
            ->where('acadyear2', '=', $academicYear)->where('yr2', '=', $year)
            ->where('sem2', '=', $semester)->count();
        return $query;
    }

    public function _saveCourseRegistrationOrTrails(array $payload)
    {
        $this->_db->table('academicrecords')->insert($payload);
    }

    public function _deleteStudentRegistration($username, $programmeId, $academicYear, $year, $semester)
    {
        $this->_db->table('academicrecords')
            ->where('indexno', '=', $username)
            ->where('programme_id', '=', $programmeId)
            ->where('acadyear2', '=', $academicYear)
            ->where('yr2', '=', $year)
            ->where('sem2', '=', $semester)
            ->delete();
    }

    public function _saveRegistrationHistory(array $payload)
    {
        $this->_db->table('registered_students')
            ->insert($payload);
    }

    public function _deleteRegistrationHistory($username, $academicYear, $semester, $year)
    {
        $this->_db->table('registered_students')
            ->where('indexno', '=', $username)
            ->where('academic_year', '=', $academicYear)
            ->where('semester', '=', $semester)
            ->where('year', '=', $year)
            ->delete();
    }
}
