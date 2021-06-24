<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Exeat extends Model
{
    private $_db;

    public function __construct(Registration $registration)
    {
        parent::__construct();
        $this->_db = DB::connection('mysql_ntc');
    }

    public function _checkExeat($username, $academicYear, $semester, $year, $status='pending')
    {
        $exeat = $this->_db->table('exeat')->where('index_number', '=', $username)
            ->where('academic_year', '=', $academicYear)
            ->where('semester', '=', $semester)
            ->where('year', '=', $year)
            ->where('status', '=', $status)
            ->first();
        return ($exeat) ? true : false;
    }

    public function _saveExeat(array $payload)
    {
        $this->_db->table('exeat')->insert($payload);
    }

    public function _getExeatHistory($username, $semester, $year)
    {
        $query = $this->_db->table('exeat')->where('index_number', '=', $username)
            ->where('semester', '=', $semester)
            ->where('year', '=', $year)
            ->get();
        return ($query->isNotEmpty()) ? $query : false;
    }
}
