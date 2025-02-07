<?php

namespace App\Http\Controllers;

use App\Models\SubCategoryProduct;
use App\Models\CategoryProduct;
use App\Models\ProductImages;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SubCategoryProductController extends Controller
{
    public function getList($id)
    {
        $Item = SubCategoryProduct::where('category_product_id',$id)->get();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['category_product'] = CategoryProduct::find($Item[$i]['category_product_id']);


                $Item[$i]['products'] = Products::where('sub_category_product_id',$Item[$i]['id'])->get();
                foreach ($Item[$i]['products'] as $key => $value) {
                    $Item[$i]['products'][$key]['images'] = ProductImages::where('product_id', $value['id'])->get();
                    for ($n = 0; $n <= count($Item[$i]['products'][$key]['images']) - 1; $n++) {
                        $Item[$i]['products'][$key]['images'][$n]['image'] = url($Item[$i]['products'][$key]['images'][$n]['image']);
                    }

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

        $category_product_id = $request->category_product_id;

        $col = array('id', 'code', 'category_product_id','name', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'code', 'category_product_id','name', 'create_by','update_by', 'created_at', 'updated_at');

        $D = SubCategoryProduct::select($col);

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
                $d[$i]->category_product = CategoryProduct::find($d[$i]->category_product_id);

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
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            $Item = new SubCategoryProduct();
            $Item->code = $request->code;
            $Item->name = $request->name;
            $Item->category_product_id = $request->category_product_id;


            $Item->save();
            //

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
     * @param  \App\Models\SubCategoryProduct  $categoryProduct
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = SubCategoryProduct::where('id', $id)
            ->first();

        if ($Item) {
        
            $Item->products = Products::where('sub_category_product_id', $Item->id)->get();

            foreach ($Item->products as $key => $value) {
                $Item->products[$key]->images = ProductImages::where('product_id', $Item->products[$key]->id)->get();

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
     * @param  \App\Models\SubCategoryProduct  $categoryProduct
     * @return \Illuminate\Http\Response
     */
    public function edit(SubCategoryProduct $categoryProduct)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CategoryProduct  $categoryProduct
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('กรุณาระบุข้อมูลให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {
            $Item = SubCategoryProduct::find($id);
            $Item->code = $request->code;
            $Item->name = $request->name;
            $Item->category_product_id = $request->category_product_id;

            $Item->save();
            //

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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubCategoryProduct  $categoryProduct
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = SubCategoryProduct::find($id);
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

}
