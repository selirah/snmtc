<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Exeat;
use App\Models\Registration;
use Illuminate\Http\Request;
use App\Models\AuthHistory;

class ExeatController extends Controller
{

    private $_exeat;
    private $_registration;
    private $_account;
    private $_authHistory;

    public function __construct(AuthHistory $authHistory, Exeat $exeat, Registration $registration, Account $account)
    {
        $this->_exeat = $exeat;
        $this->_registration = $registration;
        $this->_account = $account;
        $this->_authHistory = $authHistory;
    }


    public function requestExeat(Request $request)
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
        $dateLeave = trim($request->input('date_leave'));
        $dateReturn = trim($request->input('date_return'));
        $reason = trim($request->input('reason'));
        $class = trim($request->input('class1'));

        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;
        $year = $academicYear - $class;

        if (empty($dateLeave) || empty($dateReturn) || empty($reason)) {
            $data = [
                'success' => true,
                'description' => 'Make sure all fields are filled'
            ];
        } elseif (strtotime($dateReturn) < strtotime($dateLeave)) {
            $data = [
                'success' => true,
                'description' => 'Make sure the return date is ahead of the leave date'
            ];
        } else {
            if ($this->_exeat->_checkExeat($username, $academicYear, $semester, $year, 'pending')) {
                $data = [
                    'success' => true,
                    'description' => 'You cannot request an Exeat when you have pending exeat to be approved'
                ];
            } else {
                $payload = [
                    'index_number' => $username,
                    'date_leaving' => date("Y-m-d", strtotime($dateLeave)),
                    'date_returning' => date("Y-m-d", strtotime($dateReturn)),
                    'reason' => $reason,
                    'status' => 'pending',
                    'academic_year' => $academicYear,
                    'semester' => $semester,
                    'year' => $year,
                    'date_created' => date('Y-m-d h:i:s'),
                    'date_updated' => date('Y-m-d h:i:s')
                ];
                $this->_exeat->_saveExeat($payload);
                $data = [
                    'success' => true,
                    'description' => 'Exeat Request Created Successfully. Wait for approval'
                ];
            }
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function exeatHistory(Request $request)
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
        $semester = trim($request->input('semester'));
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $year = ((int)$academicYear - (int)$class);

        $history = $this->_exeat->_getExeatHistory($username, $semester, $year);

        if (!$history) {
            $data = [
                'success' => true,
                'description' => 'No records found'
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $history
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }
}
