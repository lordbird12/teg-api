<?php

namespace App\Http\Controllers;

use App\Models\ProductDraft;
use App\Models\ProductDraftImages;
use App\Models\ProductType;
use App\Models\StandardSize;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductDraftController extends Controller
{
    public function getList()
    {
        $Item = ProductDraft::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['images'] = ProductDraftImages::where('product_draft_id',$Item[$i]['id'])->get();
                foreach ($Item[$i]['images'] as $key => $value) {
                    if($Item[$i]['images'][$key]['image'])
                    $Item[$i]['images'][$key]['image'] = url($Item[$i]['images'][$key]['image']);
                }
                $Item[$i]['standard_size'] = StandardSize::find($Item[$i]['product_type_id']);
                $Item[$i]['product_type'] = ProductType::find($Item[$i]['standard_size_id']);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getPage(Request $request)
    {
        // รับค่าพารามิเตอร์สำหรับการแบ่งหน้าและค้นหา
        $columns = $request->columns;
        $length  = $request->length;
        $order   = $request->order;
        $search  = $request->search;
        $start   = $request->start;
        $page    = ($start / $length) + 1;

        // กำหนดคอลัมน์ที่ต้องการดึงข้อมูลจากตาราง product_drafts
        $col = [
            'id',
            'product_type_id',
            'product_name',
            'product_logo',
            'standard_size_id',
            'weight',
            'width',
            'height',
            'long',
            'cbm',
            'qty',
            'qty_box',
            'status',
            'create_by',
            'update_by',
            'created_at',
            'updated_at'
        ];

        // กำหนดคอลัมน์สำหรับการจัดเรียงข้อมูล (orderby)
        $orderby = [
            '',
            'product_type_id',
            'product_name',
            'product_logo',
            'standard_size_id',
            'weight',
            'width',
            'height',
            'long',
            'cbm',
            'qty',
            'qty_box',
            'status',
            'create_by',
            'update_by',
            'created_at',
            'updated_at'
        ];

        // สร้าง query เพื่อดึงข้อมูลจากตาราง product_drafts
        $query = ProductDraft::select($col);

        // หากมีค่า search ส่งมาจะค้นหาในทุกคอลัมน์ที่ระบุใน $col
        if (isset($search['value']) && $search['value'] != '') {
            $query->where(function ($q) use ($search, $col) {
                foreach ($col as $c) {
                    $q->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        // การจัดเรียงข้อมูลตามที่ส่งเข้ามาใน Request
        if (
            isset($order[0]['column']) &&
            isset($orderby[$order[0]['column']]) &&
            $orderby[$order[0]['column']] != ''
        ) {
            $query->orderBy($orderby[$order[0]['column']], $order[0]['dir']);
        } else {
            $query->orderBy('id', 'DESC');
        }

        // ดึงข้อมูลแบบแบ่งหน้า (Pagination)
        $data = $query->paginate($length, ['*'], 'page', $page);

        // แนบหมายเลขลำดับและข้อมูลรูปภาพที่เกี่ยวข้องกับแต่ละ Product Draft
        if ($data->isNotEmpty()) {
            $No = (($page - 1) * $length);
            foreach ($data as $index => $item) {
                $No++;
                $item->No = $No;
                // ดึงข้อมูลรูปภาพที่เกี่ยวข้องกับ Product Draft นี้
                $item->images = ProductDraftImages::where('product_draft_id', $item->id)->get();
                foreach ($item->images as $key => $value) {
                    if($item->images[$key]->image)
                    $item->images[$key]->image = url($item->images[$key]->image);
                }
                $item->standard_size = StandardSize::find($item->product_type_id);
                $item->product_type = ProductType::find($item->standard_size_id);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
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
        // Validate ฟิลด์ที่จำเป็นสำหรับ Product Draft และ Image
        $validator = Validator::make($request->all(), [
            'product_type_id'   => 'required|integer|exists:product_types,id',
            'product_name'      => 'nullable|string',
            'product_logo'      => 'nullable|string',
            'standard_size_id'  => 'required|integer|exists:standard_sizes,id',
            'weight'            => 'nullable|numeric',
            'width'             => 'nullable|numeric',
            'height'            => 'nullable|numeric',
            'long'              => 'nullable|numeric',
            'cbm'               => 'nullable|numeric',
            'qty'               => 'nullable|integer',
            'qty_box'           => 'nullable|integer',
            'status'            => 'nullable|in:Yes,No,Request',
            // ข้อมูล images คาดว่าจะเป็น Array ของข้อมูลรูปภาพ
            'images'            => 'nullable|array',
            'images.*.image_url'=> 'nullable|string|max:250',
            'images.*.image'    => 'nullable|string|max:250',
        ]);
    
        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            // สร้างข้อมูลในตาราง product_drafts
            $draft = new ProductDraft(); // ตรวจสอบให้แน่ใจว่าได้ import โมเดลถูกต้อง
            $draft->product_type_id  = $request->product_type_id;
            $draft->product_name     = $request->product_name;
            $draft->product_logo     = $request->product_logo;
            $draft->standard_size_id = $request->standard_size_id;
            $draft->weight           = $request->weight ?? 0;
            $draft->width            = $request->width ?? 0;
            $draft->height           = $request->height ?? 0;
            $draft->long             = $request->long ?? 0;
            $draft->cbm              = $request->cbm ?? 0;
            $draft->qty              = $request->qty ?? 0;
            $draft->qty_box          = $request->qty_box ?? 0;
            $draft->save();
    
            // หากมีการส่งข้อมูลรูปภาพมาด้วย ให้บันทึกข้อมูลในตาราง product_draft_images
            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $image) {
                    $draftImage = new ProductDraftImages();
                    $draftImage->product_draft_id = $draft->id;
                    $draftImage->image_url        = isset($image['image_url']) ? $image['image_url'] : null;
                    $draftImage->image            = isset($image['image']) ? $image['image'] : null;
                    $draftImage->save();
                }
            }
    
            DB::commit();
    
            return $this->returnSuccess('เพิ่มข้อมูล Product Draft สำเร็จ', $draft);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
    

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductDraft  $productDraft
     * @return \Illuminate\Http\Response
     */
    public function show(ProductDraft $productDraft)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProductDraft  $productDraft
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductDraft $productDraft)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductDraft  $productDraft
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate ข้อมูลที่จำเป็นสำหรับ Product Draft และ Image
        $validator = Validator::make($request->all(), [
            'product_type_id'    => 'required|integer|exists:product_types,id',
            'product_name'       => 'nullable|string',
            'product_logo'       => 'nullable|string',
            'standard_size_id'   => 'required|integer|exists:standard_sizes,id',
            'weight'             => 'nullable|numeric',
            'width'              => 'nullable|numeric',
            'height'             => 'nullable|numeric',
            'long'               => 'nullable|numeric',
            'cbm'                => 'nullable|numeric',
            'qty'                => 'nullable|integer',
            'qty_box'            => 'nullable|integer',
            'status'             => 'nullable|in:Yes,No,Request',
            // ข้อมูล images คาดว่าจะเป็น Array ของข้อมูลรูปภาพ
            'images'             => 'nullable|array',
            'images.*.image_url' => 'nullable|string|max:250',
            'images.*.image'     => 'nullable|string|max:250',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            // ค้นหาข้อมูล Product Draft ที่ต้องการอัปเดต
            $draft = ProductDraft::find($id);
            if (!$draft) {
                return $this->returnErrorData('ไม่พบข้อมูล Product Draft นี้', 404);
            }

            // อัปเดตข้อมูลในตาราง product_drafts
            $draft->product_type_id  = $request->product_type_id;
            $draft->product_name     = $request->product_name;
            $draft->product_logo     = $request->product_logo;
            $draft->standard_size_id = $request->standard_size_id;
            $draft->weight           = $request->weight ?? 0;
            $draft->width            = $request->width ?? 0;
            $draft->height           = $request->height ?? 0;
            $draft->long             = $request->long ?? 0;
            $draft->cbm              = $request->cbm ?? 0;
            $draft->qty              = $request->qty ?? 0;
            $draft->qty_box          = $request->qty_box ?? 0;
            $draft->save();

            // หากมีการส่งข้อมูลรูปภาพมาด้วย
            if ($request->has('images') && is_array($request->images)) {
                // ลบข้อมูลรูปภาพเก่าที่เกี่ยวข้องกับ Product Draft นี้
                ProductDraftImages::where('product_draft_id', $draft->id)->delete();

                // เพิ่มข้อมูลรูปภาพใหม่
                foreach ($request->images as $image) {
                    $draftImage = new ProductDraftImages();
                    $draftImage->product_draft_id = $draft->id;
                    $draftImage->image_url        = isset($image['image_url']) ? $image['image_url'] : null;
                    $draftImage->image            = isset($image['image']) ? $image['image'] : null;
                    $draftImage->save();
                }
            }

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูล Product Draft สำเร็จ', $draft);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductDraft  $productDraft
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        DB::beginTransaction();

        try {

            $Item = ProductDraft::find($id);
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

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e, 404);
        }
    }
}
