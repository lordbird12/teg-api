<?php

namespace App\Http\Controllers;

use App\Models\member;
use App\Models\MemberDetailUser;
use App\Models\MemberDetailCompany;
use App\Models\MemberDetailAgent;
use App\Models\MemberAddress;
use App\Models\shop;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
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


        $col = array('id', 'code', 'member_type', 'fname', 'lname', 'phone', 'birth_date', 'gender',
        'importer_code', 'password', 'referrer', 'address',
        'province', 'district', 'sub_district', 'postal_code', 'image', 'create_by', 'update_by');

        $orderby = array('', 'code', 'member_type', 'fname', 'lname', 'phone', 'birth_date', 'gender',
        'importer_code', 'password', 'referrer', 'address',
        'province', 'district', 'sub_district', 'postal_code', 'image', 'create_by', 'update_by');

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


    public function openShop(Request $request)
    {
        if (!isset($request->member_id)) {
            return $this->returnErrorData('กรุณาระบุรหัสสมาชิกให้เรียบร้อย', 404);
        } 

        $check1 = shop::where('member_id',$request->member_id)->first();
        if ($check1) {
            return $this->returnErrorData('คุณได้เปิดร้านค้านี้แล้วในระบบ', 404);
        }

        $ItemMember = member::find($request->member_id);
        if (!$ItemMember) {
            return $this->returnErrorData('ไม่พบบัญชีผู้ใช้นี้ในระบบ', 404);
        }

        try {
            $Item = new shop();
            $prefix = "#SH-";
            $id = IdGenerator::generate(['table' => 'shops', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);
            $Item->code = $id;
            $Item->member_id = $request->member_id;
            $Item->name = $request->name;
            $Item->phone = $request->phone;
            $Item->address = $request->address;
            $Item->lat = $request->lat;
            $Item->lon = $request->lon;
            $Item->open = 'Yes';
            $Item->image = $request->image;

            // if ($request->image && $request->image != null && $request->image != 'null') {
            //     $Item->image = $this->uploadImage($request->image, '/images/shops/');
            // }

            $Item->save();

            if($ItemMember){
                $ItemMember->shop = "Request";
                $ItemMember->save();
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

}
