<?php

namespace App\Http\Controllers;

use App\Models\RegisterImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RegisterImporterController extends Controller
{
    /**
     * ดึงข้อมูลร้านค้าทั้งหมด
     */
    public function getList()
    {
        $Item = RegisterImporter::get()->toArray();

        if (!empty($Item)) {
            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                if ($Item[$i]['id_card_picture']) {
                    $Item[$i]['id_card_picture'] = url($Item[$i]['id_card_picture']);
                }
                if ($Item[$i]['certificate_book_file']) {
                    $Item[$i]['certificate_book_file'] = url($Item[$i]['certificate_book_file']);
                }
                if ($Item[$i]['tax_book_file']) {
                    $Item[$i]['tax_book_file'] = url($Item[$i]['tax_book_file']);
                }
                if ($Item[$i]['logo_file']) {
                    $Item[$i]['logo_file'] = url($Item[$i]['logo_file']);
                }
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * ดึงข้อมูลแบบแบ่งหน้า (Pagination)
     */
    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;


        $col = ['id', 'comp_name', 'comp_tax', 'registered', 'address', 'province', 'district', 'sub_district', 'postal_code', 'authorized_person', 'authorized_person_phone', 'authorized_person_email', 'id_card_picture', 'certificate_book_file', 'tax_book_file', 'logo_file', 'created_at', 'updated_at'];
        $orderby = ['', 'comp_name', 'comp_tax', 'registered', 'address', 'province', 'district', 'sub_district', 'postal_code', 'authorized_person', 'authorized_person_phone', 'authorized_person_email', 'id_card_picture', 'certificate_book_file', 'tax_book_file', 'logo_file', 'created_at', 'updated_at'];

        $D = RegisterImporter::select($col);

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {
            $D->where(function ($query) use ($search, $col) {
                foreach ($col as $c) {
                    $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {
            $No = (($page - 1) * $length);
            for ($i = 0; $i < count($d); $i++) {
                $No = $No + 1;
                $d[$i]->No = $No;
                if ($d[$i]->image) {
                    $d[$i]->image = url($d[$i]->image);
                }
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
        $validator = Validator::make($request->all(), [
            'comp_name' => 'required|string|max:255',
            'comp_tax' => 'required|string|max:20',
            'registered' => 'required|boolean',
            'address' => 'required|string|max:255',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'sub_district' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'authorized_person' => 'required|string|max:255',
            'authorized_person_phone' => 'required|string|max:20',
            'authorized_person_email' => 'required|email|max:255',
            'id_card_picture' => 'nullable|string',
            'certificate_book_file' => 'nullable|string',
            'tax_book_file' => 'nullable|string',
            'logo_file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $Item = new RegisterImporter();
            $Item->comp_name = $request->comp_name;
            $Item->comp_tax = $request->comp_tax;
            $Item->registered = $request->registered;
            $Item->address = $request->address;
            $Item->province = $request->province;
            $Item->district = $request->district;
            $Item->sub_district = $request->sub_district;
            $Item->postal_code = $request->postal_code;
            $Item->authorized_person = $request->authorized_person;
            $Item->authorized_person_phone = $request->authorized_person_phone;
            $Item->authorized_person_email = $request->authorized_person_email;
            $Item->id_card_picture = $request->id_card_picture;
            $Item->certificate_book_file = $request->certificate_book_file;
            $Item->tax_book_file = $request->tax_book_file;
            $Item->logo_file = $request->logo_file;
            $Item->save();

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูลบริษัทสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RegisterImporter  $registerImporter
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = RegisterImporter::find($id);
        if ($Item) {
            if ($Item->id_card_picture) {
                $Item->id_card_picture = url($Item->id_card_picture);
            }
            if ($Item->certificate_book_file) {
                $Item->certificate_book_file = url($Item->certificate_book_file);
            }
            if ($Item->tax_book_file) {
                $Item->tax_book_file = url($Item->tax_book_file);
            }
            if ($Item->logo_file) {
                $Item->logo_file = url($Item->logo_file);
            }
          
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RegisterImporter  $registerImporter
     * @return \Illuminate\Http\Response
     */
    public function edit(RegisterImporter $registerImporter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RegisterImporter  $registerImporter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $Item = RegisterImporter::find($id);
        if (!$Item) {
            return response()->json(['error' => 'ไม่พบข้อมูลบริษัท'], 404);
        }

        DB::beginTransaction();
        try {
            $Item->comp_name = $request->comp_name ?? $Item->comp_name;
            $Item->comp_tax = $request->comp_tax ?? $Item->comp_tax;
            $Item->registered = $request->registered ?? $Item->registered;
            $Item->address = $request->address ?? $Item->address;
            $Item->province = $request->province ?? $Item->province;
            $Item->district = $request->district ?? $Item->district;
            $Item->sub_district = $request->sub_district ?? $Item->sub_district;
            $Item->postal_code = $request->postal_code ?? $Item->postal_code;
            $Item->authorized_person = $request->authorized_person ?? $Item->authorized_person;
            $Item->authorized_person_phone = $request->authorized_person_phone ?? $Item->authorized_person_phone;
            $Item->authorized_person_email = $request->authorized_person_email ?? $Item->authorized_person_email;
            $Item->id_card_picture = $request->id_card_picture ?? $Item->id_card_picture;
            $Item->certificate_book_file = $request->certificate_book_file ?? $Item->certificate_book_file;
            $Item->tax_book_file = $request->tax_book_file ?? $Item->tax_book_file;
            $Item->logo_file = $request->logo_file ?? $Item->logo_file;

            $Item->save();
            DB::commit();
            return $this->returnSuccess('อัปเดตข้อมูลบริษัทสำเร็จ', $Item);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RegisterImporter  $registerImporter
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $Item = RegisterImporter::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบบริการนี้', 404);
            }

            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }
}
