<?php

namespace App\Http\Controllers;

use App\Models\ImportProductOrder;
use App\Models\ImportProductOrderList;
use App\Models\ImportProductOrderListFee;
use App\Models\FeeMaster;
use App\Models\CategoryFeeMaster;
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
            // Create ImportProductOrder
            $importOrder = new ImportProductOrder();
            $importOrder->code = $request->code;
            $importOrder->register_importer_id = $request->register_importer_id;
            $importOrder->store_id = $request->store_id;
            $importOrder->note = $request->note;
            $importOrder->status = $request->status;
            $importOrder->invoice_file = $request->invoice_file;
            $importOrder->packinglist_file = $request->packinglist_file;
            $importOrder->license_file = $request->license_file;
            $importOrder->save();

            // Add ImportProductOrderLists
            foreach ($request->lists as $list) {
                $importOrderList = new ImportProductOrderList();
                $importOrderList->import_product_order_id = $importOrder->id;
                $importOrderList->product_type_id = $list['product_type_id'];
                $importOrderList->product_name = $list['product_name'];
                $importOrderList->track_no = $list['track_no'];
                $importOrderList->weight = $list['weight'];
                $importOrderList->width = $list['width'];
                $importOrderList->height = $list['height'];
                $importOrderList->long = $list['long'];
                $importOrderList->qty = $list['qty'];
                $importOrderList->create_by = $request->create_by;
                $importOrderList->save();

                // Add ImportProductOrderListFees
                if (isset($list['fees']) && is_array($list['fees'])) {
                    foreach ($list['fees'] as $fee) {
                        $importOrderListFee = new ImportProductOrderListFee();
                        $importOrderListFee->import_product_order_id = $importOrder->id;
                        $importOrderListFee->import_product_or_ls_id = $importOrderList->id;
                        $importOrderListFee->fee_master_id = $fee['fee_master_id'];
                        $importOrderListFee->amount = $fee['amount'];
                        $importOrderListFee->status = $fee['status'];
                        $importOrderListFee->create_by = $request->create_by;
                        $importOrderListFee->save();
                    }
                }
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
    public function show(ImportProductOrder $importProductOrder)
    {
        //
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
}
