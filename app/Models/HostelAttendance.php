<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HostelAttendance extends Model
{
    private $_connection;
    protected $table = 'hostel_attendance';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_connection = DB::connection('mysql_ntc');
    }

    private function _connectTable()
    {
        return $this->_connection->table($this->table);
    }


    public function _save(array $payload)
    {
        return $this->_connectTable()->insertGetId($payload);
    }

    public function _saveBatch(array $payload)
    {
        return $this->_connectTable()->insert($payload);
    }

    public function _getAttendanceList($academicYear, $semester)
    {
        return $this->_connectTable()
            ->where($this->table . '.academic_year', '=', $academicYear)
            ->where($this->table . '.semester', '=', $semester)
            ->join('students', $this->table . '.student_id', '=', 'students.indexno')
            ->join('hostels', $this->table . '.hostel_id', '=', 'hostels.id')
            ->select(
                $this->table . '.*',
                'students.indexno',
                'students.programmeid',
                'students.surname',
                'students.firstname',
                'students.sex',
                'students.yearofadmission',
                'students.class1',
                'students.telephone',
                'hostels.name as hostel_name',
                'hostels.phone as hostel_phone'
            )
            ->get();
    }

    public function _getHostelAttendance($hostelId, $academicYear, $semester)
    {
        return $this->_connectTable()
            ->where($this->table . '.hostel_id', '=', $hostelId)
            ->where($this->table . '.academic_year', '=', $academicYear)
            ->where($this->table . '.semester', '=', $semester)
            ->join('students', $this->table . '.student_id', '=', 'students.indexno')
            ->join('hostels', $this->table . '.hostel_id', '=', 'hostels.id')
            ->select(
                $this->table . '.*',
                'students.indexno',
                'students.programmeid',
                'students.surname',
                'students.firstname',
                'students.sex',
                'students.yearofadmission',
                'students.class1',
                'students.telephone',
                'hostels.name as hostel_name',
                'hostels.phone as hostel_phone'
            )
            ->get();
    }

    public function _getStudentAttendance($studentId, $academicYear, $semester)
    {
        return $this->_connectTable()
            ->where($this->table . '.student_id', '=', $studentId)
            ->where($this->table . '.academic_year', '=', $academicYear)
            ->where($this->table . '.semester', '=', $semester)
            ->join('students', $this->table . '.student_id', '=', 'students.indexno')
            ->join('hostels', $this->table . '.hostel_id', '=', 'hostels.id')
            ->select(
                $this->table . '.*',
                'students.indexno',
                'students.programmeid',
                'students.surname',
                'students.firstname',
                'students.sex',
                'students.yearofadmission',
                'students.class1',
                'students.telephone',
                'hostels.name as hostel_name',
                'hostels.phone as hostel_phone'
            )
            ->first();
    }

    public function _getAttendance($id)
    {
        return $this->_connectTable()
            ->where($this->table . '.id', '=', $id)
            ->join('students', $this->table . '.student_id', '=', 'students.indexno')
            ->join('hostels', $this->table . '.hostel_id', '=', 'hostels.id')
            ->select(
                $this->table . '.*',
                'students.indexno',
                'students.programmeid',
                'students.surname',
                'students.firstname',
                'students.sex',
                'students.yearofadmission',
                'students.class1',
                'students.telephone',
                'hostels.name as hostel_name',
                'hostels.phone as hostel_phone'
            )
            ->first();
    }
}
