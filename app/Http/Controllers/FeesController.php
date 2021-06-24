<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Fees;
use App\Models\Registration;
use Illuminate\Http\Request;
use App\Models\AuthHistory;

class FeesController extends Controller
{
    private $_fees;
    private $_account;
    private $_registration;
    private $_authHistory;

    public function __construct(AuthHistory $authHistory, Account $account, Fees $fees, Registration $registration)
    {
        $this->_account = $account;
        $this->_registration = $registration;
        $this->_fees = $fees;
        $this->_authHistory = $authHistory;
    }

    public function getStudentFees(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $username = trim($request->input('username'));
        $programmeId = trim($request->input('programme_id'));
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;
        $school = $this->_account->_getSchool();
        $profile = $this->_account->_getUserDetails($username);
        $gender = $profile->sex;
        $year = $academicYear - (int)$profile->class1;
        $bills = $this->_fees->_getStudentBills($semester, $academicYear, $programmeId, $gender, $year);
        $records = [
            'school' => $school,
            'bill' => $bills
        ];
        $data = [
            'success' => true,
            'description' => $records
        ];
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function billingInfo(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $username = trim($request->input('username'));
        $class = trim($request->input('class1'));
        $programmeId = trim($request->input('programme_id'));

        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;
        $year = (int)$academicYear - (int)$class;
        $student = $this->_account->_getStudentDetails($username);
        $payments = $this->_fees->_getPayments($username);
        $specificPayments = $this->_fees->_getSpecificPayments($username, $semester, $academicYear);
        $billingHistory = $this->_fees->_getBillingHistory($username);
        $semesterFees = $this->_fees->_getStudentBillSemester($username, $programmeId, $academicYear, $semester, $year);
        $amountPaidSemester = $this->_fees->_getAmountPaidSemester($username, $semester, $academicYear);
        $amountOwingSemester = $semesterFees - $amountPaidSemester;
        $totalFees = $this->_fees->_getTotalFees($username, $year, $semester);
        $amountPaidTotal = $this->_fees->_getTotalPaid($username, $year, $semester);
        $amountOwingTotal = $totalFees - $amountPaidTotal;
        $status = '';
        if ($totalFees > $amountPaidTotal) {
            $status = 'OWING';
        } elseif ($totalFees < $amountPaidTotal) {
            $status = 'OVER PAYMENT';
        } elseif ($totalFees == $amountPaidTotal) {
            $status = 'NOT OWING';
        }

        $bills = [];
        $items = [];
        if (!$billingHistory) {
            $bills = [];
        } else {
            foreach ($billingHistory as $bill) {
                $totalBill = 0;
                $billingItems = $this->_fees->_getBillingItems($bill->sem, $bill->year, $bill->acadyear, $bill->program_id, $student->sex);
                foreach ($billingItems as $it) {
                    $totalBill = $totalBill + $it->amount;
                    $items[] = [
                        'item_name' => $this->_fees->_getItemName($it->item_id)->item_name,
                        'amount' => number_format($it->amount, 2, '.', ''),
                        'total_amount' => number_format($totalBill, 2, '.', '')
                    ];
                }
                $bills[] = [
                    'bill' => $bill->amount,
                    'programme' => $this->_account->_getProgramme($bill->program_id),
                    'academic_year' => ($bill->acadyear - 1) . '-' . $bill->acadyear,
                    'year' => $bill->year,
                    'semester' => $bill->sem,
                    'date' => $bill->datetime,
                    'billing_items' => (count($items) > 0) ? $items : []
                ];
            }
        }

        $records = [
            'student' => $student,
            'semester' => $semester,
            'year' => $year,
            'semester_fees' => $semesterFees,
            'amount_paid_semester' => $amountPaidSemester,
            'amount_owing_semester' => $amountOwingSemester,
            'total_fees' => $totalFees,
            'total_fees_paid' => $amountPaidTotal,
            'total_amount_owing' => $amountOwingTotal,
            'status' => $status,
            'semester_payment' => (!$specificPayments) ? [] : $specificPayments,
            'overall_payment' => (!$payments) ? [] : $payments,
            'billing_history' => (count($bills) > 0) ? $bills : []
        ];
        $data = [
            'success' => true,
            'description' => $records
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }
}
