<?php
namespace Database\Seeders; // ต้องมี namespace นี้

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThailandLocationsSeeder extends Seeder
{
    public function run()
    {
        // ตรวจสอบไฟล์ JSON ก่อนใช้งาน
        $provincesPath = database_path('data/provinces.json');
        $districtsPath = database_path('data/districts.json');
        $subdistrictsPath = database_path('data/subdistricts.json');

        $provinces = file_exists($provincesPath) ? json_decode(file_get_contents($provincesPath), true) : [];
        $districts = file_exists($districtsPath) ? json_decode(file_get_contents($districtsPath), true) : [];
        $subdistricts = file_exists($subdistrictsPath) ? json_decode(file_get_contents($subdistrictsPath), true) : [];

        if (empty($provinces)) {
            $this->command->error("❌ provinces.json ไม่มีข้อมูลหรืออ่านไม่ได้");
        } else {
            DB::table('provinces')->insert($provinces);
        }

        if (empty($districts)) {
            $this->command->error("❌ districts.json ไม่มีข้อมูลหรืออ่านไม่ได้");
        } else {
            DB::table('districts')->insert($districts);
        }

        if (empty($subdistricts)) {
            $this->command->error("❌ subdistricts.json ไม่มีข้อมูลหรืออ่านไม่ได้");
        } else {
            DB::table('subdistricts')->insert($subdistricts);
        }
    }
}