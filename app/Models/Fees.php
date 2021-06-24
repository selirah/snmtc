<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Fees extends Model
{
    private $_db;

    public function __construct()
    {
        parent::__construct();
        $this->_db = DB::connection('mysql_ntc');
    }

    public function _getStudentBills($semester, $academicYear, $programmeId, $gender, $year)
    {
        $query = $this->_db->table('billing')
                ->join('fees_item', 'fees_item.item_id', '=', 'billing.item_id')
                ->where('semester', '=', $semester)
                ->where('academic_year', '=', $academicYear)
                ->where('program_id', '=', $programmeId)
                ->where('sex', '=', $gender)
                ->where('year', '=', $year)
                ->get();
        return $query;
    }

    public function _getPayments($username)
    {
        $query = $this->_db->table('students')
                 ->join('payments', 'students.indexno', '=', 'payments.indexno')
                 ->where('students.indexno', '=', $username)
                 ->get();
        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _getSpecificPayments($username, $semester, $academicYear)
    {
        $query = $this->_db->table('payments')
            ->where('indexno', '=',  $username)
            ->where('semester', '=', $semester)
            ->where('academic_year', '=', $academicYear)
            ->get();
        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _getStudentBillSemester($username, $programmeId, $academicYear, $semester, $year)
    {
        $query = $this->_db->table('student_bills')
            ->where('indexno', '=', $username)
            ->where('program_id', '=', $programmeId)
            ->where('acadyear', '=', $academicYear)
            ->where('year', '=', $year)
            ->where('sem', '=', $semester)
            ->sum('amount');
//        $amount = 0;
//        foreach ($query as $q) {
//            $amount = $amount + $q->amount;
//        }
        return $query;
    }

    public function _getAmountOwing($username, $semester, $academicYear)
    {
        $query = $this->_db->table('debts')
            ->where('indexno', '=', $username)
            ->where('semester', '=', $semester)
            ->where('academic_year', '=', $academicYear)
            ->first();
        return $query->amount_owing;
    }

    public function _getAmountPaidSemester($username, $semester, $academicYear)
    {
        $query = $this->_db->table('payments')
            ->where('indexno', '=', $username)
            ->where('semester', '=', $semester)
            ->where('academic_year', '=', $academicYear)
            ->get();
        $amount = 0;
        foreach ($query as $q) {
            $amount = $amount + $q->amount_paid;
        }

        return $amount;
    }

    public function _getBillingHistory($username)
    {
        $query = $this->_db->table('student_bills')
            ->where('indexno', '=', $username)
            ->orderByDesc('year')
            ->orderByDesc('sem')
            ->get();
        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _getTotalFees($username, $year, $semester)
    {
        $yearsSquared = ($year * $year) + $semester;
        $query = $this->_db->table('student_bills')
            ->where('indexno', '=', $username)
            ->where('yrsquaredplussem', '<=', $yearsSquared)
            ->get();
        $amount = 0;
        foreach ($query as $q) {
            $amount = $amount + $q->amount_paid;
        }
        return $amount;
    }

    public function _getTotalPaid($username, $year, $semester)
    {
        $yearsSquared = ($year * $year) + $semester;
        $query = $this->_db->table('payments')
            ->where('indexno', '=', $username)
            ->where('yrsquaredplussem', '<=', $yearsSquared)
            ->get();
        $amount = 0;
        foreach ($query as $q) {
            $amount = $amount + $q->amount_paid;
        }
        return $amount;
    }

    public function _getBillingItems($semester, $year, $academicYear, $programmeId, $gender)
    {
        $query = $this->_db->table('billing')
            ->where('semester', '=', $semester)
            ->where('year', '=', $year)
            ->where('academic_year', '=', $academicYear)
            ->where('program_id', '=', $programmeId)
            ->where('sex', '=', $gender)
            ->get();
        return ($query->isNotEmpty()) ? $query : false;
    }

    public function _getItemName($itemId)
    {
        $query = $this->_db->table('fees_item')->where('item_id', '=', $itemId)->first();
        return $query;
    }
}
