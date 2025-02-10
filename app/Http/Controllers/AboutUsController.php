<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AboutUsController extends Controller
{
    public function getList()
    {
        $Item = AboutUs::find(1);

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
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
            $about = new AboutUs();
            $about->detail = $request->detail;
            $about->title_box = $request->title_box;
            $about->body_box = $request->body_box;
            $about->footer_box = $request->footer_box;
            $about->phone = $request->phone;
            $about->email = $request->email;
            $about->wechat = $request->wechat;
            $about->line = $request->line;
            $about->facebook = $request->facebook;

            $about->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูลสำเร็จ', $about);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AboutUs  $aboutUs
     * @return \Illuminate\Http\Response
     */
    public function show(AboutUs $aboutUs)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AboutUs  $aboutUs
     * @return \Illuminate\Http\Response
     */
    public function edit(AboutUs $aboutUs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AboutUs  $aboutUs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $about = AboutUs::find(1);
            if (!$about) {
                return $this->returnErrorData('ไม่พบข้อมูล', 404);
            }

            $about->detail = $request->detail;
            $about->title_box = $request->title_box;
            $about->body_box = $request->body_box;
            $about->footer_box = $request->footer_box;
            $about->phone = $request->phone;
            $about->email = $request->email;
            $about->wechat = $request->wechat;
            $about->line = $request->line;
            $about->facebook = $request->facebook;
            $about->save();

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $about);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AboutUs  $aboutUs
     * @return \Illuminate\Http\Response
     */
    public function destroy(AboutUs $aboutUs)
    {
        //
    }
}
