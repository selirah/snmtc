<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Registration;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private $_attendance;
    private $_registration;


    public function __construct(Attendance $attendance, Registration $registration)
    {
        $this->_attendance = $attendance;
        $this->_registration = $registration;
    }

    public function save(Request $request)
    {
        $idNumber = trim($request->input('id_number'));
        $type = trim($request->input('type'));
        $courseId = trim($request->input('course_id'));
        $attendanceDate = trim($request->input('attendance_date'));

        if (empty($idNumber) || empty($type) || empty($attendanceDate)) {
            $data = [
                'success' => true,
                'description' => 'All fields are required'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        $dateSplit = date_parse_from_format('Y-m-d H:i:s', $attendanceDate);
        $year = $dateSplit['year'];
        $month = (strlen($dateSplit['month']) > 1) ? $dateSplit['month'] : '0' . $dateSplit['month'];
        $day = (strlen($dateSplit['day']) > 1) ? $dateSplit['day'] : '0' . $dateSplit['day'];
        $hour = (strlen($dateSplit['hour']) > 1) ? $dateSplit['hour'] : '0' . $dateSplit['hour'];
        $minute = (strlen($dateSplit['minute']) > 1) ? $dateSplit['minute'] : '0' . $dateSplit['minute'];
        $second = (strlen($dateSplit['second']) > 1) ? $dateSplit['second'] : '0' . $dateSplit['second'];


        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $date = $year . '/' . $month . '/' . $day;
        $time = $hour . ':' . $minute . ':' . $second;


        $payload = [
            'id_number' => $idNumber,
            'type' => (int)$type,
            'course_id' => ($type == '2') ? $courseId : NULL,
            'attendance_date' => $date,
            'attendance_time' => $time,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $id = $this->_attendance->_save($payload);

        if ($type == '1') {
            $data = [
                'success' => true,
                'description' => [
                    'message' => 'Attendance details saved',
                    'attendance_id' => $id
                ],
            ];
        } else {
            $data = [
                'success' => true,
                'description' => 'Attendance details saved',
            ];
        }


        return response()->json([
            'data' => $data
        ], 201);
    }

    public function saveBatch(Request $request)
    {
        $attendanceData = trim($request->input('data'));
        $attendance = json_decode($attendanceData, true);
        $payload = [];
        $count = count($attendance);

        if ($count < 1) {
            $data = [
                'success' => true,
                'description' => 'There is no data sent'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        for ($i = 0; $i < $count; $i++) {
            $idNumber = $attendance[$i]['id_number'];
            $type = $attendance[$i]['type'];
            $courseId = $attendance[$i]['course_id'];
            $attendanceDate = $attendance[$i]['attendance_date'];

            $dateSplit = date_parse_from_format('Y-m-d H:i:s', $attendanceDate);
            $year = $dateSplit['year'];
            $month = (strlen($dateSplit['month']) > 1) ? $dateSplit['month'] : '0' . $dateSplit['month'];
            $day = (strlen($dateSplit['day']) > 1) ? $dateSplit['day'] : '0' . $dateSplit['day'];
            $hour = (strlen($dateSplit['hour']) > 1) ? $dateSplit['hour'] : '0' . $dateSplit['hour'];
            $minute = (strlen($dateSplit['minute']) > 1) ? $dateSplit['minute'] : '0' . $dateSplit['minute'];
            $second = (strlen($dateSplit['second']) > 1) ? $dateSplit['second'] : '0' . $dateSplit['second'];

            $date = $year . '/' . $month . '/' . $day;
            $time = $hour . ':' . $minute . ':' . $second;

            // check if attendance data exist
            $check = $this->_attendance->_checkAttendance($idNumber, $courseId, $date);

            if (!$check) {
                $payload[] = [
                    'id_number' => $idNumber,
                    'type' => (int)$type,
                    'course_id' => $courseId,
                    'attendance_date' => $date,
                    'attendance_time' => $time,
                    'academic_year' => $academicYear,
                    'semester' => $semester,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
        }
        $this->_attendance->_saveBatch($payload);

        $data = [
            'success' => true,
            'description' => 'Attendance Data saved'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function getAll(Request $request)
    {
        $type = trim($request->get('type'));
        $date = trim($request->get('date'));
        $attendanceData = $this->_attendance->_getAttendance($type, $date);

        $data = [
            'success' => true,
            'description' => $attendanceData
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getFirst(Request $request)
    {
        $idNumber = trim($request->get('id_number'));
        $type = trim($request->get('type'));
        $date = trim($request->get('date'));
        $attendanceData = $this->_attendance->_getAttendanceData($idNumber, $type, $date);

        $data = [
            'success' => true,
            'description' => $attendanceData
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getWithCourseId(Request $request)
    {
        $courseId = trim($request->get('course_id'));
        $type = trim($request->get('type'));
        $date = trim($request->get('date'));
        $attendanceData = $this->_attendance->_getAttendanceWithCourseId($courseId, $type, $date);

        if ($attendanceData->isEmpty()) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $attendanceData
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }
}
