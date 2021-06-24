<?php

namespace App\Http\Controllers;

use App\Misc\Helper;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Hostel;

class HostelController extends Controller
{
    private $_hostel;

    public function __construct(Hostel $hostel)
    {
        $this->_hostel = $hostel;
    }

    public function addHostel(Request $request)
    {
        $name = trim($request->input('name'));
        $phone = trim($request->input('phone'));

        if (empty($name) || empty($phone)) {
            $data = [
                'success' => true,
                'description' => 'All fields are required'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        $payload = [
            'name' => strtoupper($name),
            'phone' => Helper::sanitizePhone($phone),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        $id = $this->_hostel->_save($payload);

        $data = [
            'success' => true,
            'description' => $this->_hostel->_get($id)
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function updateHostel($id, Request $request)
    {
        $name = trim($request->input('name'));
        $phone = trim($request->input('phone'));


        if (empty($name) || empty($phone)) {
            $data = [
                'success' => true,
                'description' => 'All fields are required'
            ];
            return response()->json([
                'data' => $data
            ], 201);
        }

        $payload = [
            'name' => strtoupper($name),
            'phone' => Helper::sanitizePhone($phone),
            'updated_at' => Carbon::now()
        ];

        $this->_hostel->_update($id, $payload);

        $data = [
            'success' => true,
            'description' => $this->_hostel->_get($id)
        ];

        return response()->json([
            'data' => $data
        ], 201);
    }

    public function getHostels()
    {
        $hostels = $this->_hostel->_gets();

        $data = [
            'success' => true,
            'description' => $hostels
        ];
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function deleteHostel($id)
    {
        $this->_hostel->_delete($id);

        $data = [
            'success' => true,
            'description' => 'Hostel deleted successfully'
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }
}
