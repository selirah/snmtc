<?php

namespace App\Http\Controllers;

use App\Models\Biometric;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Registration;

class BiometricController extends Controller
{
    private $_biometric;
    private $_account;
    private $_registration;

    public function __construct(Biometric $biometric, Account $account, Registration $registration)
    {
        $this->_biometric = $biometric;
        $this->_account = $account;
        $this->_registration = $registration;
    }

    public function save(Request $request)
    {
        $idNumber = trim($request->input('id_number'));
        $type = trim($request->input('type'));
        $templateKey = trim($request->input('template_key'));
        $fingerOne = trim($request->input('fingerprint_one'));
        $fingerTwo = trim($request->input('fingerprint_two'));

        if (empty($idNumber) || empty($type) || empty($templateKey) || empty($fingerOne) || empty($fingerTwo)) {
            $data = [
                'success' => true,
                'description' => 'All fields are required'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        // check if biometric data exist
        $check = $this->_biometric->_checkBiometricData($idNumber, $type);

        if ($check) {
            $data = [
                'success' => true,
                'description' => 'Biometric data already exists'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        $payload = [
            'id_number' => $idNumber,
            'type' => (int)$type,
            'template_key' => $templateKey,
            'finger_one' => $fingerOne,
            'finger_two' => $fingerTwo,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $this->_biometric->_save($payload);

        $data = [
            'success' => true,
            'description' => 'Biometric Data saved'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function saveBatch(Request $request)
    {
        $biometricData = trim($request->input('data'));
        $bio = json_decode($biometricData, true);
        $payload = [];
        $count = count($bio);

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
            $idNumber = $bio[$i]['id_number'];
            $type = $bio[$i]['type'];
            $templateKey = $bio[$i]['template_key'];
            $fingerOne = $bio[$i]['fingerprint_one'];
            $fingerTwo = $bio[$i]['fingerprint_two'];

            // check if biometric data exist
            $check = $this->_biometric->_checkBiometricData($idNumber, $type);

            if (!$check) {
                $payload[] = [
                    'id_number' => $idNumber,
                    'type' => (int)$type,
                    'template_key' => $templateKey,
                    'finger_one' => $fingerOne,
                    'finger_two' => $fingerTwo,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
        }
        $this->_biometric->_save($payload);

        $data = [
            'success' => true,
            'description' => 'Biometric Data saved'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function update($id, Request $request)
    {
        $idNumber = trim($request->input('id_number'));
        $type = trim($request->input('type'));
        $templateKey = trim($request->input('template_key'));
        $fingerOne = trim($request->input('fingerprint_one'));
        $fingerTwo = trim($request->input('fingerprint_two'));

        $payload = [
            'id_number' => $idNumber,
            'type' => (int)$type,
            'template_key' => $templateKey,
            'finger_one' => $fingerOne,
            'finger_two' => $fingerTwo,
            'updated_at' => Carbon::now()
        ];

        $this->_biometric->_update($id, $payload);

        $data = [
            'success' => true,
            'description' => 'Biometric Data updated'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function getAll(Request $request)
    {
        $type = trim($request->get('type'));
        $bioData = $this->_biometric->_getBiometric($type);
        $data = [
            'success' => true,
            'description' => $bioData
        ];
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getFirst(Request $request)
    {
        $idNumber = trim($request->get('id_number'));
        $type = trim($request->get('type'));
        $bioData = $this->_biometric->_getBiometricData($idNumber, $type);

        if (!$bioData) {
            $data = [
                'success' => true,
                'description' => []
            ];
        } else {
            $data = [
                'success' => true,
                'description' => $bioData
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function delete($id)
    {
        $this->_biometric->_delete($id);

        $data = [
            'success' => true,
            'description' => 'Biometric Data deleted'
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function getStudents()
    {
        $currentSettings = $this->_registration->_currentSettings();
        $academicYear = $currentSettings->current_acadyear;
        $students = $this->_account->_getStudents($academicYear);

        $data = [
            'success' => true,
            'description' => $students
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function getStaff()
    {

        $staff = $this->_account->_getStaff();

        $data = [
            'success' => true,
            'description' => $staff
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }
}
