<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use Carbon\Carbon;
use App\Models\HostelAttendance;

class HostelAttendanceController extends Controller
{
    private $_attendance;
    private $_registration;

    public function __construct(HostelAttendance $attendance, Registration $registration)
    {
        $this->_attendance = $attendance;
        $this->_registration = $registration;
    }

    public function saveAttendance(Request $request)
    {
        $studentId = trim($request->input('student_id'));
        $hostelId = trim($request->input('hostel_id'));
        $attendanceDate = trim($request->input('attendance_date'));
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        if (empty($studentId) || empty($hostelId) || empty($attendanceDate)) {
            $data = [
                'success' => true,
                'description' => 'All fields are required'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        // check if students data is already recorded
        $check = $this->_attendance->_getStudentAttendance($studentId, $academicYear, $semester);
        if ($check) {
            $data = [
                'success' => true,
                'description' => 'Student attendance data is already in the system'
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

        $date = $year . '/' . $month . '/' . $day;
        $time = $hour . ':' . $minute . ':' . $second;

        $payload = [
            'hostel_id' => $hostelId,
            'student_id' => $studentId,
            'attendance_date' => $date,
            'attendance_time' => $time,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $this->_attendance->_save($payload);
        $data = [
            'success' => true,
            'description' => 'Attendance details saved'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function saveAttendanceBatch(Request $request)
    {
        $attendanceData = trim($request->input('data'));
        $attendance = json_decode($attendanceData, true);
        $payload = [];
        $count = count($attendance);
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        if ($count < 1) {
            $data = [
                'success' => true,
                'description' => 'There is no data sent'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }


        for ($i = 0; $i < $count; $i++) {
            $studentId = $attendance[$i]['student_id'];
            $hostelId = $attendance[$i]['hostel_id'];
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
            $check = $this->_attendance->_getStudentAttendance($studentId, $academicYear, $semester);

            if (!$check) {
                $payload[] = [
                    'student_id' => $studentId,
                    'hostel_id' => $hostelId,
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

    public function getAttendanceList()
    {
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $list = $this->_attendance->_getAttendanceList($academicYear, $semester);

        $data = [
            'success' => true,
            'description' => $list
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getHostelAttendance($hostelId)
    {
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $list = $this->_attendance->_getHostelAttendance($hostelId, $academicYear, $semester);

        $data = [
            'success' => true,
            'description' => $list
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStudentAttendance(Request $request)
    {
        $studentId = trim($request->get('student_id'));
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $semester = $currentSettings->current_semester;

        $attendance = $this->_attendance->_getStudentAttendance($studentId, $academicYear, $semester);

        $data = [
            'success' => true,
            'description' => $attendance ? $attendance : []
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }
}
