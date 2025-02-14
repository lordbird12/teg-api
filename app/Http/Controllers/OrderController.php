<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OrderList;
use App\Models\Order;
use App\Models\Products;
use App\Models\User;
use App\Models\member;
use App\Models\OrderAddOnService;
use App\Models\OrderOption;
use App\Models\AddOnService;
use App\Models\OrderPayment;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function getList()
    {
        $Item = Order::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['member'] = member::find($Item[$i]['member_id']);
                $Item[$i]['order_lists'] = OrderList::where('order_id', $Item[$i]['id'])->get();
                $Item[$i]['order_payment'] = OrderPayment::where('ref_no', $Item[$i]['code'])->get();
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getListByStatus($id)
    {
        // Define all possible statuses
        $statuses = ['awaiting_summary','awaiting_payment','in_progress','preparing_shipment','shipped','cancelled'];

        // Get orders for the member
        $Orders = Order::where('member_id', $id)->get();

        $orderIds = [];
        foreach ($Orders as $order) {
            $orderIds[] = $order->id;
        }

        // Group delivery orders by status
        $itemsGrouped = Order::whereIn('id', $orderIds)->get()->groupBy('status');

        $result = [];

        foreach ($statuses as $status) {
            $group = [
                'status' => $status,
                'orders' => []
            ];

            if (isset($itemsGrouped[$status])) {
                foreach ($itemsGrouped[$status] as $item) {
                    $order = $item->toArray();
                    $order['member'] = member::find($item->member_id);
                    $order['order_lists'] = OrderList::where('order_id', $item->id)->get();
                    $group['orders'][] = $order;
                }
            }

            $result[] = $group;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $result);
    }

    // public function getListByStatus($id)
    // {
        
    //     $itemsGrouped = Order::where('member_id',$id)->get()->groupBy('status');
    //     $result = [];

    //     foreach ($itemsGrouped as $status => $items) {
    //         $group = [
    //             'status' => $status,
    //             'orders' => []
    //         ];

    //         foreach ($items as $item) {
    //             $order = $item->toArray();
    //             $order['member'] = member::find($item->member_id);
    //             $order['order_lists'] = OrderList::where('order_id', $item->id)->get();
    //             $group['orders'][] = $order;
    //         }

    //         $result[] = $group;
    //     }

    //     return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $result);
    // }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;
      
        $status = $request->status;

        $date_start = $request->date_start;
        $date_end = $request->date_end;

        $member_id = $request->member_id;

        $col = array('id', 'code', 'date', 'total_price','member_id','payment_term','note','member_address_id','status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'code', 'date','total_price','member_id','payment_term','note','member_address_id','status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = Order::select($col);

         // เงื่อนไขการกรองช่วงวันที่
        if ($date_start && $date_end) {
            $D->whereBetween('date', [$date_start, $date_end]);
        } elseif ($date_start) {
            $D->where('date', '>=', $date_start);
        } elseif ($date_end) {
            $D->where('date', '<=', $date_end);
        }

        if ($status) {
            $D->where('status', $status);
        }

        if ($member_id) {
            $D->where('member_id', $member_id);
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
                $d[$i]->order_lists = OrderList::where('order_id', $d[$i]->id)->get();
                foreach ($d[$i]->order_lists as $key => $value) {
                    $d[$i]->order_lists[$key]->add_on_services = OrderAddOnService::where('order_id',$d[$i]->id)
                    ->where('order_list_id',$value['id'])
                    ->get();
                    foreach ($d[$i]->order_lists[$key]->add_on_services  as $key2 => $value2) {
                        $d[$i]->order_lists[$key]->add_on_services[$key2]->add_on_service = AddOnService::find($value2['add_on_service_id']);
                    }

                    $d[$i]->order_lists[$key]->options = OrderOption::where('order_id',$d[$i]->id)
                    ->where('order_list_id',$value['id'])
                    ->get();
                }
                $d[$i]->member = member::find($d[$i]->member_id);
                $d[$i]->order_payment = OrderPayment::where('ref_no',$d[$i]->code)->get();

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
        $loginBy = $request->login_by;

        DB::beginTransaction();

        try {
            $prefix = "#OR-";
            $id = IdGenerator::generate(['table' => 'orders', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);

            $Item = new Order();

            $Item->code = $id;
            $Item->date = $request->date;
            $Item->total_price = $request->total_price;
            $Item->member_id = $request->member_id;
            $Item->member_address_id = $request->member_address_id;
            $Item->shipping_type = $request->shipping_type;
            $Item->payment_term = $request->payment_term;
            $Item->note = $request->note;
            $Item->save();


            foreach ($request->products as $key => $value) {

                $ItemL = new OrderList();
                $ItemL->order_id = $Item->id;
                $ItemL->product_code = $value['product_code'];
                $ItemL->product_name = $value['product_name'];
                $ItemL->product_url = $value['product_url'];
                $ItemL->product_image = $value['product_image'];
                $ItemL->product_category = $value['product_category'];
                $ItemL->product_store_type = $value['product_store_type'];
                $ItemL->product_note = $value['product_note'];
                $ItemL->product_price = $value['product_price'];
                $ItemL->product_qty = $value['product_qty'];
                $ItemL->save();

                foreach ($value['add_on_services'] as $key => $value2) {

                    $ItemA = new OrderAddOnService();
                    $ItemA->order_id = $Item->id;
                    $ItemA->order_list_id = $ItemL->id;
                    $ItemA->add_on_service_id = $value2['add_on_service_id'];
                    $ItemA->add_on_service_price = $value2['add_on_service_price'];
                    $ItemA->save();
                }

                foreach ($value['options'] as $key => $value2) {

                    $ItemA = new OrderOption();
                    $ItemA->order_id = $Item->id;
                    $ItemA->order_list_id = $ItemL->id;
                    $ItemA->option_name = $value2['option_name'];
                    $ItemA->option_image = $value2['option_image'];
                    $ItemA->option_note = $value2['option_note'];
                    $ItemA->save();
                }
            }

            //



            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $orders
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = Order::where('id', $id)
            ->first();

        if ($Item) {
            $Item->member = member::find($Item->member_id);
            $Item->order_lists = OrderList::where('order_id', $id)->get();
            foreach ($Item->order_lists as $key => $value) {
                $Item->order_lists[$key]->add_on_services = OrderAddOnService::where('order_id',$Item->id)
                ->where('order_list_id',$value['id'])
                ->get();
                foreach ($Item->order_lists[$key]->add_on_services  as $key2 => $value2) {
                    $Item->order_lists[$key]->add_on_services[$key2]->add_on_service = AddOnService::find($value2['add_on_service_id']);
                }

                $Item->order_lists[$key]->options = OrderOption::where('order_id',$Item->id)
                ->where('order_list_id',$value['id'])
                ->get();
            }
            $Item->order_payment = OrderPayment::where('ref_no', $Item->code)->get();
            foreach ($Item->order_payment as $key => $value) {
                if($value['image'])
                $Item->order_payment[$key]->image = url($value['image']);
            }
           
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $orders
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $orders)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $orders
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $orders)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $orders
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = Order::find($id);
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
                $Item = Order::find($value);
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

    public function updateOrderTrack(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->track_ecommerce_no)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
           
                $Item = OrderList::find($request->order_list_id);
                if($Item){
                    $Item->track_ecommerce_no = $request->track_ecommerce_no;
                    $Item->save();
                }else{
                    return $this->returnErrorData('ไม่พบข้อมูลรายการ', 404);
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

    public function updateStatusOrderList(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->order_list_id)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            $Item = OrderList::find($request->order_list_id);
            if($Item){
                $Item->status = $request->status;
                $Item->save();
            }else{
                return $this->returnErrorData('ไม่พบข้อมูลรายการ', 404);
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

    public function updateStatusOrderListAll(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->order_lists)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {

            foreach ($request->order_lists as $key => $value) {
                $Item = OrderList::find($value);
                if($Item){
                    $Item->status = $request->status;
                    $Item->save();
                }else{
                    return $this->returnErrorData('ไม่พบข้อมูลรายการ', 404);
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
