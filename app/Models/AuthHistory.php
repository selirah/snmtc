<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuthHistory extends Model
{
    private $_db;
    protected $table = 'auth_history';


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_db = DB::connection('mysql_ntc');
    }

    public function _saveAuthHistory(array $payload)
    {
        $this->_db->table($this->table)
            ->insert($payload);
    }

    public function _isAuthExpired($idNumber, $token)
    {
        $query = $this->_db->table($this->table)
            ->where('id_number', '=', $idNumber)
            ->where('token', '=', $token)
            ->where('expiry', '<=', strtotime(date('Y-m-d H:i:s')))
            ->first();
        return ($query) ? true : false;
    }

    public function _expireAuth($idNumber, $token, array $payload)
    {
        $this->_db->table($this->table)
            ->where('staff_id', '=', $idNumber)
            ->where('token', '=', $token)
            ->update($payload);
    }

    public function _getAuthUserId($token)
    {
        $query = $this->_db->table($this->table)
            ->where('token', '=', $token)
            ->first();

        return ($query) ? $query : false;
    }

    public function _revokeAuth($token, array $payload)
    {
        $this->_db->table($this->table)
            ->where('token', '=', $token)
            ->update($payload);
    }

    public function _checkLoggedIn($idNumber, $loggedIn = 1, $isExpired = 0)
    {
        $query = $this->_db->table($this->table)
            ->where('id_number', '=', $idNumber)
            ->where('is_expired', '=', $isExpired)
            ->where('is_logged_in', '=', $loggedIn)
            ->first();
        return ($query) ? true : false;
    }

}
