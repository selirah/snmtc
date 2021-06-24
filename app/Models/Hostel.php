<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Hostel extends Model
{
    private $_connection;
    protected $table = 'hostels';


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

    public function _update($id, array $payload)
    {
        $this->_connectTable()->where('id', '=', $id)->update($payload);
    }

    public function _delete($id)
    {
        $this->_connectTable()->delete($id);
    }

    public function _get($id)
    {
        return $this->_connectTable()->where('id', '=', $id)->first();
    }

    public function _gets()
    {
        return $this->_connectTable()->get();
    }
}
