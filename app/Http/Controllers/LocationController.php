<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * ดึงรายการจังหวัดทั้งหมด
     */
    public function getProvinces()
    {
        $provinces = Province::all();

        return response()->json([
            'message' => 'เรียกดูรายการจังหวัดสำเร็จ',
            'data' => $provinces
        ]);
    }

    /**
     * ดึงรายการอำเภอตามจังหวัด
     * @param int $province_id
     */
    public function getDistricts($province_id)
    {
        $districts = District::where('province_id', $province_id)->get();

        if ($districts->isEmpty()) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลอำเภอในจังหวัดนี้',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'เรียกดูรายการอำเภอสำเร็จ',
            'data' => $districts
        ]);
    }

    /**
     * ดึงรายการตำบล/เขตตามอำเภอ
     * @param int $district_id
     */
    public function getSubdistricts($district_id)
    {
        $subdistricts = Subdistrict::where('district_id', $district_id)->get();

        if ($subdistricts->isEmpty()) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลตำบลในอำเภอนี้',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'เรียกดูรายการตำบลสำเร็จ',
            'data' => $subdistricts
        ]);
    }
}
