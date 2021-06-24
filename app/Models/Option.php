<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Option extends Model
{
    private $_connection;
    protected $table = 'savsoft_options';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_connection = DB::connection('mysql_ntc');
    }

    private function _connectTable()
    {
        return $this->_connection->table($this->table);
    }

    public function _getOptions($questionId)
    {
        return $this->_connectTable()
            ->where('qid', '=', $questionId)
            ->get();
    }

    public function _getScore($questionId, $optionId)
    {
        return $this->_connectTable()
            ->where('oid', '=', $optionId)
            ->where('qid', '=', $questionId)
            ->first();
    }
}
