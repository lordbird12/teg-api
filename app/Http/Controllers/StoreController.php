<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * ดึงข้อมูลร้านค้าทั้งหมด
     */
    public function getList()
    {
        $Item = Store::get()->toArray();

        if (!empty($Item)) {
            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                if ($Item[$i]['image']) {
                    $Item[$i]['image'] = url($Item[$i]['image']);
                }
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

        $col = ['id', 'name', 'description', 'image', 'address','map', 'phone', 'created_at', 'updated_at'];
        $orderby = ['', 'name', 'description', 'image', 'address','map', 'phone', 'created_at', 'updated_at'];

        $D = Store::select($col);

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
                if ($d[$i]->image) {
                    $d[$i]->image = url($d[$i]->image);
                }
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    /**
     * เพิ่มร้านค้าใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = new Store();
            $Item->name = $request->name;
            $Item->description = $request->description;
            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/stores/');
            }
            $Item->address = $request->address;
            $Item->phone = $request->phone;
            $Item->map = $request->map;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มร้านค้าสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ดึงข้อมูลร้านค้าตาม ID
     */
    public function show($id)
    {
        $Item = Store::find($id);

        if (!$Item) {
            return $this->returnErrorData('ไม่พบข้อมูลร้านค้านี้', 404);
        }

        if ($Item->image) {
            $Item->image = url($Item->image);
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * อัปเดตร้านค้า
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = Store::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลร้านค้านี้', 404);
            }

            $Item->name = $request->name;
            $Item->description = $request->description;
            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/stores/');
            }
            $Item->address = $request->address;
            $Item->phone = $request->phone;
            $Item->map = $request->map;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ลบร้านค้า
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = Store::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลร้านค้านี้', 404);
            }

            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    public function updateData(Request $request)
    {
        if (!isset($request->id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {
            $Item = Store::find($request->id);

            if (!$Item) {
                return $this->returnErrorData('ไม่พบรายการ', 404);
            }

            $Item->name = $request->name;
            $Item->description = $request->description;
            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/stores/');
            }
            $Item->address = $request->address;
            $Item->phone = $request->phone;

            $Item->save();
            //
            
            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
}
