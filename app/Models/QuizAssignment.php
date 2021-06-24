<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuizAssignment extends Model
{
    private $_connection;
    protected $table = 'assessing_student';

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

    public function _checkQuizAvailability($quizId, $indexNumber, $status = 0)
    {
        $query = $this->_connectTable()
            ->where('quiz_id', '=', $quizId)
            ->where('student_id', '=', $indexNumber)
            ->where('status', '=', $status)
            ->where('start_time', '<', time())
            ->where('end_time', '>', time())
            ->first();
        return ($query) ? true : false;
    }

    public function _fetchWrittenQuiz($indexNumber, $status = 1)
    {
        $query = $this->_connectTable()
            ->where('student_id', '=', $indexNumber)
            ->where('status', '=', $status)
            ->get();
        return $query;
    }


    public function _update($studentId, $quizId, $payload)
    {
        $this->_connectTable()
            ->where('student_id', '=', $studentId)
            ->where('quiz_id', '=', $quizId)
            ->update($payload);
    }

}
