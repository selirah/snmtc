<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Academics extends Model
{
    private $_db;

    public function __construct()
    {
        parent::__construct();
        $this->_db = DB::connection('mysql_ntc');
    }


    public function _getStudentResults($username, $programmeId, $semester)
    {
        $query = $this->_db->table('academics')
            ->where('index_number', '=', $username)
            ->where('programme_id', '=', $programmeId)
            ->where('semester', '=', $semester)
            ->get();

        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _gradeSystem($grade)
    {
        $query = $this->_db->table('gradingsystem_scoring')
            ->where('grade', '=', $grade)
            ->first();
        return $query;
    }
}
