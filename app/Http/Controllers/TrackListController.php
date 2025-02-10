<?php

namespace App\Http\Controllers;

use App\Models\TrackList;
use Illuminate\Http\Request;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TrackListController extends Controller
{
    /**
     * ดึงข้อมูล TrackList ทั้งหมด
     */
    public function getList()
    {
        $items = TrackList::get()->toArray();

        if (!empty($items)) {
            for ($i = 0; $i < count($items); $i++) {
                $items[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $items);
    }

    /**
     * ดึงข้อมูล TrackList แบบแบ่งหน้า (Pagination)
     */
    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length  = $request->length;
        $order   = $request->order;
        $search  = $request->search;
        $start   = $request->start;
        $page    = ($start / $length) + 1;

        $col = ['id', 'track_no', 'date', 'create_by', 'update_by', 'created_at', 'updated_at'];
        $orderby = ['', 'track_no', 'date', 'create_by', 'update_by', 'created_at', 'updated_at'];

        $query = TrackList::select($col);

        if (isset($order[0]['column']) && !empty($orderby[$order[0]['column']])) {
            $query->orderBy($orderby[$order[0]['column']], $order[0]['dir']);
        } else {
            $query->orderBy('id', 'DESC');
        }

        if (isset($search['value']) && $search['value'] != '') {
            $query->where(function ($q) use ($search, $col) {
                foreach ($col as $c) {
                    $q->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $data = $query->paginate($length, ['*'], 'page', $page);

        if ($data->isNotEmpty()) {
            $No = (($page - 1) * $length);
            foreach ($data as $index => $item) {
                $No++;
                $data[$index]->No = $No;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    /**
     * เพิ่มข้อมูล TrackList ใหม่
     */
    public function store(Request $request)
    {
        // Validate ฟิลด์ track_no และ date
        $validator = Validator::make($request->all(), [
            'track_no' => 'required|string|max:255',
            'date'     => 'required|date'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $item = new TrackList();

            $item->track_no = $request->track_no;
            $item->date     = $request->date;

            // หากมีฟิลด์ create_by สามารถเก็บข้อมูลผู้ใช้งานที่ทำการเพิ่มได้
            // $item->create_by = $request->create_by;

            $item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูล TrackList สำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ดึงข้อมูล TrackList ตาม ID
     */
    public function show($id)
    {
        $item = TrackList::find($id);

        if (!$item) {
            return $this->returnErrorData('ไม่พบข้อมูล TrackList นี้', 404);
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $item);
    }

    /**
     * อัปเดตข้อมูล TrackList (ผ่าน parameter id)
     */
    public function update(Request $request, $id)
    {
        // Validate ฟิลด์ track_no และ date
        $validator = Validator::make($request->all(), [
            'track_no' => 'required|string|max:255',
            'date'     => 'required|date'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $item = TrackList::find($id);
            if (!$item) {
                return $this->returnErrorData('ไม่พบข้อมูล TrackList นี้', 404);
            }

            $item->track_no = $request->track_no;
            $item->date     = $request->date;

            // หากมีฟิลด์ update_by สามารถเก็บข้อมูลผู้ใช้งานที่ทำการแก้ไขได้
            // $item->update_by = $request->update_by;

            $item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูล TrackList สำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ลบข้อมูล TrackList
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $item = TrackList::find($id);
            if (!$item) {
                return $this->returnErrorData('ไม่พบข้อมูล TrackList นี้', 404);
            }

            $item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * อัปเดตข้อมูล TrackList โดยส่ง id มาจาก Request
     */
    public function updateData(Request $request)
    {
        if (!isset($request->id)) {
            return $this->returnErrorData('กรุณาเลือกข้อมูลที่ต้องการอัปเดต', 400);
        }

        DB::beginTransaction();

        try {
            $item = TrackList::find($request->id);
            if (!$item) {
                return $this->returnErrorData('ไม่พบข้อมูล TrackList นี้', 404);
            }

            $item->track_no = $request->track_no;
            $item->date     = $request->date;
            $item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูล TrackList สำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
}
