<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceImages;
use App\Models\ServiceIcon;
use App\Models\ServiceIconBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * ดึงข้อมูลทั้งหมดของบริการ
     */
    public function getList()
    {
        $Item = Service::get()->toArray();

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

        $col = ['id', 'name', 'description','remark', 'image','line','phone', 'create_by', 'update_by', 'created_at', 'updated_at'];
        $orderby = ['', 'name', 'description','remark', 'image','line','phone', 'create_by', 'update_by', 'created_at', 'updated_at'];

        $D = Service::select($col);

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
                $d[$i]->images = ServiceImages::where('service_id',$d[$i]->id)->get();
                foreach ($d[$i]->images as $key => $value) {
                    if($d[$i]->images[$key]->image){
                        $d[$i]->images[$key]->image = url($d[$i]->images[$key]->image);
                    }
                }
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    /**
     * เพิ่มข้อมูลบริการใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = new Service();
            $Item->name = $request->name;
            $Item->description = $request->description;
            if ($request->image) {
                $Item->image = $request->image;
            }
            $Item->line = $request->line;
            $Item->phone = $request->phone;
            $Item->remark = $request->remark;

            $Item->save();

            foreach ($request->images as $key => $value) {

                $Files = new ServiceImages();
                $Files->service_id =  $Item->id;
                $Files->image = $value;
                $Files->save();
            }

            foreach ($request->icons as $key => $value) {

                $Icons = new ServiceIcon();
                $Icons->service_id =  $Item->id;
                $Icons->name =  $value['name'];
                $Icons->image = $value['image'];
                $Icons->save();
            }

            foreach ($request->icon_boxs as $key => $value) {

                $Icons = new ServiceIconBox();
                $Icons->service_id =  $Item->id;
                $Icons->name =  $value['name'];
                $Icons->description =  $value['description'];
                $Icons->image = $value['image'];
                $Icons->save();
            }


            DB::commit();

            return $this->returnSuccess('เพิ่มบริการสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ดึงข้อมูลบริการตาม ID
     */
    public function show($id)
    {
        $Item = Service::find($id);

        if ($Item) {
            if ($Item->image) {
                $Item->image = url($Item->image);
            }

            $Item->images = ServiceImages::where('service_id',$Item->id)->get();
            foreach ($Item->images as $key => $value) {
                if($Item->images[$key]->image){
                    $Item->images[$key]->image = url($Item->images[$key]->image);
                }
            }

            $Item->icons = ServiceIcon::where('service_id',$Item->id)->get();
            foreach ($Item->icons as $key => $value) {
                if($Item->icons[$key]->image){
                    $Item->icons[$key]->image = url($Item->icons[$key]->image);
                }
            }

            $Item->icon_boxs = ServiceIconBox::where('service_id',$Item->id)->get();
            foreach ($Item->icon_boxs as $key => $value) {
                if($Item->icon_boxs[$key]->image){
                    $Item->icon_boxs[$key]->image = url($Item->icon_boxs[$key]->image);
                }
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * อัปเดตข้อมูลบริการ
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = Service::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบบริการนี้', 404);
            }

            $Item->name = $request->name;
            $Item->description = $request->description;
            if ($request->image) {
                $Item->image = $request->image;
            }
            $Item->line = $request->line;
            $Item->phone = $request->phone;
            $Item->remark = $request->remark;

            $Item->save();

            foreach ($request->images as $key => $value) {

                $Files = new ServiceImages();
                $Files->service_id =  $Item->id;
                $Files->image = $value;
                $Files->save();
            }

            foreach ($request->icons as $key => $value) {

                $Icons = new ServiceIcon();
                $Icons->service_id =  $Item->id;
                $Icons->name =  $value['name'];
                $Icons->image = $value['image'];
                $Icons->save();
            }

            foreach ($request->icon_boxs as $key => $value) {

                $Icons = new ServiceIconBox();
                $Icons->service_id =  $Item->id;
                $Icons->name =  $value['name'];
                $Icons->description =  $value['description'];
                $Icons->image = $value['image'];
                $Icons->save();
            }

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    /**
     * ลบบริการ
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = Service::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบบริการนี้', 404);
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
     * อัปเดตข้อมูลบริการโดยระบุ ID
     */
    public function updateData(Request $request)
    {
        if (!isset($request->id)) {
            return $this->returnErrorData('กรุณาเลือกบริการที่ต้องการอัปเดต', 400);
        }

        DB::beginTransaction();

        try {
            $Item = Service::find($request->id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบบริการนี้', 404);
            }

            $Item->name = $request->name;
            $Item->description = $request->description;
            $Item->image = $request->image;

            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
}
