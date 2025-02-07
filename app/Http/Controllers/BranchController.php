<?php

namespace App\Http\Controllers;

use App\Models\member;
use App\Models\Branch;
use App\Models\CategoryProduct;
use App\Models\SubCategoryProduct;
use App\Models\ProductImages;
use App\Models\Products;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function getList()
    {
        $Item = Branch::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                if ($Item[$i]['image']) {
                    $Item[$i]['image'] = url($Item[$i]['image']);
                }
                $Item[$i]['products'] = Products::where('branche_id',$Item[$i]['id'])->get();
                foreach ($Item[$i]['products'] as $key => $value) {
                    $Item[$i]['products'][$key]['images'] = ProductImages::where('product_id', $value['id'])->get();
                    for ($n = 0; $n <= count($Item[$i]['products'][$key]['images']) - 1; $n++) {
                        $Item[$i]['products'][$key]['images'][$n]['image'] = url($Item[$i]['products'][$key]['images'][$n]['image']);
                    }

                    $Item[$i]['products'][$key]['category_product'] = CategoryProduct::find($Item[$i]['products'][$key]['category_product']);

                    $Item[$i]['products'][$key]['sub_category_product'] = SubCategoryProduct::find($Item[$i]['products'][$key]['sub_category_product']);
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


        $col = array('id','code','name','image', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('','code', 'name','image','create_by', 'update_by', 'created_at', 'updated_at');

        $D = Branch::select($col);


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
                $d[$i]->member = member::find($d[$i]->member_id);

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

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            $Item = new Branch();
            $prefix = "#B-";
            $id = IdGenerator::generate(['table' => 'news', 'field' => 'code', 'length' => 9, 'prefix' => $prefix]);
            $Item->code = $id;
            $Item->name = $request->name;
            $Item->address = $request->address;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/branchs/');
            }

            $Item->save();

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
     * @param  \App\Models\Branch  $shop
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = Branch::where('id', $id)
            ->first();

        if ($Item) {
            $Item->products = Products::where('shop_id', $Item->id)->get();
			foreach($Item->products as $key => $value) {
				$Item->products[$key]->images = ProductImages::where('product_id',$value['id'])->get();
				for ($n = 0; $n <= count($Item->products[$key]->images) - 1; $n++) {
                        $Item->products[$key]->images[$n]->image = url($Item->products[$key]->images[$n]->image);
                }
			}
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Branch  $shop
     * @return \Illuminate\Http\Response
     */
    public function edit(shop $shop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Branch  $shop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, shop $shop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Branch  $shop
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = Branch::find($id);
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
        if (!isset($request->id)) {
            return $this->returnErrorData('กรุณาเลือกร้านให้ถูกต้อง', 404);
        }

        DB::beginTransaction();

        try {
            $Item = Branch::find($request->id);
            
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลร้านค้านี้ในระบบ', 404);
            }
           
            $Item->name = $request->name;
            $Item->address = $request->address;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/branchs/');
            }

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
}
