<?php

namespace App\Http\Controllers;

use App\Models\member;
use App\Models\MemberDetailUser;
use App\Models\MemberDetailCompany;
use App\Models\MemberDetailAgent;
use App\Models\MemberAddress;
use App\Models\RegisterImporter;
use App\Models\shop;
use App\Models\TransportThaiMaster;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function searchAutoComplete(Request $request)
    {
        // ตรวจสอบการรับค่า search keyword
        $keyword = $request->get('keyword');

        if (!$keyword) {
            return $this->returnErrorData('กรุณาระบุคำค้นหา', 400);
        }

        // ค้นหาข้อมูลจากรหัสสมาชิกหรือชื่อ
        $items = Member::where('importer_code', 'like', '%' . $keyword . '%')
            ->orWhere('fname', 'like', '%' . $keyword . '%')
            ->limit(10) // จำกัดผลลัพธ์ที่แสดง
            ->get();

        // แปลง URL รูปภาพ
        foreach ($items as $item) {
            if ($item->image) {
                $item->image = url($item->image);
            }
        }

        return $this->returnSuccess('ค้นหาสำเร็จ', $items);
    }


    public function getList()
    {
        $Item = member::get()->toarray();

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

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;


        $col = array(
            'id', 'code', 'member_type', 'fname', 'lname', 'phone', 'birth_date', 'gender',
            'importer_code', 'password', 'referrer', 'address', 'province', 'district',
            'sub_district', 'postal_code', 'image', 'avaliable_time', 'credit_limit', 'loan_amount', 'bus_route', 'email', 
            'facebook', 'line_id', 'wechat', 'notify_sms', 'notify_line', 'notify_email', 
            'found_via', 'priority_update_tracking', 'priority_package_protection', 
            'priority_order_system', 'responsible_person', 'responsible_sale', 
            'responsible_remark', 'id_card_copy', 'company_certificate', 'pp20_document','language','create_by', 'update_by'
        );
        
        $orderby = array(
            '', 'code', 'member_type', 'fname', 'lname', 'phone', 'birth_date', 'gender',
            'importer_code', 'password', 'referrer', 'address', 'province', 'district',
            'sub_district', 'postal_code', 'image','avaliable_time', 'credit_limit', 'loan_amount', 'bus_route', 'email', 
            'facebook', 'line_id', 'wechat', 'notify_sms', 'notify_line', 'notify_email', 
            'found_via', 'priority_update_tracking', 'priority_package_protection', 
            'priority_order_system', 'responsible_person', 'responsible_sale', 
            'responsible_remark', 'id_card_copy', 'company_certificate', 'pp20_document','language','create_by', 'update_by'
        );

        $D = member::select($col);


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

        if (!isset($request->fname)) {
            return $this->returnErrorData('กรุณาระบุชื่อให้เรียบร้อย', 404);
        }
        
        $check = member::where('importer_code',$request->importer_code)->first();
        if ($check) {
            return $this->returnErrorData('รหัสผู้นำเข้าซ้ำกับที่มีในระบบ กรุณาเปลี่ยนเป็นรหัสอื่น', 404);
        }
        
        else

            DB::beginTransaction();

        try {
            $Item = new member();
            $prefix = "#M-";
            $id = IdGenerator::generate(['table' => 'members', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);
            $Item->code = $id;
            $Item->member_type = $request->member_type;
            $Item->fname = $request->fname;
            $Item->lname = $request->lname;
            $Item->phone = $request->phone;
            $Item->password = md5($request->password);
            $Item->birth_date = $request->birth_date;
            $Item->gender = $request->gender;
            $Item->importer_code = $request->importer_code;
            $Item->referrer = $request->referrer;
            $Item->image = $request->image;

            // ที่อยู่ปัจจุบัน
            $Item->address = $request->live_address;
            $Item->province = $request->live_province;
            $Item->district = $request->live_district;
            $Item->sub_district = $request->live_sub_district;
            $Item->postal_code = $request->live_postal_code;

            // ข้อมูลเพิ่มเติมที่เพิ่มเข้ามา
            $Item->avaliable_time = $request->avaliable_time; // morning, afternoon
            $Item->credit_limit = $request->credit_limit;
            $Item->loan_amount = $request->loan_amount;
            $Item->bus_route = $request->bus_route;
            $Item->email = $request->email;
            $Item->facebook = $request->facebook;
            $Item->line_id = $request->line_id;
            $Item->wechat = $request->wechat;

            // การแจ้งเตือน
            $Item->notify_sms = $request->notify_sms;
            $Item->notify_line = $request->notify_line;
            $Item->notify_email = $request->notify_email;

            // ช่องทางที่รู้จัก
            $Item->found_via = $request->found_via;

            // ความสำคัญของบริการ
            $Item->priority_update_tracking = $request->priority_update_tracking;
            $Item->priority_package_protection = $request->priority_package_protection;
            $Item->priority_order_system = $request->priority_order_system;

            // ข้อมูลผู้ดูแล
            $Item->responsible_person = $request->responsible_person;
            $Item->responsible_sale = $request->responsible_sale;
            $Item->responsible_remark = $request->responsible_remark;

            // เอกสารแนบ
            $Item->id_card_copy = $request->id_card_copy;
            $Item->company_certificate = $request->company_certificate;
            $Item->pp20_document = $request->pp20_document;
            $Item->language = $request->language;
            $Item->save();

            if($Item){
                if($Item->member_type == "บุคคลทั่วไป"){
                    //รายละเอียดการสมัคร
                    $ItemDetail = new MemberDetailUser();
                    $ItemDetail->member_id = $Item->id;
                    // การเชื่อมโยง TransportThaiMaster
                    $ItemDetail->transport_thai_master_id = $request->transport_thai_master_id;

                    // การขนส่ง
                    $ItemDetail->ever_imported_from_china = $request->ever_imported_from_china;
                    $ItemDetail->order_quantity = $request->order_quantity;
                    $ItemDetail->frequent_importer = $request->frequent_importer;
                    $ItemDetail->need_transport_type = $request->need_transport_type;
                    $ItemDetail->additional_requests = $request->additional_requests;
                    $ItemDetail->save();

                    $Item->detail = $ItemDetail;

                }else if($Item->member_type == "นิติบุคคล"){
                    //รายละเอียดการสมัคร
                    $ItemDetail = new MemberDetailCompany();
                    $ItemDetail->member_id = $Item->id;
                    // การเชื่อมโยง TransportThaiMaster
                    $ItemDetail->transport_thai_master_id = $request->transport_thai_master_id;

                    $ItemDetail->comp_name = $request->comp_name;
                    $ItemDetail->comp_tax = $request->comp_tax;
                    $ItemDetail->comp_phone = $request->comp_phone;
                    
                    // การขนส่ง
                    $ItemDetail->ever_imported_from_china = $request->ever_imported_from_china;
                    $ItemDetail->order_quantity = $request->order_quantity;
                    $ItemDetail->frequent_importer = $request->frequent_importer;
                    $ItemDetail->need_transport_type = $request->need_transport_type;
                    $ItemDetail->additional_requests = $request->additional_requests;
                    $ItemDetail->save();

                    $Item->detail = $ItemDetail;

                }else if($Item->member_type == "ตัวแทน"){
                    //รายละเอียดการสมัคร
                    $ItemDetail = new MemberDetailAgent();
                    $ItemDetail->member_id = $Item->id;
                    // การเชื่อมโยง TransportThaiMaster
                    $ItemDetail->transport_thai_master_id = $request->transport_thai_master_id;

                    $ItemDetail->comp_name = $request->comp_name;
                    $ItemDetail->comp_tax = $request->comp_tax;
                    $ItemDetail->comp_phone = $request->comp_phone;

                    $ItemDetail->cargo_name = $request->cargo_name;
                    $ItemDetail->cargo_website = $request->cargo_website;
                    $ItemDetail->cargo_image = $request->cargo_image;

                    // การขนส่ง
                    $ItemDetail->order_quantity_in_thai = $request->order_quantity_in_thai;
                    $ItemDetail->order_quantity = $request->order_quantity;
                    $ItemDetail->have_any_customers = $request->have_any_customers;
                    $ItemDetail->additional_requests = $request->additional_requests;
                    $ItemDetail->save();

                    $Item->detail = $ItemDetail;

                }else{
                    return $this->returnErrorData('ไม่พบประเภทสมาชิกที่คุณเลือก', 404);
                }

                // ที่อยู่สำหรับการขนส่ง
                $ItemAddress = new MemberAddress();
                $ItemAddress->member_id = $Item->id;
                $ItemAddress->address = $request->address;
                $ItemAddress->province = $request->province;
                $ItemAddress->district = $request->district;
                $ItemAddress->sub_district = $request->sub_district;
                $ItemAddress->postal_code = $request->postal_code;
                $ItemAddress->latitude = $request->latitude;
                $ItemAddress->longitude = $request->longitude;
                $ItemAddress->save();

                $Item->shipping_address = $ItemAddress;

                $Item->register_importer = RegisterImporter::where('member_id',$id)->first();

            }

            //log
            $userId = "admin";
            $type = 'เพิ่มรายการ';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->name;
            $this->Log($userId, $description, $type);
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
     * @param  \App\Models\member  $member
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = member::find($id);
        
        if ($Item) {
            if($Item->image)
            $Item->image = url($Item->image);

            if($Item->member_type == "บุคคลทั่วไป"){
                $Item->detail = MemberDetailUser::where('member_id',$Item->id)->first();
            }else  if($Item->member_type == "นิติบุคคล"){
                $Item->detail = MemberDetailCompany::where('member_id',$Item->id)->first();
            }else  if($Item->member_type == "ตัวแทน"){
                $Item->detail = MemberDetailAgent::where('member_id',$Item->id)->first();
                if($Item->detail->cargo_image){
                    $Item->detail->cargo_image = url($Item->detail->cargo_image);
                }
            }
            $Item->detail->transport_thai_master = TransportThaiMaster::find($Item->detail->transport_thai_master_id);
            if($Item->detail->transport_thai_master){
                if($Item->detail->transport_thai_master->image)
                $Item->detail->transport_thai_master->image = url($Item->detail->transport_thai_master->image);
            }
            $Item->ship_address = MemberAddress::where('member_id',$Item->id)->get();

        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\member  $member
     * @return \Illuminate\Http\Response
     */
    public function edit(member $member)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\member  $member
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!isset($id)) {
            return $this->returnErrorData('กรุณาเลือกสมาชิกให้ถูกต้อง', 404);
        }

        DB::beginTransaction();

        try {
            $Item = member::find($id);
            
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลสมาชิกนี้ในระบบ', 404);
            }
          
			if($Item->password)
            $Item->password = md5($request->password);
			if($request->image)
            $Item->image = $request->image;

            $Item->fname = $request->fname;
            $Item->lname = $request->lname;
            $Item->phone = $request->phone;
            $Item->birth_date = $request->birth_date;
            $Item->gender = $request->gender;
            $Item->importer_code = $request->importer_code;
            $Item->company_name = $request->company_name;
            $Item->address = $request->address;
            $Item->province = $request->province;
            $Item->district = $request->district;
            $Item->sub_district = $request->sub_district;
            $Item->postal_code = $request->postal_code;
            $Item->member_type = $request->member_type;

            $Item->save();

            //log
            $userId = "admin";
            $type = 'แก้ไข';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการเพิ่ม ' . $request->username;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\member  $member
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = member::find($id);
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

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
	
	public function updateData(Request $request)
    {
        if (!isset($request->member_id)) {
            return $this->returnErrorData('กรุณาเลือกสมาชิกให้ถูกต้อง', 404);
        }

        DB::beginTransaction();

        try {
            $Item = member::find($request->member_id);
            
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลสมาชิกนี้ในระบบ', 404);
            }
          
			if($Item->password)
            $Item->password = md5($request->password);
			if($request->image)
            $Item->image = $request->image;

            $Item->fname = $request->fname;
            $Item->lname = $request->lname;
            $Item->phone = $request->phone;
            $Item->birth_date = $request->birth_date;
            $Item->gender = $request->gender;
            $Item->importer_code = $request->importer_code;
            $Item->company_name = $request->company_name;
            $Item->address = $request->address;
            $Item->province = $request->province;
            $Item->district = $request->district;
            $Item->sub_district = $request->sub_district;
            $Item->postal_code = $request->postal_code;

            $Item->save();

            //log
            $userId = "admin";
            $type = 'แก้ไข';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการเพิ่ม ' . $request->username;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }


    public function updateAddress(Request $request)
    {
        // Validate ข้อมูลที่รับเข้ามา
        $validator = Validator::make($request->all(), [
            'member_id'   => 'required|integer|exists:members,id',
            'address'     => 'nullable|string|max:255',
            'province'    => 'nullable|string|max:255',
            'district'    => 'nullable|string|max:255',
            'sub_district'=> 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);

        // หากตรวจสอบไม่ผ่านให้ส่ง error กลับ
        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();
        
        try {
            $item = new MemberAddress(); // เปลี่ยนเป็น Model ของคุณ
            $item->member_id = $request->member_id;
            $item->address = $request->address;
            $item->province = $request->province;
            $item->district = $request->district;
            $item->sub_district = $request->sub_district;
            $item->postal_code = $request->postal_code;
            $item->latitude = $request->latitude;
            $item->longitude = $request->longitude;
            $item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูลสำเร็จ', $item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

    public function destroyAddress($id)
    {
        DB::beginTransaction();

        try {

            $Item = MemberAddress::find($id);
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

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function updateMemberAddress(Request $request, $id)
    {
        // ตรวจสอบค่าที่รับเข้ามา
        $validator = Validator::make($request->all(), [
            'member_id'   => 'required|integer|exists:members,id',
            'address'     => 'nullable|string|max:255',
            'province'    => 'nullable|string|max:255',
            'district'    => 'nullable|string|max:255',
            'sub_district'=> 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            // ค้นหาข้อมูลที่อยู่ของสมาชิก
            $address = MemberAddress::find($id);

            if (!$address) {
                return $this->returnErrorData('ไม่พบข้อมูลที่อยู่', 404);
            }

            // อัปเดตข้อมูล
            $address->member_id   = $request->member_id;
            $address->address     = $request->address;
            $address->province    = $request->province;
            $address->district    = $request->district;
            $address->sub_district= $request->sub_district;
            $address->postal_code = $request->postal_code;
            $address->latitude    = $request->latitude;
            $address->longitude   = $request->longitude;
            $address->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตที่อยู่สำเร็จ', $address);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }

}
