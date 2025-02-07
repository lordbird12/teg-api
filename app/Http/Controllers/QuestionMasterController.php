<?php

namespace App\Http\Controllers;

use App\Models\QuestionMaster;
use Illuminate\Http\Request;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QuestionMasterController extends Controller
{
    /**
     * ดึงข้อมูลทั้งหมดของคำถาม
     */
    public function getList($type)
    {
        $Item = QuestionMaster::where('type',$type)->get()->toArray();

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

        $col = ['id', 'code', 'type', 'option', 'created_at', 'updated_at'];
        $orderby = ['', 'code', 'type', 'option', 'created_at', 'updated_at'];

        $D = QuestionMaster::select($col);

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
     * เพิ่มคำถามใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'option' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = new QuestionMaster();
            $prefix = "#QM-";
            $id = IdGenerator::generate(['table' => 'question_masters', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);
            $Item->code = $id;
            $Item->type = $request->type;
            $Item->option = $request->option;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มคำถามสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ดึงข้อมูลคำถามตาม ID
     */
    public function show($id)
    {
        $Item = QuestionMaster::find($id);

        if (!$Item) {
            return $this->returnErrorData('ไม่พบข้อมูลคำถามนี้', 404);
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * อัปเดตข้อมูลคำถาม
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'option' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = QuestionMaster::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลคำถามนี้', 404);
            }

            $Item->type = $request->type;
            $Item->option = $request->option;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ลบคำถาม
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = QuestionMaster::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลคำถามนี้', 404);
            }

            $Item->delete();

            DB::commit();

            return $this->returnSuccess('ลบคำถามสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
}
