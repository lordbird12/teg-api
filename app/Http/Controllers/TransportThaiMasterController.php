<?php

namespace App\Http\Controllers;

use App\Models\TransportThaiMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransportThaiMasterController extends Controller
{
    public function getList()
    {
        $items = TransportThaiMaster::all();

        if ($items->isNotEmpty()) {
            foreach ($items as $index => $item) {
                $item->No = $index + 1;
                if ($item->image) {
                    $item->image = url($item->image);
                }
            }
        }
        
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $items);
    }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $category_product_id = $request->category_product_id;

        $col = array('id', 'code', 'image','name', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'code', 'image','name', 'create_by','update_by', 'created_at', 'updated_at');

        $D = TransportThaiMaster::select($col);

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {

            $D->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orWhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                //search with
                // $query = $this->withPermission($query, $search);
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;
                if($d[$i]->image)
                $d[$i]->image = url($d[$i]->image);

            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $item = new TransportThaiMaster();
            $item->name = $request->name;
            
            if ($request->image && $request->image != null && $request->image != 'null') {
                $item->image = $this->uploadImage($request->image, '/images/transports/');
            }
            
            $item->save();
            DB::commit();
            return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function show($id)
    {
        $item = TransportThaiMaster::find($id);

        if ($item) {
            if ($item->image) {
                $item->image = url($item->image);
            }
            return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $item);
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $item);
    }

    public function update(Request $request, $id)
    {
        $item = TransportThaiMaster::find($id);
        if (!$item) {
            return response()->json(['error' => 'ไม่พบข้อมูล'], 404);
        }

        DB::beginTransaction();
        try {
            $item->name = $request->name ?? $item->name;
            
            if ($request->image && $request->image != null && $request->image != 'null') {
                $item->image = $this->uploadImage($request->image, '/images/transports/');
            }

            $item->save();
            DB::commit();
            return $this->returnSuccess('ดำเนินการสำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function destroy($id)
    {
        $item = TransportThaiMaster::find($id);
        if (!$item) {
            return response()->json(['error' => 'ไม่พบข้อมูล'], 404);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            DB::commit();
            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function updateData(Request $request)
    {
        if (!isset($request->id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {
            $Item = TransportThaiMaster::find($request->id);

            if (!$Item) {
                return $this->returnErrorData('ไม่พบรายการ', 404);
            }

            $Item->name = $request->name ?? $Item->name;
            
            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/transports/');
            }

            $Item->save();
            //

            // //log
            // $userId = "admin";
            // $type = 'เพิ่มรายการ';
            // $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->name;
            // $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
}
