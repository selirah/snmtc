<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Result extends Model
{
    private $_connection;
    protected $table = 'savsoft_result';

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

    public function _saveResults(array $payload)
    {
        $query = $this->_connectTable()
            ->insertGetId($payload);
        return $query;
    }

    public function _getResult($resultId, $quizId, $userId)
    {
        $query = $this->_connectTable()
            ->where('rid', '=', $resultId)
            ->where('quid', '=', $quizId)
            ->where('uid', '=', $userId)
            ->first();
        return ($query) ? $query : false;
    }

    public function _getResultWithoutId($quizId, $userId)
    {
        $query = $this->_connectTable()
            ->where('quid', '=', $quizId)
            ->where('uid', '=', $userId)
            ->first();
        return ($query) ? $query : false;
    }

}
