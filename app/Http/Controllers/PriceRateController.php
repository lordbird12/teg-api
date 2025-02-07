<?php

namespace App\Http\Controllers;

use App\Models\PriceRate;
use Illuminate\Http\Request;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PriceRateController extends Controller
{
    /**
     * ดึงข้อมูลราคาทั้งหมด
     */
    public function getList($vehicle)
    {
        $Item = PriceRate::where('vehicle',$vehicle)->get()->toArray();

        if (!empty($Item)) {
            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * ดึงข้อมูลแบบแบ่งหน้า (Pagination)
     */
    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = ['id', 'vehicle', 'name', 'type','kg','cbm', 'create_by', 'update_by', 'created_at', 'updated_at'];
        $orderby = ['', 'vehicle', 'name', 'type','kg','cbm', 'create_by', 'update_by', 'created_at', 'updated_at'];

        $D = PriceRate::select($col);

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {
            $D->where(function ($query) use ($search, $col) {
                foreach ($col as $c) {
                    $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {
            $No = (($page - 1) * $length);
            for ($i = 0; $i < count($d); $i++) {
                $No = $No + 1;
                $d[$i]->No = $No;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    /**
     * เพิ่มข้อมูลราคาใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = new PriceRate();
            $prefix = "#PR-";
            $id = IdGenerator::generate(['table' => 'price_rates', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);
            $Item->code = $id;
            $Item->vehicle = $request->vehicle;
            $Item->type = $request->type;
            $Item->name = $request->name;
            $Item->kg = $request->kg;
            $Item->cbm = $request->cbm;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูลราคาสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ดึงข้อมูลราคาตาม ID
     */
    public function show($id)
    {
        $Item = PriceRate::find($id);

        if (!$Item) {
            return $this->returnErrorData('ไม่พบข้อมูลราคานี้', 404);
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * อัปเดตข้อมูลราคา
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = PriceRate::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลราคานี้', 404);
            }

            $Item->vehicle = $request->vehicle;
            $Item->type = $request->type;
            $Item->name = $request->name;
            $Item->kg = $request->kg;
            $Item->cbm = $request->cbm;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ลบข้อมูลราคา
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = PriceRate::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลราคานี้', 404);
            }

            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * อัปเดตข้อมูลราคาตาม ID
     */
    public function updateData(Request $request)
    {
        if (!isset($request->id)) {
            return $this->returnErrorData('กรุณาเลือกข้อมูลที่ต้องการอัปเดต', 400);
        }

        DB::beginTransaction();

        try {
            $Item = PriceRate::find($request->id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลราคานี้', 404);
            }

            $Item->vehicle = $request->vehicle;
            $Item->type = $request->type;
            $Item->name = $request->name;
            $Item->kg = $request->kg;
            $Item->cbm = $request->cbm;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
}
