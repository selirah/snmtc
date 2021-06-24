<?php

namespace App\Http\Controllers;

use App\Models\Academics;
use App\Models\Registration;
use Illuminate\Http\Request;
use App\Models\AuthHistory;

class AcademicsController extends Controller
{
    private $_academics;
    private $_registration;
    private $_authHistory;

    public function __construct(AuthHistory $authHistory, Academics $academics, Registration $registration)
    {
        $this->_academics = $academics;
        $this->_registration = $registration;
        $this->_authHistory = $authHistory;
    }

    public function getStudentResults(Request $request)
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
        $semester = trim($request->input('semester'));
        $programmeId = trim($request->input('programme_id'));
        $semesterResults = $this->_academics->_getStudentResults($username, $programmeId, $semester);
        $results = [];
        $total_credits = 0;
        $total_wgp = 0;
        if (!$semesterResults) {
            $data = [
                'success' => true,
                'description' => 'There are no results found for this semester'
            ];
        } else {
            foreach ($semesterResults as $semesterResult) {
                $total_wgp += $semesterResult->weighted_gp;
                $total_credits += $semesterResult->credit;
                $results[] = [
                    'course_code' => $semesterResult->course_code,
                    'course_name' => $semesterResult->course_name,
                    'credit' => $semesterResult->credit,
                    'marks' => number_format($semesterResult->marks, 2, '.', ''),
                    'grade' => $semesterResult->grade,
                    'remark' => $this->_academics->_gradeSystem($semesterResult->grade)->remark,
                    'grade_point' => $semesterResult->grade_point,
                    'weighted_gp' => $semesterResult->weighted_gp
                ];
            }

            $RESULTS = [
                'breakdown' => $results,
                'total_credits' => $total_credits,
                'total_wgp' => $total_wgp,
                'semester_gpa' => number_format($total_wgp / $total_credits, 2, '.', '')
            ];

            $data = [
                'success' => true,
                'description' => $RESULTS
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

}
