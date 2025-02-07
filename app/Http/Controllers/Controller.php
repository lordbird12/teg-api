<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Models\Device;
use App\Models\Log;
use App\Models\Notify_log;
use App\Models\Notify_log_user;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\DB;
use OneSignal;
use Illuminate\Support\Facades\Response;
use Mpdf\Barcode\BarcodeGeneratorFactory;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $notification;

    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }

    public function returnSuccess($massage, $data)
    {

        return response()->json([
            'code' => strval(200),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 200);
    }

    public function returnUpdate($massage)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => [],
        ], 201);
    }

    public function returnUpdateReturnData($massage, $data)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 201);
    }

    public function returnErrorData($massage, $code)
    {
        return response()->json([
            'code' => strval($code),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 404);
    }

    public function returnError($massage)
    {
        return response()->json([
            'code' => strval(401),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 401);
    }

    public function Log($userId, $description, $type)
    {
        $Log = new Log();
        $Log->user_id = $userId;
        $Log->description = $description;
        $Log->type = $type;
        $Log->save();
    }

    public function sendMail($email, $data, $title, $type)
    {

        $mail = new SendMail($email, $data, $title, $type);
        Mail::to($email)->send($mail);
    }

    public function sendLine($line_token, $text)
    {

        $sToken = $line_token;
        $sMessage = $text;

        $chOne = curl_init();
        curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chOne, CURLOPT_POST, 1);
        curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
        $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '');
        curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chOne);

        curl_close($chOne);
    }

    public function uploadImages(Request $request)
    {

        $image = $request->image;
        $path = $request->path;

        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path('/thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $img = Image::make($image->path());
        $img->save($destinationPath . '/' . $input['imagename']);
        $destinationPath = public_path($path);
        $image->move($destinationPath, $input['imagename']);

        return $this->returnSuccess('ดำเนินการสำเร็จ', $path . $input['imagename']);
    }

    public function uploadImage($image, $path)
    {
        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path('/thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $img = Image::make($image->path());
        $img->save($destinationPath . '/' . $input['imagename']);
        $destinationPath = public_path($path);
        $image->move($destinationPath, $input['imagename']);

        return $path . $input['imagename'];
    }

    public function uploadFile(Request $request)
    {

        try {

            if ($request->hasFile('file')) {

                $files = $request->file('file');
                $filePath = $request->file_path;
                $fileName = $request->file_name;

                $path_files = [];

                $destinationPath = public_path($request->path);

                $objScan = scandir($destinationPath);

                $file = $files;
                $filename = $file->getClientOriginalName();

                $str_filename = explode('.', $filename);
                $filetype = $str_filename[1];

                $dt = date("Y-m-d H:i:s");
                $key_gen = "$dt" . '_' . $fileName . "";
                $name = md5(uniqid($key_gen, true)) . '.' . "$filetype";

                $file->move($destinationPath . '/' . $filePath, $name);
                $path_files['name'] = $fileName;
                $path_files['path'] = $name;

                return $path_files;
            } else {

                return $this->returnErrorData('File Not Found', 404);
            }
        } catch (\Throwable $e) {

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function uploadFile($file)
    // {
    // $input['filename'] = time() . '.' . $file->extension();

    // $destinationPath = public_path('/file_thumbnail');
    // if (!File::exists($destinationPath)) {
    //     File::makeDirectory($destinationPath, 0777, true);
    // }

    // $destinationPath = public_path($path);
    // $file->move($destinationPath, $input['filename']);

    // return $path . $input['filename'];

    // $file = $request->getClientOriginalName();
    // $path = $request->getPath();

    // $input['filename'] = time() . '.' . $request->extension();

    // $destinationPath = public_path('/file_thumbnail');
    // if (!File::exists($destinationPath)) {
    //     File::makeDirectory($destinationPath, 0777, true);
    // }

    // $destinationPath = public_path($path);
    // $file->move($destinationPath, $file);

    // return $path . $input['filename'];
    // }

    // public function uploadFile($file, $path)
    // {
    //     $input['filename'] = time() . '.' . $file->extension();
    //     $destinationPath = public_path('/file_thumbnail');
    //     if (!File::exists($destinationPath)) {
    //         File::makeDirectory($destinationPath, 0777, true);
    //     }

    //     $destinationPath = public_path($path);
    //     $file->move($destinationPath, $input['filename']);

    //     return $path . $input['filename'];
    // }

    public function getDropDownYear()
    {
        $Year = intval(((date('Y')) + 1) + 543);

        $data = [];

        for ($i = 0; $i < 10; $i++) {

            $Year = $Year - 1;
            $data[$i]['year'] = $Year;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDropDownProvince()
    {

        $province = array("กระบี่", "กรุงเทพมหานคร", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บุรีรัมย์", "บึงกาฬ", "ปทุมธานี", "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", "เพชรบุรี", "เพชรบูรณ์", "แพร่", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยโสธร", "ยะลา", "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อยุธยา", "อ่างทอง", "อำนาจเจริญ", "อุดรธานี", "อุตรดิตถ์", "อุทัยธานี", "อุบลราชธานี");

        $data = [];

        for ($i = 0; $i < count($province); $i++) {

            $data[$i]['province'] = $province[$i];
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDownloadFomatImport($params)
    {

        $file = $params;
        $destinationPath = public_path() . "/fomat_import/";

        return response()->download($destinationPath . $file);
    }

    public function checkDigitMemberId($memberId)
    {

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {

            $sum += (int) ($memberId[$i]) * (13 - $i);
        }

        if ((11 - ($sum % 11)) % 10 == (int) ($memberId[12])) {
            return 'true';
        } else {
            return 'false';
        }
    }

    public function genCode(Model $model, $prefix, $number)
    {

        $countPrefix = strlen($prefix);
        $countRunNumber = strlen($number);

        //get last code
        $Property_type = $model::orderby('code', 'desc')->first();
        if ($Property_type) {
            $lastCode = $Property_type->code;
        } else {
            $lastCode = $prefix . $number;
        }

        $codelast = substr($lastCode, $countPrefix, $countRunNumber);

        $newNumber = intval($codelast) + 1;
        $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);

        $runNumber = $prefix . $Number;

        return $runNumber;
    }


    // public function dateBetween($dateStart, $dateStop)
    // {
    //     $datediff = strtotime($dateStop) - strtotime($this->dateform($dateStart));
    //     return abs($datediff / (60 * 60 * 24));
    // }

    // public function log_noti($Title, $Description, $Url, $Pic, $Type)
    // {
    //     $log_noti = new Log_noti();
    //     $log_noti->title = $Title;
    //     $log_noti->description = $Description;
    //     $log_noti->url = $Url;
    //     $log_noti->pic = $Pic;
    //     $log_noti->log_noti_type = $Type;

    //     $log_noti->save();
    // }

    /////////////////////////////////////////// seach datatable  ///////////////////////////////////////////

    public function withPermission($query, $search)
    {

        $col = array('id', 'name', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('permission', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withMember($query, $search)
    {

        // $col = array('id', 'member_group_id','code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        // $query->orWhereHas('member', function ($query) use ($search, $col) {

        //     $query->Where(function ($query) use ($search, $col) {

        //         //search datatable
        //         $query->orwhere(function ($query) use ($search, $col) {
        //             foreach ($col as &$c) {
        //                 $query->orWhere($c, 'like', '%' . $search['value'] . '%');
        //             }
        //         });
        //     });

        // });

        // return $query;
    }


    public function withInquiryType($query, $search)
    {

        $col = array('id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('inquiry_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyType($query, $search)
    {

        $col = array('id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertySubType($query, $search)
    {

        $col = array('id', 'property_type_id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyAnnouncer($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_announcer', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyColorLand($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_color_land', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyOwnership($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_ownership', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyFacility($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_facility', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertySubFacility($query, $search)
    {

        $col = array('id', 'property_facility_id', 'name', 'icon', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_facility', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                $query = $this->withPropertyFacility($query, $search);
            });
        });

        return $query;
    }

    public function withPropertySubFacilityExplend($query, $search)
    {

        $col = array('id', 'property_sub_facility_id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_facility_explend', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                $query = $this->withPropertySubFacility($query, $search);
            });
        });

        return $query;
    }

    public function pay_slip($id)
    {
        $qurry = [];

        $tax_ID = '0-6735-63000-95-1';          //เลขประจำตัวผู้เสียภาษีอากร
        $name_pay = 'นาสาวนาตยา นราวัฒน์';        //ชื่อพนักงาน
        $week = '16-30/9/2566';                 //วิกที่
        $position = 'ผู้บริหารร้าน';                //ตำแหน่ง
        $pay_date = '30/9/2566';                //วันที่สั่งจ่าย

        $salary = 15000;                        //เงินเดือน
        $late = 228;                            //หักสาย
        $ot = 20;                               //ล่วงเวลา
        $Leave = 1000;                          //หักลา
        $workholidays = '-';                    //ทำงานในวันหยุด
        $ahead = '-';                           //หักเบิกล่วงหน้า
        $insurance = 475;                       //หักค่าประกันสังคม
        $in = 15020;                            //รวมรายการได้
        $out = 1703;                            //รวมรายการหัก
        $income = 13317;                        //เงินได้สุทธิ

        $name_save = 'นางสาวอริษา แสนในเมือง';    //ผู้บันทึก
        $name_approve = 'นาสาวนาตยา นราวัฒน์';    //ผู้อนุมัติจ่าย

        $content = '
            <div style="font-size: 24px; text-align: center;"> <b>ห้างหุ่นส่วนจำกัด ส.สปีดออโต้ปากน้ำใหญ่(สำหนักงานใหญ่) </div>
            <div style="font-size: 24px; text-align: center;"> <b>251/2 หมู่ 11 ตำบลนาป่า อำเภอเมือง จังหวัดเพชรบูรณ์ 67000 โทร. 081-239-3070</div>
            <div style="font-size: 24px; text-align: center;"> <b>เลขประจำตัวผู้เสียภาษีอากร ' . $tax_ID . '</div>
            <div style="font-size: 24px; text-align: center;"> <b>ใบแจ้งราย PAY SLIP</div>
            <style>
                table {
                    border-collapse: collapse;
                    width:100%;
                }
                th, td {
                    border: 1px solid black;
                    padding: 5px;
                    font-size: 20px;

                }
            </style>
            <table>
                <tr>
                    <td style="width:10%; border: none;">ชื่อพนักงาน</td>
                    <td style="width:20%; border: none;">' . $name_pay . '</td>
                    <td style="width:10%; border: none;"></td>
                    <td style="width:15%; border: none;"></td>
                    <td style="width:15%; border: none;"></td>
                    <td style="width:10%; text-align: center; border: none;">วิกที่</td>
                    <td style="width:20%; border: none;">' . $week . '</td>
                </tr>
                <tr>
                    <td style="border: none;">ตำแหน่ง</td>
                    <td style="border: none;">' . $position . '</td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="text-align: center; border: none;">วันที่สั่งจ่าย</td>
                    <td style="border: none;">' . $pay_date . '</td>
                </tr>
                <tr>
                    <th colspan="2">รายได้</th>
                    <th>จำนวนเงิน</th>
                    <th colspan="2">รายการหลัก</th>
                    <th>จำนวนเงิน</th>
                    <th>หมายเหตุ</th>
                </tr>
            ';
        $content .= '
                <tr>
                    <td colspan="2">เงินเดือน</td>
                    <td style="text-align: right;">' . $salary . '</td>
                    <td colspan="2" >หักสาย</td>
                    <td style="text-align: right;">' . $late . ' </td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2" >ล่วงเวลา</td>
                    <td style="text-align: right;">' . $ot . '</td>
                    <td colspan="2" >หักลา 1 วัน </td>
                    <td style="text-align: right;">' . $Leave . '</td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">ทำงานในวันหยุด</td>
                    <td style="text-align: right;">' . $workholidays . '</td>
                    <td colspan="2" >หักเบิกล้วงหน้า </td>
                    <td style="text-align: right;">' . $ahead . '</td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td style="text-align: right;"></td>
                    <td colspan="2" >หักค่าประกันสังคม </td>
                    <td style="text-align: right;">' . $insurance . '</td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td ></td>
                    <td colspan="2" ></td>
                    <td style="text-align: right;"></td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td ></td>
                    <td colspan="2" ></td>
                    <td style="text-align: right;"></td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td ></td>
                    <td colspan="2" ></td>
                    <td style="text-align: right;"></td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td ></td>
                    <td colspan="2" ></td>
                    <td style="text-align: right;"></td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td ></td>
                    <td colspan="2" ></td>
                    <td style="text-align: right;"></td>
                    <td ></td>
                </tr>
            ';

        $content .= '
                <tr>
                    <td colspan="2" rowspan="2" style="text-align: right;">รวมรายการได้</td>
                    <td rowspan="2" style="text-align: right;">' . $in . '</td>
                    <td colspan="2" rowspan="2" style="text-align: right;">รวมรายการหัก</td>
                    <td rowspan="2" style="text-align: right;">' . $out . '</td>
                    <td style="text-align: center;">เงินได้สุทธิ</td>
                </tr>
                <tr>
                    <td style="text-align: center;">' . $income . '</td>
                </tr>
            </table>
            <br>
            <table>
                <tr>
                    <td style="border: none; width:10%;">&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;</td>
                    <td style="border: none; width:45%;">ผู้บันทึก............................................................<br>
                    &nbsp;&nbsp;&nbsp;(' . $name_save . ') </td>
                    <td style="border: none; width:45%;">ผู้รับเงิน............................................................<br>
                    &nbsp;&nbsp;&nbsp;(' . $name_pay . ')</td>
                </tr>
                <tr>
                    <td style="border: none; width:10%;"></td>
                    <td style="border: none; width:45%;">ผู้อนุมัติจ่าย.....................................................<br>
                    &nbsp;&nbsp;&nbsp;(' . $name_approve . ')</td>
                </tr>
            </table>
        ';
        //PDF
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $mpdf = new \Mpdf\Mpdf([
            'fontDir' => array_merge($fontDirs, [
                base_path() . '/custom/font/directory',
            ]),
            'fontdata' => $fontData + [ // lowercase letters only in font key
                'th-sarabun-it' => [
                    'R' => 'THSarabunIT๙.ttf',
                    'I' => 'THSarabunIT๙ Italic.ttf',
                    'B' => 'THSarabunIT๙ Bold.ttf',
                    'BI' => 'THSarabunIT๙ BoldItalic.ttf',
                ],
                'th-sarabun' => [
                    'R' => 'THSarabun.ttf',
                    'I' => 'THSarabun Italic.ttf',
                    'B' => 'THSarabun Bold.ttf',
                    'BI' => 'THSarabun BoldItalic.ttf',
                ],
            ],
            'default_font' => 'th-sarabun',
            'mode' => 'utf-8',
            'format' => 'A4',
            // 'default_font_size' => 12,
            // 'default_font' => 'sarabun',
            // 'margin_left' => 5,
            // 'margin_right' => 5,
            // 'margin_top' => 5,
            // 'margin_bottom' => 5,
            // 'margin_header' => 5,
            // 'margin_footer' => 5,
        ]);

        $mpdf->SetTitle('pay_slip');
        $mpdf->AddPage();
        $mpdf->WriteHTML($content);
        $mpdf->Output();
    }

    /////////////////////////////////////////// seach datatable  ///////////////////////////////////////////

    // public function sendNotifyAll($title, $body, $target_id, $type)
    // {

    //     $device =  Device::get();

    //     $notiToken = [];
    //     $notifyUser = [];

    //     for ($i = 0; $i < count($device); $i++) {

    //         $notiToken[] = $device[$i]->notify_token;
    //         // $notifyUser[] = $device[$i]->user_id;
    //         $notifyUser[] = $device[$i]->user_d;
    //     }

    //     $FcmToken = array_values(array_unique($notiToken));


    //     for ($i = 0; $i < count($FcmToken); $i++) {

    //         try {

    //             $message = CloudMessage::fromArray([
    //                 'token' => $FcmToken[$i],
    //                 'notification' => [
    //                     'title' => $title,
    //                     'body' => $body
    //                 ],
    //             ]);

    //             $this->notification->send($message);
    //         } catch (\Throwable $e) {
    //             dd($e);
    //         }
    //     }



    //     //add log
    //     $this->addNotifyLog($title, $body, $target_id, $type, $notifyUser);
    // }

    public function sendNotifyAll($title = 'No Title', $body = 'No Body', $target_id = null, $type = 'general', $image = "https://makok.dev-asha9.com/public/logo.jpg")
    {
        try {
            // Fetch all devices
            $devices = Device::whereNotNull('notify_token')->pluck('notify_token', 'user_id')->toArray();

            if (empty($devices)) {
                return response()->json(['status' => 'error', 'message' => 'No devices found'], 404);
            }

            // Extract unique FCM tokens
            $FcmTokens = array_values(array_unique($devices));

            // echo $FcmTokens;
            // exit;
            // Prepare notifications
            $params = [
                'include_player_ids' => $FcmTokens, // Send to all devices
                'android_accent_color' => 'FF4D00',
                'small_icon' => 'default_icon',
                'content_available' => true,
                'headings' => [
                    'en' => $title,
                    'th' => $title,
                ],
                'contents' => [
                    'en' => $body,
                    'th' => $body,
                ],
                'big_picture' => $image,
                'ios_badgeType' => 'Increase',
                'ios_badgeCount' => 1,
            ];

            // Send notification
            $response = OneSignal::sendNotificationCustom($params);

            // Log notification if needed
            // $this->addNotifyLog($title, $body, $target_id, $type, array_keys($devices));

            return response()->json([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'response' => $response,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while sending notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendNotify($title, $body, $target_id, $type, $qouta_id)
    {

        $device =  Device::with('user')
            ->with('frammer')
            ->where('qouta_id', $qouta_id)
            ->get();

        $notiToken = [];
        $notifyUser = [];

        for ($i = 0; $i < count($device); $i++) {

            $notiToken[] = $device[$i]->notify_token;
            // $notifyUser[] = $device[$i]->user_id;
            $notifyUser[] = $device[$i]->qouta_id;
        }

        $FcmToken = array_values(array_unique($notiToken));
        $NotifyUser = array_values(array_unique($notifyUser));

        for ($i = 0; $i < count($FcmToken); $i++) {
            try {
                $message = CloudMessage::fromArray([
                    'token' => $FcmToken[$i],
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                ]);

                $this->notification->send($message);
            } catch (\Throwable $e) {
                //
            }
        }




        //add log
        $this->addNotifyLog($title, $body, $target_id, $type, $NotifyUser);
    }

    public function addNotifyLog($title, $body, $target_id, $type, $NotifyUser)
    {

        $Notify_log = new  Notify_log();
        $Notify_log->title = $title;
        $Notify_log->detail = $body;
        $Notify_log->target_id = $target_id;
        $Notify_log->type = $type;
        $Notify_log->save();

        $result = array_unique($NotifyUser);
        sort($result); // เรียงลำดับ index ตามค่า

        //add notify user
        for ($i = 0; $i < count($result); $i++) {
            $Notify_log_user = new  Notify_log_user();
            $Notify_log_user->notify_log_id =  $Notify_log->id;
            // $Notify_log_user->user_id = $result[$i];
            $Notify_log_user->user_id = $result[$i];
            $Notify_log_user->read = false;

            $Notify_log_user->save();
        }

        return $Notify_log;
    }

    public function getNetBalance($member_id)
    {
        $totalIn = DB::table('wallet_transactions')->where('type', 'I')->where('member_id', $member_id)->sum('amount');
        $totalOut = DB::table('wallet_transactions')->where('type', 'O')->where('member_id', $member_id)->sum('amount');
        $netBalance = $totalIn - $totalOut;

        return $netBalance;
    }

    public function fullreceipt()
    {

        $items = [];

        for ($i = 1; $i <= 8; $i++) { // สร้าง 10 รายการ
            $items[] = [
                'id' => $i,
                'name' => 'Product ' . $i,
                'unit' => rand(1, 50),  // จำนวนสุ่ม 1 - 100
                'unitprice' => rand(50, 500), // ราคาต่อหน่วยสุ่ม 50 - 500
            ];
        }
        $randoms = 1; // สุ่มค่า 0 หรือ 1
        $check = '';

        switch ($randoms) {
            case 1:
                $check = "&#9745;"; // ☑ (ถูกเลือก)
                break;
            case 0:
            default:
                $check = "&#9744;"; // ☐ (ไม่ถูกเลือก)
                break;
        }
        $customername = 'บริษัท พี ยู เอ็น อินเทลลิเจนท์ จำกัด';
        $customeraddress = 'เลขที่ 145/161 ซอยคู้บอน 27/7 ถนนคู้บอน แขวงท่าแร้งเขตบางเขน กรุงเทพมหานคร 10220';
        $customertaxid = '0105557083391';
        $customerref = 'IVT-201910001';
        $issuedate = '01/10/2019';
        $no = 'RT-20191000005';
        $attention = 'Keng';

        $issueexit = 'Thai Express Global Cargo Co.,Ltd';
        $addressissuer = 'อาคาร ไทยทาวเวอร์ ห้องที่ 145/161 ซอย27/7 ถนนคู้บอน แขวงท่าแร้ง เขตบางเขน กรุงเทพมหานคร 10220';
        $email = 'Sale@teglogistics.net';
        $w = 'https://www.tegcargo.com/';
        $taxid = '0105557123422';
        $preparedby = 'Panupong Palakawong';


        $imagePath = storage_path('app/public/logo-gp3-01-1980x1980.png');

        if (file_exists($imagePath)) {
            $type = pathinfo($imagePath, PATHINFO_EXTENSION);
            $data = file_get_contents($imagePath);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
            $base64 = ''; // หรือใส่ URL ของโลโก้สำรอง
        }

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'fontDir' => array_merge($fontDirs, [
                base_path() . '/custom/font/directory',
            ]),
            'fontdata' => $fontData + [
                'th-sarabun' => [
                    'R' => 'THSarabun.ttf',
                    'I' => 'THSarabun Italic.ttf',
                    'B' => 'THSarabun Bold.ttf',
                    'BI' => 'THSarabun BoldItalic.ttf',
                ],
            ],
            'default_font' => 'th-sarabun',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5,
            'orientation' => 'P',
        ]);

        $mpdf->SetTitle('ใบเสร็จแบบเต็ม');

        $itemsPerPage = 15;
        $totalItems = count($items);
        $pages = ceil($totalItems / $itemsPerPage);
        $TotalAmount = 0;
        $TotalAmounts = 0;

        for ($page = 0; $page < $pages; $page++) {
            if ($page > 0) {
                $mpdf->AddPage();
            }
            $html = '

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width:20%;font-size: 28;font-weight: bold;">
                <p>ใบเสร็จรับเงิน</p>
                <p>Receipt</p></td>
                <td style="width:50%;text-align:left;">ต้นฉบับ (Original)</td>
                <td style="width:30%;text-align:right;padding-right:50px;">
                    <img src="' . $base64 . '" alt="logo" style="width:100px;height:auto;">
                </td>
            </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse;margin-top:10px;">
            <tr>
                <td style="width:20%;font-size: 16;"><strong>ลูกค้า</strong> / Customer</td>
                <td style="width:50%;font-size: 16;">' . $customername . '</td>
                <td style="width:10%;font-size: 16;"><strong>เลข</strong> / No.</td>
                <td style="width:20%;font-size: 16;">' . $no . '</td>
            </tr>
            <tr>
                <td style="width:20%;font-size: 16;"><strong>ที่อยู่</strong> / Address</td>
                <td style="width:50%;font-size: 16;">' . nl2br(htmlspecialchars(substr($customeraddress, 0, 148))) . '</td>
                <td style="width:10%;font-size: 16;"><strong>วันที่ </strong>/ Issue</td>
                <td style="width:20%;font-size: 16;">' . $issuedate . '</td>
            </tr>';
            if (strlen($customeraddress) > 100) { // เพิ่มแถวใหม่ถ้าที่อยู่ยาว
                $html .= '
            <tr>
                <td style="width:20%;font-size: 16;"></td>
                <td style="width:50%;font-size: 16;">' . nl2br(htmlspecialchars(substr($customeraddress, 148, 300))) . '</td>
                <td style="width:10%;font-size: 16;"><strong>อ้างอิง</strong> / Ref</td>
                <td style="width:20%;font-size: 16;">' . $customerref . '</td>
            </tr>';
            } else {
                $html .= '
            <tr>
                <td style="width:20%;font-size: 16;"></td>
                <td style="width:50%;font-size: 16;"></td>
                <td style="width:10%;font-size: 16;"><strong>อ้างอิง</strong> / Ref</td>
                <td style="width:20%;font-size: 16;">' . $customerref . '</td>
            </tr>';
            }
            $html .= '
            </table>
            <table style="width: 100%; border-collapse: collapse;margin-bottom:10px;">
            <tr>
                <td style="width:20%;font-size: 16;"><strong>เลขผู้เสียภาษี</strong> / Tax ID</td>
                <td style="width:20%;font-size: 16;">' . $customertaxid . '</td>
                <td style="width:60%;font-size: 16;"><strong>E</strong>:-</td>
            </tr>
            <tr>
                <td style="width:20%;font-size: 16;border-bottom: 1px solid black;"><strong>ผู้ติดต่อ</strong> / Attention</td>
                <td style="width:20%;font-size: 16;border-bottom: 1px solid black;">' . $attention . '</td>
                <td style="width:60%;font-size: 16;border-bottom: 1px solid black;"><strong>T</strong>:-</td>
            </tr>
            </table>
            <table style="width: 100%; border-collapse: collapse;margin-top:10px;">
                <tr>
                    <td style="width:10%;font-size: 16;"><strong>ผู้ออก</strong></td>
                    <td style="width:50%;font-size: 16;">' . $issueexit . '</td>
                    <td style="width:20%;font-size: 16;"><strong>เลขผู้เสียภาษี</strong> / Tax ID</td>
                    <td style="width:20%;font-size: 16;">' . $taxid . '</td>
                </tr>
                <tr>
                    <td style="width:10%;font-size: 16;">issuer</td>
                    <td style="width:50%;font-size: 16;">' . nl2br(htmlspecialchars(substr($addressissuer, 0, 155))) . '</td>
                    <td style="width:20%;font-size: 16;"><strong>จัดเตรียมโดย</strong> / Prepared by</td>
                    <td style="width:20%;font-size: 16;">' . $preparedby . '</td>
                </tr>';
            if (strlen($addressissuer) > 155) { // เพิ่มแถวใหม่ถ้าที่อยู่ยาว
                $html .= '
                <tr>
                    <td style="width:10%;font-size: 16;"></td>
                    <td style="width:50%;font-size: 16;">' . nl2br(htmlspecialchars(substr($addressissuer, 155, 300))) . '</td>
                    <td style="width:20%;font-size: 16;"><strong>T</strong>: 25</td>
                    <td style="width:20%;font-size: 16;"><strong>E</strong>:' . $email . '</td>
                </tr>';
            } else {
                $html .= '
                    <tr>
                        <td style="width:10%;font-size: 16;"></td>
                        <td style="width:50%;font-size: 16;"></td>
                        <td style="width:20%;font-size: 16;"><strong>T</strong>: 25</td>
                        <td style="width:20%;font-size: 16;"><strong>E</strong>:</td>
                    </tr>';
            }
            $html .= '
                <tr>
                    <td style="width:10%;font-size: 16;"></td>
                    <td style="width:50%;font-size: 16;"></td>
                    <td colspan="2" style="width:20%;font-size: 16;"><strong>W</strong>: https://peakengine.com</td>

                </tr>
            </table>
            <table style="width: 100%; border-collapse: collapse;margin-top:10px;">
            <thead>
                <tr>
                    <td style="width:10%;font-size: 16;border-top: 1px solid black;border-bottom: 1px solid black;padding-left: 5px;">
                        <p style="font-weight: bold;">รหัส</p>
                        <p>ID no.</p>
                    </td>
                    <td style="width:45%;font-size: 16;border: 1px solid black;padding-left: 5px;">
                        <p style="font-weight: bold;">คำอธิบาย</p>
                        <p>Description</p>
                    </td>
                    <td style="width:10%;font-size: 16;border: 1px solid black;padding-left: 5px;">
                        <p style="font-weight: bold;">จำนวน</p>
                        <p>Quantity</p>
                    </td>
                    <td style="width:12%;font-size: 16;border: 1px solid black;padding-left: 5px;">
                        <p style="font-weight: bold;">ราคาต่อหน่วย</p>
                        <p>Unit Price</p>
                    </td>
                    <td style="width:8%;font-size: 16;border: 1px solid black;text-align: center;">
                        <p style="font-weight: bold;">ภาษี</p>
                        <p>VAT</p>
                    </td>
                    <td style="width:15%;font-size: 16;border-top: 1px solid black;border-bottom: 1px solid black;padding-left: 5px;">
                        <p style="font-weight: bold;">มูลค่าก่อนภาษี</p>
                        <p>Pre-Tax Amount</p>
                    </td>
                </tr>
                </thead>
                <tbody>';
            for ($i = $page * $itemsPerPage; $i < min(($page + 1) * $itemsPerPage, $totalItems); $i++) {
                // return $services[$i];
                $product = $items[$i];
                $sum = (floatval(str_replace(',', '', $product['unitprice'])) * intval($product['unit']));
                $html .= '
                            <tr id="body">
                                <td style="text-align: left; border: none;border-right: 1px solid #000;padding-left: 5px;">' . ($i + 1) . '</td>
                                <td style="text-align: left; border-right: none; border-top: none; border-bottom: none; padding-left: 5px;border-right: 1px solid #000;">' . $product['name'] . '</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none;padding-right: 5px;border-right: 1px solid #000;">' . $product['unit'] . '</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none; padding-right: 5px;border-right: 1px solid #000;">' . number_format($product['unitprice'], 2) . '</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none; padding-right: 5px;border-right: 1px solid #000;">7%</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none; padding-right: 5px;">' . number_format($sum, 2) . '</td>
                            </tr>';
                $TotalAmounts += $sum;
            }
            $TotalAmount = $TotalAmounts;


            $emptyRowsNeeded = $itemsPerPage - min($itemsPerPage, $totalItems - $page * $itemsPerPage);
            for ($j = 0; $j < $emptyRowsNeeded; $j++) {
                $html .= '
                            <tr id="body">
                                <td style="text-align: center; border: none;border-right: 1px solid #000;">&nbsp;</td>
                                <td style="text-align: left; border-right: none; border-top: none; border-bottom: none; padding-left: 10px;border-right: 1px solid #000;">&nbsp;</td>
                                <td style="text-align: center; border-right: none; border-top: none; border-bottom: none;border-right: 1px solid #000;">&nbsp;</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none;border-right: 1px solid #000;">&nbsp;</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none;border-right: 1px solid #000;">&nbsp;</td>
                                <td style="text-align: right; border-top: none; border-bottom: none;">&nbsp;</td>
                            </tr>';
            }

            $tax_Amount = $TotalAmount * 0.07;
            $Amount = $TotalAmount;
            $TotalAmounts = $Amount + $tax_Amount;
            $totalAmounttext = $this->convertToThaiText(number_format($TotalAmounts, 2));
            $html .= '
        </tbody>
        </table>
        <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width:45%;border-top: 1px solid black;height: 30px;"><strong>หมายเหตุ</strong> : Remarks</td>
            <td colspan="2" style="text-align: right;border-right: 1px solid black;border-bottom: 1px solid black; border-top: 1px solid black;height: 30px;"><strong>ราคาสุทธิสินค้าที่เสียภาษี (บาท)</strong> / Pre-VAT Amount</td>
            <td style="width:15%;text-align: right; border-bottom: 1px solid black;border-top: 1px solid black;height: 30px;">' . number_format($Amount, 2) . '</td>
        </tr>
        <tr>
            <td>ใบเสร็จรับเงินนี้จะไม่สมบูรณ์หากยังไม่ได้รับชำระเงิน</td>
            <td colspan="2" style="height: 30px;text-align: right;border-right: 1px solid black;border-bottom: 1px solid black; "><strong>ภาษีมูลค่าเพิ่ม (บาท)</strong> / VAT</td>
            <td style="text-align: right; border-bottom: 1px solid black;height: 30px;">' . number_format($tax_Amount, 2) . '</td>
        </tr>
        <tr>
            <td rowspan="2" style="border-bottom: 1px solid black;"></td>
            <td colspan="2" style="height: 30px;text-align: right; border-right: 1px solid black;border-bottom: 1px solid black;"><strong>จำนวนเงินทั้งสิ้น (บาท)</strong> / Grand Total</td>
            <td style="height: 30px;text-align: right; border-bottom: 1px solid black;">' . number_format($TotalAmounts, 2) . '</td>
        </tr>
        <tr>
            <td style="height: 30px;font-size: 14;text-align: left; border-bottom: 1px solid black;font-weight: bold; background-color: #d3d3d3;width:15%;padding-left: 5px;">จำนวนเงินรวมทั้งสิ้น</td>
            <td colspan="2" style="height: 30px;font-size: 14;text-align: right;border-bottom: 1px solid black;font-weight: bold; background-color: #d3d3d3;width:50%;padding-right: 5px;">' . $totalAmounttext . '</td>
        </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse;margin-top:10px;">
            <tr>
                <td style="width:50%;"><strong>การชำระเงิน</strong> / Payment</td>
                <td style="width:25%;"><strong>อนุมัติโดย</strong> / Approved by</td>
                <td style="width:25%;"><strong>รับชำระ</strong> / Received by</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: left;"><span style="font-family: Arial, sans-serif; font-size: 16px; display: inline-block;">' . $check . '</span> เงินสด : ชำระวันที่ 01/10/2019 ยอด ' . number_format($TotalAmounts, 2) . '</td>
            </tr>
            <tr>
                <td colspan="3" >ข้อมูลเพิ่มเติม......................................................................</td>
            </tr>
            <tr>
                <td colspan="3" ><span style="font-family: Arial, sans-serif; font-size: 16px; display: inline-block;">' . $check . '</span> หัก ณ ที่จ่าย</td>
            </tr>
            <tr>
                <td style="width:50%;">ข้อมูลเพิ่มเติม......................................................................</td>
                <td style="width:25%;">
                    <p>.........................................................</p>
                    <p>วันที่ / Date ...................................</p>
                </td>
                <td style="width:25%;">
                    <p>.........................................................</p>
                    <p>วันที่ / Date ...................................</p>
                </td>
            </tr>
        </table>';
            $mpdf->WriteHTML($html);
        }
        // $mpdf->Output('receipt.pdf', \Mpdf\Output\Destination::INLINE);
        $pdfContent = $mpdf->Output('', 'S');

        $contentLength = strlen($pdfContent);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=mpdf.pdf',
            'Access-Control-Expose-Headers' => 'Accept-Ranges',
            'Access-Control-Allow-Headers' => 'Accept-Ranges,range',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $contentLength,
        ];

        return Response::make($pdfContent, 200, $headers);
    }

    public function abbreceipt()
    {

        $items = [];

        for ($i = 1; $i <= 15; $i++) { // สร้าง 10 รายการ
            $items[] = [
                'id' => $i,
                'name' => 'Product ' . $i,
                'unit' => rand(1, 5),  // จำนวนสุ่ม 1 - 100
                'unitprice' => rand(50, 100), // ราคาต่อหน่วยสุ่ม 50 - 500
            ];
        }
        $branchid = '105';
        $branchcode = '05601';
        $taxid = '01075420000011';
        $vatcode = '08274';
        $poscode = 'E02017000201002';

        $paidprice = 5000;

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [58, 297],
            'default_font_size' => 10,
            'fontDir' => array_merge($fontDirs, [
                base_path() . '/custom/font/directory',
            ]),
            'fontdata' => $fontData + [
                'th-sarabun' => [
                    'R' => 'THSarabun.ttf',
                    'I' => 'THSarabun Italic.ttf',
                    'B' => 'THSarabun Bold.ttf',
                    'BI' => 'THSarabun BoldItalic.ttf',
                ],
            ],
            'default_font' => 'th-sarabun',
            'margin_left' => 3,
            'margin_right' => 3,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_header' => 5,
            'margin_footer' => 5,
            'orientation' => 'P',
        ]);
        $TotalAmounts = 0;

        $mpdf->SetTitle('ใบเสร็จแบบย่อ');
        $html = '
        <div style="text-align: center;line-height: 0.3;">
            <p style="font-weight: bold;font-size: 14;">TEG Cargo (สาขา ' . $branchid . ') {' . $branchcode . '}</p>
            <p style="font-weight: bold;font-size: 14;">TAX#' . $taxid . ' (VAT Included)</p>
            <p style="font-weight: bold;font-size: 14;">Vat code ' . $vatcode . ' POS#' . $poscode . '</p>
            <p>ใบเสร็จรับเงิน/ใบกำหับภาษีอย่างย่อ</p>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            ';
        foreach ($items as $product) {
            $sum = (floatval(str_replace(',', '', $product['unitprice'])) * intval($product['unit']));
            $html .= '
                            <tr>
                                <td style="width:10%;text-align: left; border: none;padding-left: 5px;">' . $product['unit'] . '</td>
                                <td style="text-align: left; border-right: none; border-top: none; border-bottom: none; padding-left: 5px;">' . $product['name'] . '</td>
                                <td style="text-align: right; border-right: none; border-top: none; border-bottom: none; padding-right: 5px;">' . number_format($sum, 2) . '</td>
                            </tr>';
            $TotalAmounts += $sum;
        }
        $TotalAmount = $TotalAmounts;
        $totalUnits = array_sum(array_column($items, 'unit'));
        $countprice = $paidprice - $TotalAmount;
        $html .= '
                <tr>
                    <td colspan="2" style="text-align: left;border-top: 0.5px dashed black;">ยอดสุทธิ ' . $totalUnits . ' ชิ้น</td>
                    <td style="text-align: right;border-top: 0.5px dashed black;">' . number_format($TotalAmount, 2, '.', ',') . '</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: left;">เงินรับ</td>
                    <td style="text-align: right;">' . number_format($paidprice, 2) . '</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: left;">เงินสด/เงินทอน</td>
                    <td style="text-align: right;">' . number_format($countprice, 2) . '</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: left;border-top: 0.5px dashed black;border-bottom: 0.5px dashed black;">R#0001055307 P3:7233852</td>
                    <td style="text-align: right;border-top: 0.5px dashed black;border-bottom: 0.5px dashed black;">22/12/60  17:04</td>
                </tr>
        </table>
        <div style="text-align: center; margin-top: 10px;">
            <img src="' . (storage_path('app/public/360_F_455480661_B1ndlageM3kplzg1NRPFUgYj2iWXvDQS.jpg')) . '" alt="logo" style="width:150px;height:auto;">
        </div>';

        $mpdf->WriteHTML($html);

        // $mpdf->Output('receipt.pdf', \Mpdf\Output\Destination::INLINE);
        $pdfContent = $mpdf->Output('', 'S');

        $contentLength = strlen($pdfContent);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=mpdf.pdf',
            'Access-Control-Expose-Headers' => 'Accept-Ranges',
            'Access-Control-Allow-Headers' => 'Accept-Ranges,range',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $contentLength,
        ];

        return Response::make($pdfContent, 200, $headers);
    }

    public function convertToThaiText($number)
    {
        $txtnum1 = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ'];
        $txtnum2 = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $number = str_replace([",", " ", "บาท"], "", $number);
        $number = explode(".", $number);
        if (sizeof($number) > 2) {
            return 'ข้อมูลไม่ถูกต้อง';
        }
        $strlen = strlen($number[0]);
        $convert = '';
        for ($i = 0; $i < $strlen; $i++) {
            $n = substr($number[0], $i, 1);
            if ($n != 0) {
                if ($i == ($strlen - 1) && $n == 1) {
                    $convert .= 'เอ็ด';
                } elseif ($i == ($strlen - 2) && $n == 2) {
                    $convert .= 'ยี่';
                } elseif ($i == ($strlen - 2) && $n == 1) {
                    $convert .= '';
                } else {
                    $convert .= $txtnum1[$n];
                }
                $convert .= $txtnum2[$strlen - $i - 1];
            }
        }

        $convert .= 'บาท';
        if (empty($number[1]) || $number[1] == '00') {
            $convert .= 'ถ้วน';
        } else {
            $strlen = strlen($number[1]);
            for ($i = 0; $i < $strlen; $i++) {
                $n = substr($number[1], $i, 1);
                if ($n != 0) {
                    if ($i == ($strlen - 1) && $n == 1) {
                        $convert .= 'เอ็ด';
                    } elseif ($i == ($strlen - 2) && $n == 2) {
                        $convert .= 'ยี่';
                    } elseif ($i == ($strlen - 2) && $n == 1) {
                        $convert .= '';
                    } else {
                        $convert .= $txtnum1[$n];
                    }
                    $convert .= $txtnum2[$strlen - $i - 1];
                }
            }
            $convert .= 'สตางค์';
        }
        return $convert;
    }
}
