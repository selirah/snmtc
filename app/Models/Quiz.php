<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Quiz extends Model
{
    private $_connection;
    protected $table = 'savsoft_quiz';

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

    public function _getQuizList()
    {
        $query = $this->_connectTable()
            ->orderByDesc('quid')
            ->get();
        return $query;
    }

    public function _getQuiz($quizId)
    {
        $query = $this->_connectTable()
            ->where('quid', '=', $quizId)
            ->first();
        return ($query) ? $query : false;
    }
}
