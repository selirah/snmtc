<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Registration;
use Illuminate\Http\Request;
use App\Models\AuthHistory;

class RegistrationController extends Controller
{
    private $_registration;
    private $_account;
    private $_authHistory;

    public function __construct(AuthHistory $authHistory, Registration $registration, Account $account)
    {
        $this->_registration = $registration;
        $this->_account = $account;
        $this->_authHistory = $authHistory;
    }

    public function semesterRegistration(Request $request)
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

        $currentSettings = $this->_registration->_currentSettings();
        $student = $this->_account->_getStudentDetails($username);

        $programmeId = $student->programmeid;
        $year = ((int)$currentSettings->current_acadyear - (int)$student->class1);
        $semester = $currentSettings->current_semester;
        $academicYear = $currentSettings->current_acadyear;

        $checkFeesPaid = $this->_account->checkFeesPaid($username, $semester, $academicYear, $year, $programmeId);
        $checkClearance = $this->_account->_checkClearance($username, $semester, $academicYear);
        $checkBill = $this->_account->_checkBill($semester, $academicYear, $programmeId, $year);
        $startDate = $currentSettings->regn_start_date;
        $endDate = $currentSettings->regn_end_date;
        $today = date('Y-m-d');
        $courses = $this->_registration->_getCourses($programmeId, $year, $semester);
        $trailedCourses = $this->_registration->_getTrailedCourses($username, $programmeId);
        $registrationCount = $this->_registration->_registrationCount($username, $programmeId, $academicYear, $year, $semester);

        $records = [
            'is_fees_paid' => $checkFeesPaid,
            'is_clearance' => $checkClearance,
            'is_bill' => $checkBill,
            'academic_year' => $academicYear,
            'year' => $year,
            'semester' => $semester,
            'courses' => $courses,
            'trails' => (!$trailedCourses) ? [] : $trailedCourses,
            'registration_count' => $registrationCount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'current_date' => $today
        ];

        $data = [
            'success' => true,
            'description' => $records
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function registerStudent(Request $request)
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

        $currentSettings = $this->_registration->_currentSettings();
        $student = $this->_account->_getStudentDetails($username);

        $programmeId = $student->programmeid;
        $year = ((int)$currentSettings->current_acadyear - (int)$student->class1);
        $semester = $currentSettings->current_semester;
        $academicYear = $currentSettings->current_acadyear;
        $courses = $this->_registration->_getCourses($programmeId, $year, $semester);
        $trailedCourses = $this->_registration->_getTrailedCourses($username, $programmeId);
        $courseData = [];
        $trailedData = [];
        foreach ($courses as $course) {
            $courseData[] = [
                'indexno' => $username,
                'programme_id' =>$programmeId,
                'class1' => ($academicYear - $year),
                'acadyear' => $academicYear,
                'yr' => $year,
                'sem' => $semester,
                'acadyear2' => $academicYear,
                'yr2' => $year,
                'sem2' => $semester,
                'course' => $course->course,
                'code' => $course->code,
                'coursename' => $course->course_name,
                'credithrs' => $course->credit_hrs,
                'online' => 'online',
                'yrsquaredplussem' => ($year * $year) + $semester
            ];
        }
        $this->_registration->_saveCourseRegistrationOrTrails($courseData);
        $registrationHistory = [
            'indexno' => $username,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'year' => $year,
            'date_created' => date('Y-m-d H:i:s'),
            'programme_id' => $programmeId
        ];

        $this->_registration->_saveRegistrationHistory($registrationHistory);

        if ($trailedCourses != false) {
            if (count($trailedCourses) > 0) {
                foreach ($trailedCourses as $trailedCourse) {
                    $trailedData[] = [
                        'indexno' => $username,
                        'programme_id' => $programmeId,
                        'class1' => ($academicYear - $year),
                        'acadyear' => $academicYear,
                        'yr' => $year,
                        'sem' => $semester,
                        'acadyear2' => $academicYear,
                        'yr2' => $year,
                        'sem2' => $semester,
                        'course' => $trailedCourse->course,
                        'code' => $trailedCourse->code,
                        'coursename' => $trailedCourse->coursename,
                        'credithrs' => $trailedCourse->credithrs,
                        'online' => 'online',
                        'yrsquaredplussem' => ($year * $year) + $semester
                    ];
                }
                $this->_registration->_saveCourseRegistrationOrTrails($trailedData);
            }
        }
        $data = [
            'success' => true,
            'description' => 'Registration successful'
        ];
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function deleteStudentRegistration(Request $request)
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
        $year = $academicYear - $class;
        $semester = $currentSettings->current_semester;
        $this->_registration->_deleteStudentRegistration($username, $programmeId, $academicYear, $year, $semester);
        $this->_registration->_deleteRegistrationHistory($username, $academicYear, $semester, $year);
        $data = [
            'success' => true,
            'description' => 'Registration deleted successfully'
        ];
        return response()->json([
            'data' => $data
        ], 200);
    }
}
