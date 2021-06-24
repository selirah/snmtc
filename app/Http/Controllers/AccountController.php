<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AuthHistory;
use App\Models\Registration;
use App\Misc\Helper;
use Carbon\Carbon;

class AccountController extends Controller
{
    private $_account;
    private $_registration;
    private $_attendance;
    private $_authHistory;

    public function __construct(Account $account, AuthHistory $authHistory, Registration $registration, Attendance $attendance)
    {
        $this->_account = $account;
        $this->_registration = $registration;
        $this->_attendance = $attendance;
        $this->_authHistory = $authHistory;
    }

    public function authenticate(Request $request)
    {
        $username = trim($request->input('username'));       /////// Get User Details
        $password = trim($request->input('password'));

        if (empty($username) || empty($password)) {
            return response()->json([
                'success' => true,
                'description' => 'Username and password required'
            ], 200);
        }

        $auth = $this->_account->_auth($username, $password);   ///////////// Check if user exist
        if ($auth) {

            $user = $this->_account->_getUserDetails($username);
            $currentSettings = $this->_registration->_currentSettings();
            if ($user->status == 'ACTIVE') {
                // Check is user is already logged in
                $check = $this->_authHistory->_checkLoggedIn($username);
                if ($check) {
                    return response()->json([
                        'success' => true,
                        'description' => 'You are logged in on another device.'
                    ], 200);
                }

                // generate random token and save
                $token = Helper::generateRandomToken();
                $expiry = strtotime(date("Y-m-d H:i:s", strtotime('+24 hours')));
                $authHistory = [
                    'id_number' => $username,
                    'token' => $token,
                    'expiry' => $expiry,
                    'is_expired' => 0,
                    'is_logged_in' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                $this->_authHistory->_saveAuthHistory($authHistory);
                $profile = [
                    'username' => $user->indexno,
                    'firstname' => $user->firstname,
                    'surname' => $user->surname,
                    'programme_id' => $user->programmeid,
                    'programme' => $this->_account->_getProgramme($user->programmeid)->programme,
                    'class1' => $user->class1,
                    'gender' => $user->sex,
                    'phone' => $user->telephone,
                    'faculty' => $this->_account->_getFaculty($user->facid)->faculty,
                    'department' => $this->_account->_getDepartment($user->deptid)->department_name,
                    'image' => $user->picture,
                    'is_registered' => $currentSettings->register_students,
                ];
                $data = [
                    'success' => true,
                    'description' => [
                        'token' => 'Bearer: ' . $token,
                        'expiry' => $expiry,
                        'profile' => $profile
                    ]
                ];
            } else {
                $data = [
                    'success' => true,
                    'description' => 'You are not yet activated to use the app'
                ];
            }
        } else {
            $data = [
                'success' => true,
                'description' => 'Invalid login credentials'
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function authenticateStaff(Request $request)
    {
        $username = trim($request->input('username'));       /////// Get User Details
        $password = trim($request->input('password'));

        if (empty($username) || empty($password)) {
            return response()->json([
                'success' => true,
                'description' => 'Username and password required'
            ], 200);
        }

        $auth = $this->_account->_authStaff($username, $password);   ///////////// Check if user exist
        if ($auth) {

            // Check is user is already logged in
            $check = $this->_authHistory->_checkLoggedIn($username);
            if ($check) {
                return response()->json([
                    'success' => true,
                    'description' => 'You are logged in on another device.'
                ], 200);
            }

            // generate random token and save
            $token = Helper::generateRandomToken();
            $expiry = strtotime(date("Y-m-d H:i:s", strtotime('+24 hours')));
            $authHistory = [
                'id_number' => $username,
                'token' => $token,
                'expiry' => $expiry,
                'is_expired' => 0,
                'is_logged_in' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),

            ];
            $this->_authHistory->_saveAuthHistory($authHistory);

            $user = $this->_account->_getStaffDetails($username);
            $school = $this->_account->_getSchool();
            $institution = [
                'name' => $school->name,
                'address' => $school->address,
                'logo' => 'http://students.sdantc.edu.gh/assets/img/' . $school->logo,
                'phone' => $school->phone,
                'email' => $school->email
            ];
            $profile = [
                'id' => $user->id,
                'username' => $user->staff_id,
                'firstname' => $user->firstname,
                'surname' => $user->lastname,
                'school' => $institution,
                'gender' => $user->gender,
                'phone' => $user->phone_number,
                'image' => $user->picture
            ];
            $data = [
                'success' => true,
                'description' => [
                    'token' => 'Bearer: ' . $token,
                    'expiry' => $expiry,
                    'profile' => $profile
                ]
            ];
        } else {
            $data = [
                'success' => true,
                'description' => 'Invalid login credentials'
            ];
        }
        return response()->json([
            'data' => $data
        ], 200);
    }


    public function passwordReset(Request $request)
    {
        $username = trim($request->input('username'));
        $type = trim($request->input('type'));
        $data = [];

        switch ($type) {
            case 'student':
                $user = $this->_account->_getUserDetails($username);
                if ($user) {    ///////// Check if user exist
                    $fname = $user->firstname;
                    $phone = $user->telephone;
                    $password =  substr(md5(time()), 10, 6);

                    $this->_account->_updatePassword($username, $password);  ////// Update password

                    $message = 'Hi ' . $fname . ', Your new password is ' . $password . '. You can login and change this Password to what you prefer. Thank you.';
                    Helper::sendSMS($phone, $message, 'SDANMTC');    /////////// Send SMS to User with new password
                    $data = [
                        'success' => true,
                        'description' => 'Password reset successfully. SMS has been sent to you'
                    ];
                } else {
                    $data = [
                        'success' => true,
                        'description' => 'Username entered does not exist'
                    ];
                }
                break;
            case 'staff':
                $user = $this->_account->_getStaffDetails($username);
                if ($user) {    ///////// Check if user exist
                    $fname = $user->firstname;
                    $phone = $user->phone_number;
                    $password =  substr(md5(time()), 10, 6);

                    $this->_account->_updateStaffPassword($username, $password);  ////// Update password

                    $message = 'Hi ' . $fname . ', Your new password is ' . $password . '. You can login and change this Password to what you prefer. Thank you.';
                    Helper::sendSMS($phone, $message, 'SDANMTC');    /////////// Send SMS to User with new password
                    $data = [
                        'success' => true,
                        'description' => 'Password reset successfully. SMS has been sent to you'
                    ];
                } else {
                    $data = [
                        'success' => true,
                        'description' => 'Username entered does not exist'
                    ];
                }
                break;
        }
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function updateImage(Request $request)
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
        $type = trim($request->input('type'));
        $image = trim($request->input('image'));
        $data = [];

        switch ($type) {
            case 'student':
                $this->_account->_updateImage($username, $image);
                $data = [
                    'success' => true,
                    'description' => 'Image saved successfully'
                ];
                break;
            case 'staff':
                $this->_account->_updateStaffImage($username, $image);
                $data = [
                    'success' => true,
                    'description' => 'Image saved successfully'
                ];
                break;
        }
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStudents(Request $request)
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

        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $students = $this->_account->_getStudents($academicYear);

        if ($students->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $students
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStaff(Request $request)
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

        $staff = $this->_account->_getStaff();

        if ($staff->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $staff
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStaffCourses(Request $request)
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

        $staffId = trim($request->get('id'));
        $staffCourses = $this->_account->_getStaffCourses($staffId);
        $payload = [];

        if ($staffCourses->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {

            foreach ($staffCourses as $course) {
                $courseDetail = $this->_account->_getCourse($course->course_id);
                $payload[] = $courseDetail;
            }

            $data = [
                'success' => true,
                'description' => $payload
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getCourseStudents(Request $request)
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

        $courseCode = trim($request->get('course_code'));
        $attendanceId = trim($request->get('attendance_id'));
        $courseSplit = explode(' ', $courseCode);
        $course = $courseSplit[0];
        $code = $courseSplit[1];
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $students = $this->_account->_getCourseStudents($course, $code, $academicYear, $semester);

        if ($students->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $students
            ];
        }

        $payload = [
            'course_id' => $courseCode
        ];

        $this->_attendance->_update($attendanceId, $payload);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStudentCourses(Request $request)
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

        $indexNumber = trim($request->get('index_number'));
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $courses = $this->_account->_getStudentCourses($indexNumber, $academicYear, $semester);

        if ($courses->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $courses
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function logout(Request $request)
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
        $authHistory = [
            'is_expired' => 1,
            'is_logged_in' => 0
        ];

        $this->_authHistory->_revokeAuth($token, $authHistory);

        $response = [
            'success' => true,
            'description' => 'Successfully logged out'
        ];

        return response()->json($response, 200);
    }
}
