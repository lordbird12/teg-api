<?php

namespace App\Http\Controllers;

use App\Models\ImportPO;
use App\Models\ImportProductOrder;
use App\Models\ImportProductOrderList;
use App\Models\ImportProductOrderListFee;
use App\Models\FeeMaster;
use App\Models\CategoryFeeMaster;
use App\Models\DeliveryOrderList;
use App\Models\DeliveryOrderTracking;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\DeliveryOrder;
use App\Models\member;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ImportProductOrderController extends Controller
{
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

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;



        $col = ['id', 'code', 'member_id', 'delivery_order_id', 'import_po_id', 'register_importer_id', 'store_id', 'note', 'status', 'invoice_file', 'packinglist_file', 'license_file', 'file', 'total_expenses', 'created_at', 'updated_at'];
        $orderby = ['', 'code', 'member_id', 'delivery_order_id', 'import_po_id', 'register_importer_id', 'store_id', 'note', 'status', 'invoice_file', 'packinglist_file', 'license_file', 'file', 'total_expenses', 'created_at', 'updated_at'];

        $query = ImportProductOrder::select($col)
            ->with('member')
            ->with('deliveryOrder')
            ->with('store')
            ->with('registerImporter');

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

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['No'] = $i + 1;
                if ($data[$i]['file']) {
                    $data[$i]['file'] = url($data[$i]['file']);
                }

                if ($data[$i]['invoice_file']) {
                    $data[$i]['invoice_file'] = url($data[$i]['invoice_file']);
                }

                if ($data[$i]['packinglist_file']) {
                    $data[$i]['packinglist_file'] = url($data[$i]['packinglist_file']);
                }

                if ($data[$i]['license_file']) {
                    $data[$i]['license_file'] = url($data[$i]['license_file']);
                }

                if ($data[$i]['store']) {
                    if ($data[$i]['store']['image']) {
                        $data[$i]['store']['image'] = url($data[$i]['store']['image']);
                    }
                }
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
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

        if (!$request->import_product_order_id) {
            return $this->returnErrorData('ไม่พบเลข PO ในระบบ', 404);
        }


        DB::beginTransaction();

        try {
            // Create ImportProductOrder
            $importOrder = ImportProductOrder::find($request->import_product_order_id);
            $prefix = "#IO-";
            $id = IdGenerator::generate(['table' => 'import_product_orders', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);

            $importOrder->code = $id;
            $importOrder->register_importer_id = $request->register_importer_id;
            $importOrder->store_id = $request->store_id;
            $importOrder->note = $request->note;
            $importOrder->invoice_file = $request->invoice_file;
            $importOrder->packinglist_file = $request->packinglist_file;
            $importOrder->license_file = $request->license_file;
            $importOrder->save();

            foreach ($request->fees as $fee) {
                $feeMaster = FeeMaster::find($fee['fee_master_id']);
                if ($feeMaster) {
                    $importOrderListFee = new ImportProductOrderListFee();
                    $importOrderListFee->import_product_order_id = $importOrder->id;
                    $importOrderListFee->fee_master_id = $fee['fee_master_id'];
                    $importOrderListFee->amount = $feeMaster->price;
                    $importOrderListFee->save();
                }
            }

            // Add ImportProductOrderLists
            foreach ($request->lists as $list) {
                $deliveryLists = DeliveryOrderList::find($list['delivery_order_list_id']);
                if ($deliveryLists) {
                    $importOrderList = new ImportProductOrderList();
                    $importOrderList->import_product_order_id = $importOrder->id;
                    $importOrderList->product_type_id = $deliveryLists->product_type_id;
                    $importOrderList->product_name = $deliveryLists->product_name;
                    $importOrderList->product_image = $deliveryLists->product_image;
                    $importOrderList->standard_size_id = $deliveryLists->standard_size_id;
                    $importOrderList->delivery_order_tracking_id = $deliveryLists->delivery_order_tracking_id;
                    $importOrderList->weight = $deliveryLists->weight;
                    $importOrderList->width = $deliveryLists->width;
                    $importOrderList->height = $deliveryLists->height;
                    $importOrderList->long = $deliveryLists->long;
                    $importOrderList->qty = $deliveryLists->qty;
                    $importOrderList->qty_box = $deliveryLists->qty_box;
                    $importOrderList->save();
                }
            }

            if ($importOrder) {
                $importOrder->status = "waiting_for_document_review";
                $importOrder->save();
            }

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $importOrder);
        } catch (\Throwable $e) {
            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImportProductOrder  $importProductOrder
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = ImportProductOrder::with('member')
            ->with('deliveryOrder')
            ->with('store')
            ->with('registerImporter')
            ->where('id', $id)
            ->first();

        if ($Item) {
            if ($Item->file) {
                $Item->file = url($Item->file);
            }

            if ($Item->invoice_file) {
                $Item->invoice_file = url($Item->invoice_file);
            }

            if ($Item->packinglist_file) {
                $Item->packinglist_file = url($Item->packinglist_file);
            }

            if ($Item->license_file) {
                $Item->license_file = url($Item->license_file);
            }

            if ($Item->store) {
                if ($Item->store->image) {
                    $Item->store->image = url($Item->store->image);
                }
            }

            $Item->member = member::find($Item->member_id);
            $Item->delivery_order = DeliveryOrder::find($Item->delivery_order_id);
            if ($Item->delivery_order) {
                $Item->delivery_order->deliverty_order_lists = DeliveryOrderList::where('delivery_order_id', $Item->delivery_order_id)->get();
            }
            $Item->delivery_order_tracks = DeliveryOrderTracking::where('delivery_order_id', $Item->delivery_order_id)->get();

            // foreach ($Item->delivery_order_tracks as $key => $value) {
            //     $Item->delivery_order_tracks[$key]->delivery_order_lists = DeliveryOrderList::where('delivery_order_id', $id)->get();
            //     foreach ($Item->delivery_order_tracks[$key]->delivery_order_lists as $key2 => $value2) {
            //         $Item->delivery_order_tracks[$key]->delivery_order_lists[$key2]->standard_size = StandardSize::find($value2['standard_size_id']);
            //         $Item->delivery_order_tracks[$key]->delivery_order_lists[$key2]->images = DeliveryOrderListImages::where('delivery_order_list_id',$value2['id'])
            //         ->get();
            //     }
            // }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ImportProductOrder  $importProductOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(ImportProductOrder $importProductOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportProductOrder  $importProductOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ImportProductOrder $importProductOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImportProductOrder  $importProductOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(ImportProductOrder $importProductOrder)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->import_product_order_id)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            foreach ($request->fees as $key => $value) {
                $Item = ImportProductOrderListFee::where('import_product_order_id', $request->import_product_order_id)
                    ->where('fee_master_id', $value['fee_master_id'])
                    ->first();

                if ($Item) {
                    $Item->amount = $value['amount'];
                    $Item->save();
                }
            }

            $check = ImportProductOrder::find($request->import_product_order_id);

            if (!$check) {
                return $this->returnErrorData('ไม่พบรายการสั่งซื้อนี้ กรุณาเปลี่ยนเป็นรหัสอื่น', 404);
            } else {
                $check->status = "waiting_for_tax_payment";
                $check->save();
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

    public function updateFileData(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->import_product_order_id)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {

            $Item = ImportProductOrder::find($request->import_product_order_id);

            if (!$Item) {
                return $this->returnErrorData('ไม่พบรายการสั่งซื้อนี้ กรุณาเปลี่ยนเป็นรหัสอื่น', 404);
            } else {
                $Item->file = $request->file;
                $Item->status = "completed";
                $Item->save();
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

    public function getListByStatus($id)
    {
        // Define all possible statuses
        $statuses = ['importing_documents', 'waiting_for_document_review', 'waiting_for_tax_payment', 'in_progress', 'completed'];

        // Get orders for the member
        $Orders = ImportProductOrder::where('member_id', $id)->get();

        $orderIds = [];
        foreach ($Orders as $order) {
            $orderIds[] = $order->id;
        }

        // Group delivery orders by status
        $itemsGrouped = ImportProductOrder::whereIn('id', $orderIds)->get()->groupBy('status');

        $result = [];

        foreach ($statuses as $status) {
            $group = [
                'status' => $status,
                'import_orders' => []
            ];

            if (isset($itemsGrouped[$status])) {
                foreach ($itemsGrouped[$status] as $item) {
                    $order = $item->toArray();
                    $order['member'] = member::find($item->member_id);
                    $order['import_order_lists'] = ImportProductOrderList::where('import_product_order_id', $item->id)->get();
                    $group['import_orders'][] = $order;
                }
            }

            $result[] = $group;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $result);
    }
}
