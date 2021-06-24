<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    private $_db;
    private $_registration;

    public function __construct(Registration $registration)
    {
        parent::__construct();
        $this->_db = DB::connection('mysql_ntc');
        $this->_registration = $registration;
    }

    public function _auth($username, $password)
    {
        $user = $this->_db->table('students')->where('indexno', '=', $username)->where('pass', '=', md5($password))->first();
        return ($user) ? true : false;
    }

    public function _authStaff($username, $password)
    {
        $user = $this->_db->table('staff')->where('staff_id', '=', $username)->where('password', '=', md5($password))->first();
        return ($user) ? true : false;
    }

    public function _getUserDetails($username)
    {
        $user = $this->_db->table('students')->where('indexno', '=', $username)->first();
        return $user;
    }

    public function _getStaffDetails($username)
    {
        $user = $this->_db->table('staff')->where('staff_id', '=', $username)->first();
        return $user;
    }

    public function _getProgramme($programmeId)
    {
        $programme = $this->_db->table('programmes')->where('programme_id', '=', $programmeId)->first();
        return $programme;
    }

    public function _getFaculty($facultyId)
    {
        $faculty = $this->_db->table('faculty')->where('faculty_id', '=', $facultyId)->first();
        return $faculty;
    }

    public function _getDepartment($departmentId)
    {
        $faculty = $this->_db->table('department')->where('department_id', '=', $departmentId)->first();
        return $faculty;
    }

    public function _updatePassword($username, $password)
    {
        $this->_db->table('students')->where('indexno', '=', $username)->update(['pass' => md5($password)]);
    }

    public function _updateStaffPassword($username, $password)
    {
        $this->_db->table('staff')->where('staff_id', '=', $username)->update(['password' => md5($password)]);
    }

    public function _updateImage($username, $image)
    {
        $this->_db->table('students')->where('indexno', '=', $username)->update(['picture' => $image]);
    }

    public function _updateStaffImage($username, $image)
    {
        $this->_db->table('staff')->where('staff_id', '=', $username)->update(['picture' => $image]);
    }

    public function _getStudentDetails($username)
    {
        $student = $this->_db->table('students')->where('indexno', $username)->join('programmes', 'programmes.programme_id', '=', 'students.programmeid')->first();
        return $student;
    }

    private function _requiredFees($username, $semester, $academicYear, $year, $programmeId)
    {
        $user = $this->_getUserDetails($username);
        $gender = $user->sex;
        if ((($academicYear * $academicYear) + $semester) == 4064257) {
            $query = $this->_db->table('billing AS b')->where('program_id', $programmeId)->where('sex', $gender)->where('year', $year)->sum('b.amount AS amount')->first();
            return $query->amount;
        } else {
            $data = $this->_db->table('billing')->where('program_id', $programmeId)->where('sex', $gender)->where('year', $year)->get();
            $sum = 0;
            foreach ($data as $datum) {
                if ((($datum->academic_year * $datum->academic_year) + $datum->semester) < (($academicYear * $academicYear) + $semester)) {
                    $sum = $sum + $datum->amount;
                }
            }
            return ($sum != 0) ? $sum : 0;
        }
    }

    private function _getCurrentFee($username, $semester, $academicYear, $year, $programmeId)
    {
        $user = $this->_getUserDetails($username);
        $gender = $user->sex;

        $amount = 0;
        $data = $this->_db->table('billing')->where('program_id', $programmeId)->where('sex', $gender)
            ->where('semester', $semester)->where('academic_year', $academicYear)->where('year', $year)->get();
        foreach ($data as $datum) {
            $amount = $amount + $datum->amount;
        }
        return ($amount != 0) ? $amount : 0;
    }

    private function _freshersPercentage()
    {
        return $this->_registration->_currentSettings()->percentage_freshers;
    }

    private function _percentage()
    {
        return $this->_registration->_currentSettings()->percentage;
    }

    private function _checkAmountPaid($username)
    {
        $amount = 0;
        $data = $this->_db->table('payments')->where('indexno', $username)->get();
        foreach ($data as $datum) {
            $amount = $amount + $datum->amount_paid;
        }
        return ($amount != 0) ? $amount : 0;
    }

    public function checkFeesPaid($username, $semester, $academicYear, $year, $programmeId)
    {
//        $requiredFees = $this->_requiredFees($username, $semester, $academicYear, $year, $programmeId);
        $currentFees = $this->_getCurrentFee($username, $semester, $academicYear, $year, $programmeId);

        $percentage = 0;
        if ($year == 1) {
            $percentage = $this->_freshersPercentage();
        } else {
            $percentage = $this->_percentage();
        }
        $amountPaid = $this->_checkAmountPaid($username);
        if ($amountPaid > 0) {
            if (($amountPaid >= ($percentage / 100) * $currentFees) && $amountPaid != 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function _checkClearance($username, $semester, $academicYear)
    {
        $query = $this->_db->table('clearance')->where('indexno', $username)->where('semester', $semester)
            ->where('academic_year', $academicYear)->get();
        return ($query->isNotEmpty()) ? true : false;
    }

    public function _checkBill($semester, $academicYear, $programmeId, $year)
    {
        $query = $this->_db->table('billing')->where('semester', $semester)->where('academic_year', $academicYear)
            ->where('program_id', $programmeId)->where('year', $year)->get();
        return ($query->isNotEmpty()) ? true : false;
    }

    public function _getSchool()
    {
        $school = $this->_db->table('institution')->limit(1)->first();
        return $school;
    }

    public function _getStudents($academicYear)
    {
        $query = $this->_db->table('students')
            ->whereRaw($academicYear . '-class1 <= 3')
            ->select('students.indexno', 'students.programmeid', 'students.surname', 'students.firstname', 'students.sex', 'students.yearofadmission', 'students.class1', 'students.telephone')
            ->get();
        return $query;
    }

    public function _getStaff()
    {
        $query = $this->_db->table('staff')->get();
        return $query;
    }

    public function _getStaffCourses($staffId)
    {
        $query = $this->_db->table('user_courses')
            ->where('user_id', '=', $staffId)
            ->get();
        return $query;
    }

    public function _getCourse($courseId)
    {
        $query = $this->_db->table('courses')
            ->where('course_id', '=', $courseId)
            ->first();
        return $query;
    }

    public function _getCourseStudents($course, $code, $academicYear, $semester)
    {
        $query = $this->_db->table('academicrecords')
            ->where('academicrecords.course', '=', $course)
            ->where('academicrecords.code', '=', $code)
            ->where('academicrecords.acadyear', '=', $academicYear)
            ->where('academicrecords.sem', '=', $semester)
            ->leftJoin('students', 'academicrecords.indexno', '=', 'students.indexno')
            ->leftJoin('biometric', 'academicrecords.indexno', '=', 'biometric.id_number')
            ->select('academicrecords.*', 'students.indexno', 'students.programmeid', 'students.surname', 'students.firstname', 'students.sex',
                'students.yearofadmission', 'students.class1', 'students.telephone', 'biometric.id_number', 'biometric.type',
                'biometric.template_key', 'biometric.finger_one', 'biometric.finger_two')
            ->get();
        return $query;
    }

    public function _getStudentCourses($indexNumber, $academicYear, $semester)
    {
        $query = $this->_db->table('academicrecords')
            ->where('indexno', '=', $indexNumber)
            ->where('acadyear', '=', $academicYear)
            ->where('sem', '=', $semester)
            ->get();
        return $query;
    }
}
