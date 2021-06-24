<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Question extends Model
{
    private $_connection;
    protected $table = 'savsoft_qbank';

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

    public function _getQuestions()
    {
        $query = $this->_connectTable()
            ->get();

        return $query;
    }

    public function _getQuestion($questionId)
    {
        $query = $this->_connectTable()
            ->where('qid', '=', $questionId)
            ->join('courses as sc', 'sc.course_id', '=', $this->table.'.cid')
            ->join('semester as sl', 'sl.id', '=', $this->table.'.lid')
            ->first();
        return ($query) ? $query : false;
    }
}
