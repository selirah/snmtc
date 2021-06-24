<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Biometric extends Model
{
    private $_connection;
    protected $table = 'biometric';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_connection = DB::connection('mysql_ntc');
    }

    private function _connectTable()
    {
        $table = $this->_connection->table($this->table);
        return $table;
    }

    public function _getBiometricData($idNumber, $type)
    {
        switch ($type) {
            case 'staff':
                $query = $this->_connectTable()
                    ->where('id_number', '=', $idNumber)
                    ->join('staff', $this->table . '.id_number', '=', 'staff.staff_id')
                    ->select($this->table . '.*', 'staff.id as _id', 'staff.staff_id', 'staff.firstname', 'staff.lastname', 'staff.phone_number', 'staff.email_address',
                        'staff.gender')
                    ->first();
                return $query;
                break;
            case 'students':
                $query = $this->_connectTable()
                    ->where('id_number', '=', $idNumber)
                    ->join('students', $this->table . '.id_number', '=', 'students.indexno')
                    ->select($this->table . '.*', 'students.indexno', 'students.programmeid', 'students.surname', 'students.firstname', 'students.sex',
                        'students.yearofadmission', 'students.class1', 'students.telephone')
                    ->first();
                return $query;
                break;
        }
    }

    public function _getBiometric($type)
    {
        switch ($type) {
            case 'staff':
                $query = $this->_connectTable()
                    ->where('type', '=', 1)
                    ->join('staff', $this->table . '.id_number', '=', 'staff.staff_id')
                    ->select($this->table . '.*', 'staff.id as _id', 'staff.staff_id', 'staff.firstname', 'staff.lastname', 'staff.phone_number', 'staff.email_address',
                        'staff.gender')
                    ->get();
                return $query;
                break;
            case 'students':
                $query = $this->_connectTable()
                    ->where('type', '=', 2)
                    ->join('students', $this->table . '.id_number', '=', 'students.indexno')
                    ->select($this->table . '.*', 'students.indexno', 'students.programmeid', 'students.surname', 'students.firstname', 'students.sex',
                        'students.yearofadmission', 'students.class1', 'students.telephone')
                    ->get();
                return $query;
                break;
        }
    }

    public function _save(array $payload)
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
