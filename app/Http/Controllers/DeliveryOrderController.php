<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderList;
use App\Models\Products;
use App\Models\User;
use App\Models\member;
use App\Models\OrderAddOnService;
use App\Models\OrderOption;
use App\Models\AddOnService;
use App\Models\DeliveryOrderListImages;
use App\Models\StandardSize;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    public function getList()
    {
        $Item = DeliveryOrder::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['member'] = member::find($Item[$i]['member_id']);
                $Item[$i]['delivery_order_lists'] = DeliveryOrderList::where('delivery_order_id', $Item[$i]['id'])->get();
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getListByStatus($id)
    {
        $Orders = Order::where('member_id',$id)->get();

        $orderIds = [];
        foreach ($Orders as $order) {
            $orderIds[] = $order->id;
        }
 
        $itemsGrouped = DeliveryOrder::whereIn('order_id',$orderIds)->get()->groupBy('status');
        $result = [];

        foreach ($itemsGrouped as $status => $items) {
            $group = [
                'status' => $status,
                'delivery_orders' => []
            ];

            foreach ($items as $item) {
                $order = $item->toArray();
                $order['member'] = member::find($item->member_id);
                $order['delivery_order_lists'] = DeliveryOrderList::where('delivery_order_id', $item->id)->get();
                $group['delivery_orders'][] = $order;
            }

            $result[] = $group;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $result);
    }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;
        $date = $request->date;
        $status = $request->status;


        $col = array('id', 'code', 'date', 'order_id','track_no','driver_name','driver_phone','note','status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'code', 'date', 'order_id','track_no','driver_name','driver_phone','note','status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = DeliveryOrder::select($col);

        if ($date) {
            $D->where('date', $date);
        }

        if ($status) {
            $D->where('status', $status);
        }

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        } else {
            $D->orderby('id', 'DESC');
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
                $d[$i]->order = Order::find($d[$i]->order_id);
                if($d[$i]->order){
                    $d[$i]->order->order_lists = OrderList::where('order_id',$d[$i]->order_id)->get();
                }
                $d[$i]->delivery_order_lists = DeliveryOrderList::where('delivery_order_id', $d[$i]->id)->get();
                foreach ($d[$i]->delivery_order_lists as $key => $value) {
                    $d[$i]->delivery_order_lists[$key]->standard_size = StandardSize::find($value['standard_size_id']);
                    $d[$i]->delivery_order_lists[$key]->images = DeliveryOrderListImages::where('delivery_order_list_id',$value['id'])
                    ->get();
                }
                // $d[$i]->member = member::find($d[$i]->member_id);

            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Generate unique code for delivery order
            $prefix = "#DO-";
            $id = IdGenerator::generate(['table' => 'delivery_orders', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);

            // Create a new DeliveryOrder record
            $deliveryOrder = new DeliveryOrder();
            $deliveryOrder->code = $id;
            $deliveryOrder->order_id = $request->order_id;
            $deliveryOrder->date = $request->date;
            $deliveryOrder->track_no = $request->track_no;
            $deliveryOrder->driver_name = $request->driver_name;
            $deliveryOrder->driver_phone = $request->driver_phone;
            $deliveryOrder->note = $request->note;
            $deliveryOrder->status = $request->status;
            $deliveryOrder->create_by = $request->create_by;
            $deliveryOrder->save();

            // Add delivery order lists
            foreach ($request->lists as $list) {
                $deliveryOrderList = new DeliveryOrderList();
                $deliveryOrderList->delivery_order_id = $deliveryOrder->id;
                $deliveryOrderList->standard_size_id = $list['standard_size_id'];
                $deliveryOrderList->weight = $list['weight'];
                $deliveryOrderList->width = $list['width'];
                $deliveryOrderList->height = $list['height'];
                $deliveryOrderList->long = $list['long'];
                $deliveryOrderList->qty = $list['qty'];
                $deliveryOrderList->create_by = $request->create_by;
                $deliveryOrderList->save();

                // Add images for each delivery order list
                if (isset($list['images']) && is_array($list['images'])) {
                    foreach ($list['images'] as $image) {
                        $deliveryOrderListImage = new DeliveryOrderListImages();
                        $deliveryOrderListImage->delivery_order_list_id = $deliveryOrderList->id;
                        $deliveryOrderListImage->image_url = $image['image_url'];
                        $deliveryOrderListImage->image = $image['image'];
                        $deliveryOrderListImage->create_by = $request->create_by;
                        $deliveryOrderListImage->save();
                    }
                }
            }

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $deliveryOrder);
        } catch (\Throwable $e) {
            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DeliveryOrder  $deliveryOrders
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = DeliveryOrder::where('id', $id)
            ->first();

        if ($Item) {
            $Item->member = member::find($Item->member_id);
            $Item->order = Order::find($Item->order_id);
            if($Item->order){
                $Item->order->order_lists = OrderList::where('order_id', $Item->order_id)->get();
                foreach ($Item->order->order_lists as $key => $value) {
                    $Item->order->order_lists[$key]->add_on_services = OrderAddOnService::where('order_id',$Item->order_id)
                    ->where('order_list_id',$value['id'])
                    ->get();
                    foreach ($Item->order->order_lists[$key]->add_on_services  as $key2 => $value2) {
                        $Item->order->order_lists[$key]->add_on_services[$key2]->add_on_service = AddOnService::find($value2['add_on_service_id']);
                    }
    
                    $Item->order->order_lists[$key]->options = OrderOption::where('order_id',$Item->order_id)
                    ->where('order_list_id',$value['id'])
                    ->get();
                }
            }
            $Item->delivery_order_lists = DeliveryOrderList::where('delivery_order_id', $id)->get();
            foreach ($Item->delivery_order_lists as $key => $value) {
                $Item->delivery_order_lists[$key]->standard_size = StandardSize::find($value['standard_size_id']);
                $Item->delivery_order_lists[$key]->images = DeliveryOrderListImages::where('delivery_order_list_id',$value['id'])
                ->get();
                // foreach ($Item->order_lists[$key]->add_on_services  as $key2 => $value2) {
                //     $Item->order_lists[$key]->add_on_services[$key2]->add_on_service = AddOnService::find($value2['add_on_service_id']);
                // }

                // $Item->order_lists[$key]->options = OrderOption::where('order_id',$Item->id)
                // ->where('order_list_id',$value['id'])
                // ->get();
            }
           
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DeliveryOrder  $deliveryOrders
     * @return \Illuminate\Http\Response
     */
    public function edit(DeliveryOrder $deliveryOrders)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeliveryOrder  $deliveryOrders
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DeliveryOrder $deliveryOrders)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DeliveryOrder  $deliveryOrders
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = DeliveryOrder::find($id);
            $Item->delete();

            //log
            $userId = "admin";
            $type = 'ลบผู้ใช้งาน';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function updateStatus(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->orders)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            foreach ($request->orders as $key => $value) {
                $Item = DeliveryOrder::find($value);
                if($Item){
                    $Item->status = $request->status;
                    $Item->save();
                }
                
            }
            //

            //log
            $userId = "admin";
            $type = 'เพิ่มรายการ';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }


    public function dashboard()
    {
        // DB::beginTransaction();

        try {
            $Item['bests']['best_saller'] = 482;
            $Item['bests']['best_sale_item_qty'] = 3123;
            $Item['bests']['best_outstock'] = 4114;

            $Item['last_weeks']['mon'] = 10;
            $Item['last_weeks']['tue'] = 50;
            $Item['last_weeks']['wed'] = 20;
            $Item['last_weeks']['thu'] = 32;
            $Item['last_weeks']['fri'] = 56;
            $Item['last_weeks']['sat'] = 80;
            $Item['last_weeks']['son'] = 90;


            $Item['months'][1] = 2084;
            $Item['months'][2] = 4972;
            $Item['months'][3] = 1048;
            $Item['months'][4] = 5027;
            $Item['months'][5] = 1012;
            $Item['months'][5] = 5021;
            $Item['months'][6] = 2120;
            $Item['months'][7] = 5048;
            $Item['months'][8] = 2845;
            $Item['months'][9] = 4937;
            $Item['months'][10] = 3123;
            $Item['months'][11] = 4109;
            $Item['months'][12] = 4841;


            $Item['graph']['complete'] = 10;
            $Item['graph']['waiting'] = 30;
            $Item['graph']['delivery'] = 50;
            $Item['graph']['unaction'] = 10;


            $Users = User::get()->toarray();
            for ($i = 0; $i < count($Users); $i++) {
                $Users[$i]['total'] = 259;
            }
            $Item['users'] = $Users;


            // DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            // DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
}
