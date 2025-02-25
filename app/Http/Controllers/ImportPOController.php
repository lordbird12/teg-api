<?php

namespace App\Http\Controllers;

use App\Models\ImportPO;
use App\Models\ImportProductOrder;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderList;
use App\Models\DeliveryOrderTracking;
use App\Models\DeliveryOrderAddOnServices;
use App\Models\member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ImportPOController extends Controller
{
    public function getList($id)
    {
        $items = ImportProductOrder::where('member_id',$id)->get()->toArray();

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $items[$key]['No'] = $key + 1;
                $items[$key]['delivery_orders'] = DeliveryOrder::find($items[$key]['delivery_order_id']);
                if($items[$key]['delivery_orders']){
                    $items[$key]['delivery_orders']['track'] = DeliveryOrderTracking::where('delivery_order_id',$items[$key]['delivery_orders']['id'])->first();
                    $items[$key]['delivery_orders']['order'] = Order::find($items[$key]['delivery_orders']['order_id']);
                    if($items[$key]['delivery_orders']['track']){
                        $items[$key]['delivery_orders']['track']['delivery_order_lists'] = DeliveryOrderList::where('delivery_order_id',$items[$key]['delivery_order_id'])->where('delivery_order_tracking_id',$items[$key]['delivery_orders']['track']['id'])->get();
                    }
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

        $col = ['id', 'member_id', 'delivery_order_id', 'created_at', 'updated_at'];
        $orderby = ['', 'member_id','delivery_order_id', 'created_at', 'updated_at'];

        $query = ImportProductOrder::select($col);

        if ($orderby[$order[0]['column']] ?? false) {
            $query->orderBy($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if (!empty($search['value'])) {
            $query->where(function ($q) use ($search, $col) {
                foreach ($col as $c) {
                    $q->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $data = $query->paginate($length, ['*'], 'page', $page);

        if ($data->isNotEmpty()) {
            $No = (($page - 1) * $length);
            foreach ($data as $item) {
                $item->No = ++$No;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|integer',
            'delivery_orders' => 'required|array',
            'delivery_orders.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            foreach ($request->delivery_orders as $key => $value) {
                $importPO = new ImportProductOrder();
                $importPO->member_id = $request->member_id;
                $importPO->delivery_order_id = $value;
                $importPO->save();
            }
            

            DB::commit();

            return $this->returnSuccess('บันทึกข้อมูลสำเร็จ', $importPO);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $item = ImportProductOrder::find($id);

        if (!$item) {
            return $this->returnErrorData('ไม่พบข้อมูล', 404);
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $item);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|integer',
            'delivery_orders' => 'required|array',
            'delivery_orders.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $importPO = ImportProductOrder::find($id);

            if (!$importPO) {
                return $this->returnErrorData('ไม่พบข้อมูล', 404);
            }

            $importPO->member_id = $request->member_id;
            $importPO->delivery_orders = $request->delivery_orders;
            $importPO->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $importPO);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $importPO = ImportProductOrder::find($id);

            if (!$importPO) {
                return $this->returnErrorData('ไม่พบข้อมูล', 404);
            }

            $importPO->delete();

            DB::commit();

            return $this->returnSuccess('ลบข้อมูลสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }
}
