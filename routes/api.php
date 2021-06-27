<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth', [
    'uses' => 'AccountController@authenticate'
]);
Route::post('/reset-password', [
    'uses' => 'AccountController@passwordReset'
]);
Route::post('/image', [
    'uses' => 'AccountController@updateImage'
]);
Route::post('/auth/staff', [
    'uses' => 'AccountController@authenticateStaff'
]);
Route::get('/students', [
    'uses' => 'AccountController@getStudents'
]);
Route::get('/staff', [
    'uses' => 'AccountController@getStaff'
]);
Route::get('/staff-courses', [
    'uses' => 'AccountController@getStaffCourses'
]);
Route::get('/course-students', [
    'uses' => 'AccountController@getCourseStudents'
]);
Route::get('/student-courses', [
    'uses' => 'AccountController@getStudentCourses'
]);
Route::get('/auth/logout', [
    'uses' => 'AccountController@logout'
]);

/**
 * ROUTE FOR REGISTRATION CONTROLLER
 */
Route::post('/registration', [
    'uses' => 'RegistrationController@semesterRegistration'
]);
Route::post('/register-student', [
    'uses' => 'RegistrationController@registerStudent'
]);
Route::post('/delete-registration', [
    'uses' => 'RegistrationController@deleteStudentRegistration'
]);

/**
 * ROUTE FOR FEES CONTROLLER
 */
Route::post('/fees', [
    'uses' => 'FeesController@getStudentFees'
]);
Route::post('/bill-info', [
    'uses' => 'FeesController@billingInfo'
]);

/**
 * ROUTE FOR ACADEMICS CONTROLLER
 */
Route::post('/get-results', [
    'uses' => 'AcademicsController@getStudentResults'
]);

/**
 * ROUTE FOR EXEAT CONTROLLER
 */
Route::post('/request-exeat', [
    'uses' => 'ExeatController@requestExeat'
]);

Route::post('/exeat-history', [
    'uses' => 'ExeatController@exeatHistory'
]);

/**
 * ROUTE FOR BIOMETRIC
 */
Route::post('/biometric', [
    'uses' => 'BiometricController@save'
]);

Route::post('/biometric/save-batch', [
    'uses' => 'BiometricController@saveBatch'
]);

Route::put('/biometric/{id}', [
    'uses' => 'BiometricController@update'
]);

Route::get('/biometric', [
    'uses' => 'BiometricController@getAll'
]);

Route::get('/biometric/get-first', [
    'uses' => 'BiometricController@getFirst'
]);

Route::delete('/biometric/{id}', [
    'uses' => 'BiometricController@delete'
]);

Route::get('/biometric/get-students', [
    'uses' => 'BiometricController@getStudents'
]);

Route::get('/biometric/get-staff', [
    'uses' => 'BiometricController@getStaff'
]);

/**
 * ROUTE FOR ATTENDANCE
 */
Route::post('/attendance', [
    'uses' => 'AttendanceController@save'
]);

Route::post('/attendance/save-batch', [
    'uses' => 'AttendanceController@saveBatch'
]);


Route::get('/attendance', [
    'uses' => 'AttendanceController@getAll'
]);

Route::get('/attendance/get-individual', [
    'uses' => 'AttendanceController@getFirst'
]);

Route::get('/attendance/get-course', [
    'uses' => 'AttendanceController@getFirst'
]);

/**
 * ROUTE FOR ASSESSMENT
 */
Route::get('/quiz', [
    'uses' => 'AssessmentController@quizList'
]);
Route::get('/quiz/attempt', [
    'uses' => 'AssessmentController@attemptQuiz'
]);
Route::post('/quiz/save', [
    'uses' => 'AssessmentController@saveAnswers'
]);
Route::get('/quiz/result', [
    'uses' => 'AssessmentController@getQuizResult'
]);



/**
 * ROUTE FOR HOSTEL
 */
Route::post('/hostels', [
    'uses' => 'HostelController@addHostel'
]);

Route::put('/hostels/{id}', [
    'uses' => 'HostelController@updateHostel'
]);

Route::get('/hostels', [
    'uses' => 'HostelController@getHostels'
]);

Route::delete('/hostels/{id}', [
    'uses' => 'HostelController@deleteHostel'
]);

/**
 * ROUTE FOR HOSTEL ATTENDANCE
 */
Route::get('/attendance-list', [
    'uses' => 'HostelAttendanceController@getAttendanceList'
]);

Route::get('/hostel-attendance/{hostelId}', [
    'uses' => 'HostelAttendanceController@getHostelAttendance'
]);

Route::get('/student-attendance', [
    'uses' => 'HostelAttendanceController@getStudentAttendance'
]);

Route::post('/hostel-attendance', [
    'uses' => 'HostelAttendanceController@saveAttendance'
]);

Route::post('/hostel-attendance/save-batch', [
    'uses' => 'HostelAttendanceController@saveAttendanceBatch'
]);

/**
 * ROUTE FOR CHURCH ATTENDANCE
 */
Route::post('/church-attendance', [
    'uses' => 'ChurchAttendanceController@saveAttendance'
]);

Route::post('/church-attendance/save-batch', [
    'uses' => 'ChurchAttendanceController@saveAttendanceBatch'
]);


Route::get('/church-attendance', [
    'uses' => 'ChurchAttendanceController@getAttendanceList'
]);

Route::get('/church-attendance/get-individual', [
    'uses' => 'ChurchAttendanceController@getStudentStaffAttendance'
]);
