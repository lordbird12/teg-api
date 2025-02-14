<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderList;
use App\Models\ImportPO;
use App\Models\ImportProductOrder;
use App\Models\ImportProductOrderList;
use App\Models\ImportProductOrderListFee;
use App\Models\member;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderPaymentController extends Controller
{
    public function getList()
    {
        $Item = OrderPayment::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = ['id', 'member_id', 'ref_no','order_type' ,'date', 'total_price', 'note', 'image', 'created_at', 'updated_at'];
        $orderby = ['', 'member_id', 'ref_no', 'order_type' ,'total_price'];

        $D = OrderPayment::select($col);

        if (isset($order[0]['column']) && $orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if (!empty($search['value'])) {
            $D->where(function ($query) use ($search, $col) {
                foreach ($col as $field) {
                    $query->orWhere($field, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    public function store(Request $request)
    {
        if (!isset($request->member_id) || !isset($request->ref_no)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้ครบถ้วน', 404);
        }

       if($request->order_type == "order"){
            $check = Order::where('code',$request->ref_no)->first();
            if($check){
                if($check->status == "awaiting_summary"){
                    return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนการสรุปยอด ยังไม่สามารถรับชำระเงินได้', 404);
                }else if($check->status == "in_progress"){
                    return $this->returnErrorData('รายการสั่งซื้อมีการชำระเงินแล้ว', 404);
                }else if($check->status == "preparing_shipment"){
                    return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนกำลังเตรียมการจัดส่ง', 404);
                }else if($check->status == "shipped"){
                    return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนจัดส่งแล้ว', 404);
                }else if($check->status == "cancelled"){
                    return $this->returnErrorData('รายการสั่งซื้อถูกยกเลิกแล้ว', 404);
                }else{
                    $status = "in_progress";
                }
            }else{
                return $this->returnErrorData('ไม่พบรายการที่ท่านอ้างอิงอยู่ กรุณาตรวจสอบ', 404);
            }
       }else if($request->order_type == "delivery"){
                $check = DeliveryOrder::where('code',$request->ref_no)->first();
                if($check){
                    if($check->status == "awaiting_summary"){
                        return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนการสรุปยอด ยังไม่สามารถรับชำระเงินได้', 404);
                    }else if($check->status == "in_progress"){
                        return $this->returnErrorData('รายการสั่งซื้อมีการชำระเงินแล้ว', 404);
                    }else if($check->status == "preparing_shipment"){
                        return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนกำลังเตรียมการจัดส่ง', 404);
                    }else if($check->status == "shipped"){
                        return $this->returnErrorData('รายการสั่งซื้ออยู่ในขั้นตอนจัดส่งแล้ว', 404);
                    }else if($check->status == "cancelled"){
                        return $this->returnErrorData('รายการสั่งซื้อถูกยกเลิกแล้ว', 404);
                    }else{
                        $status = "delivered";
                    }
                }else{
                    return $this->returnErrorData('ไม่พบรายการที่ท่านอ้างอิงอยู่ กรุณาตรวจสอบ', 404);
                }
        }else if($request->order_type == "import"){
            
            // 'importing_documents','waiting_for_document_review','waiting_for_tax_payment','in_process','completed'
                $check = ImportProductOrder::where('code',$request->ref_no)->first();
             
                if($check){
                    if($check->status == "importing_documents"){
                        return $this->returnErrorData('รายการนี้อยู่ในขั้นตอนยื่นตรวจสอบเอกสาร', 404);
                    }else if($check->status == "waiting_for_document_review"){
                        return $this->returnErrorData('รายการนี้อยู่ในขั้นตอนตรวจสอบเอกสาร', 404);
                    }else if($check->status == "in_progress"){
                        return $this->returnErrorData('รายการนี้อยู่ในขั้นตอนจัดทำเอกสาร', 404);
                    }else if($check->status == "completed"){
                        return $this->returnErrorData('รายการนี้ได้ดำเนินการเสร็จสิ้นแล้ว', 404);
                    }else{
                        $status = "in_progress";
                    }
                }else{
                    return $this->returnErrorData('ไม่พบรายการที่ท่านอ้างอิงอยู่ กรุณาตรวจสอบ', 404);
                }
        }else{
            return $this->returnErrorData('ประเภทการทำรายการไม่ถูกต้อง', 404);
        }
        

        if($request->payment_type == "wallet"){
            $checkWallet = member::find($request->member_id);
            if (!$checkWallet) {
                return $this->returnErrorData('ไม่พบสมาชิก กรุณาเปลี่ยนเป็นรหัสอื่น', 404);
            }else{
                if($checkWallet->wallet_balance < $request->total_price){
                    return $this->returnErrorData('เงินของคุณมีไม่เพียงพอ กรุณาเติมเงิน', 404);
                }
            }
        }else{
            return $this->returnErrorData('ระบบรองรับการชำระเงินรูปแบบ Wallet เท่านั้น ขณะนี้', 404);
        }

        DB::beginTransaction();

        try {
            $Item = new OrderPayment();
            $Item->member_id = $request->member_id;
            $Item->ref_no = $request->ref_no;
            $Item->date = $request->date;
            $Item->total_price = $request->total_price;
            $Item->note = $request->note;
            $Item->image = $request->image;
            $Item->payment_type = $request->payment_type;
            $Item->order_type = $request->order_type;

            $Item->save();

            if($Item){
                $check->status = $status;
                $check->save();

                $ItemWallet = new WalletTransaction();
                $ItemWallet->member_id = $request->member_id;
                $ItemWallet->in_from = null;
                $ItemWallet->out_to = "Order";
                $ItemWallet->reference_id = $check->code ?? null;
                $ItemWallet->detail = "Buy Item";
                $ItemWallet->amount = $request->total_price;
                $ItemWallet->type = "O";

                $ItemWallet->save();

                if($ItemWallet){
                    $ItemMember = member::find($request->member_id);
                    $ItemMember->wallet_balance = $this->getNetBalance($request->member_id);
                    $ItemMember->save();
                }
            }

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 404);
        }
    }

    public function show($id)
    {
        $Item = OrderPayment::find($id);

        if ($Item) {
            return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
        }

        return $this->returnErrorData('ไม่พบข้อมูล', 404);
    }

    public function update(Request $request, $id)
    {
        if (!isset($id)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้ครบถ้วน', 404);
        }

        DB::beginTransaction();

        try {
            $Item = OrderPayment::find($id);
            $Item->member_id = $request->member_id;
            $Item->ref_no = $request->ref_no;
            $Item->date = $request->date;
            $Item->total_price = $request->total_price;
            $Item->note = $request->note;
            $Item->image = $request->image;
            $Item->payment_type = $request->payment_type;
            $Item->order_type = $request->order_type;

            $Item->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 404);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = OrderPayment::find($id);
            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 404);
        }
    }
}