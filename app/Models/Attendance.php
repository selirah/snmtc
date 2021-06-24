<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    private $_connection;
    protected $table = 'attendance';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_connection = DB::connection('mysql_ntc');
    }

    private function _connectTable()
    {
        return $this->_connection->table($this->table);
    }

    public function _checkAttendance($idNumber, $courseId, $date)
    {
        return $this->_connectTable()
            ->where('id_number', '=', $idNumber)
            ->where('course_id', '=', $courseId)
            ->where('attendance_date', '=', $date)
            ->first();
    }

    public function _getAttendanceData($idNumber, $type, $date)
    {
        switch ($type) {
            case 'staff':
                return $this->_connectTable()
                    ->where('id_number', '=', $idNumber)
                    ->where('attendance_date', '=', $date)
                    ->join('staff', $this->table . '.id_number', '=', 'staff.staff_id')
                    ->select(
                        $this->table . '.*',
                        'staff.staff_id',
                        'staff.firstname',
                        'staff.lastname',
                        'staff.phone_number',
                        'staff.email_address',
                        'staff.gender'
                    )
                    ->get();
                break;
            case 'students':
                return $this->_connectTable()
                    ->where('id_number', '=', $idNumber)
                    ->where('attendance_date', '=', $date)
                    ->join('students', $this->table . '.id_number', '=', 'students.indexno')
                    ->select(
                        $this->table . '.*',
                        'students.indexno',
                        'students.programmeid',
                        'students.surname',
                        'students.firstname',
                        'students.sex',
                        'students.yearofadmission',
                        'students.class1',
                        'students.telephone'
                    )
                    ->get();
                break;
        }
    }

    public function _getAttendance($type, $date)
    {
        switch ($type) {
            case 'staff':
                $query = $this->_connectTable()
                    ->where('type', '=', 1)
                    ->where('attendance_date', '=', $date)
                    ->join('staff', $this->table . '.id_number', '=', 'staff.staff_id')
                    ->select(
                        $this->table . '.*',
                        'staff.staff_id',
                        'staff.firstname',
                        'staff.lastname',
                        'staff.phone_number',
                        'staff.email_address',
                        'staff.gender'
                    )
                    ->get();
                return $query;
                break;
            case 'students':
                $query = $this->_connectTable()
                    ->where('type', '=', 2)
                    ->where('attendance_date', '=', $date)
                    ->join('students', $this->table . '.id_number', '=', 'students.indexno')
                    ->select(
                        $this->table . '.*',
                        'students.indexno',
                        'students.programmeid',
                        'students.surname',
                        'students.firstname',
                        'students.sex',
                        'students.yearofadmission',
                        'students.class1',
                        'students.telephone'
                    )
                    ->get();
                return $query;
                break;
        }
    }

    public function _getAttendanceWithCourseId($courseId, $type, $date)
    {
        switch ($type) {
            case 'staff':
                $query = $this->_connectTable()
                    ->where('course_id', '=', $courseId)
                    ->where('attendance_date', '=', $date)
                    ->join('staff', $this->table . '.id_number', '=', 'staff.staff_id')
                    ->select(
                        $this->table . '.*',
                        'staff.staff_id',
                        'staff.firstname',
                        'staff.lastname',
                        'staff.phone_number',
                        'staff.email_address',
                        'staff.gender'
                    )
                    ->get();
                return $query;
                break;
            case 'students':
                $query = $this->_connectTable()
                    ->where('course_id', '=', $courseId)
                    ->where('attendance_date', '=', $date)
                    ->join('students', $this->table . '.id_number', '=', 'students.indexno')
                    ->select(
                        $this->table . '.*',
                        'students.indexno',
                        'students.programmeid',
                        'students.surname',
                        'students.firstname',
                        'students.sex',
                        'students.yearofadmission',
                        'students.class1',
                        'students.telephone'
                    )
                    ->get();
                return $query;
                break;
        }
    }

    public function _save(array $payload)
    {
        return $this->_connectTable()->insertGetId($payload);
    }

    public function _saveBatch(array $payload)
    {
        $this->_connectTable()->insert($payload);
    }


    public function _update($id, array $payload)
    {
        $this->_connectTable()->where('id', '=', $id)->update($payload);
    }

    public function _delete($id)
    {
        $this->_connectTable()->delete($id);
    }
}
