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

    public function isURL($url)
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            return true;
        } else {
            return false;
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
            <div style="font-size: 24px; text-align: center;"> <b>เลขประจำตัวผู้เสียภาษีอากร '.$tax_ID.'</div>
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
                    <td style="width:20%; border: none;">'.$name_pay.'</td>
                    <td style="width:10%; border: none;"></td>
                    <td style="width:15%; border: none;"></td>
                    <td style="width:15%; border: none;"></td>
                    <td style="width:10%; text-align: center; border: none;">วิกที่</td>
                    <td style="width:20%; border: none;">'.$week.'</td>
                </tr>
                <tr>
                    <td style="border: none;">ตำแหน่ง</td>
                    <td style="border: none;">'.$position.'</td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="text-align: center; border: none;">วันที่สั่งจ่าย</td>
                    <td style="border: none;">'.$pay_date.'</td>
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
                    <td style="text-align: right;">'.$salary.'</td>
                    <td colspan="2" >หักสาย</td>
                    <td style="text-align: right;">'.$late.' </td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2" >ล่วงเวลา</td>
                    <td style="text-align: right;">'.$ot.'</td>
                    <td colspan="2" >หักลา 1 วัน </td>
                    <td style="text-align: right;">'.$Leave.'</td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">ทำงานในวันหยุด</td>
                    <td style="text-align: right;">'.$workholidays.'</td>
                    <td colspan="2" >หักเบิกล้วงหน้า </td>
                    <td style="text-align: right;">'.$ahead.'</td>
                    <td ></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td style="text-align: right;"></td>
                    <td colspan="2" >หักค่าประกันสังคม </td>
                    <td style="text-align: right;">'.$insurance.'</td>
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
                    <td rowspan="2" style="text-align: right;">'.$in.'</td>
                    <td colspan="2" rowspan="2" style="text-align: right;">รวมรายการหัก</td>
                    <td rowspan="2" style="text-align: right;">'.$out.'</td>
                    <td style="text-align: center;">เงินได้สุทธิ</td>
                </tr>
                <tr>
                    <td style="text-align: center;">'.$income.'</td>
                </tr>
            </table>
            <br>
            <table>
                <tr>
                    <td style="border: none; width:10%;">&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;</td>
                    <td style="border: none; width:45%;">ผู้บันทึก............................................................<br>
                    &nbsp;&nbsp;&nbsp;('.$name_save.') </td>
                    <td style="border: none; width:45%;">ผู้รับเงิน............................................................<br>
                    &nbsp;&nbsp;&nbsp;('.$name_pay.')</td>
                </tr>
                <tr>
                    <td style="border: none; width:10%;"></td>
                    <td style="border: none; width:45%;">ผู้อนุมัติจ่าย.....................................................<br>
                    &nbsp;&nbsp;&nbsp;('.$name_approve.')</td>
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
                ], 'th-sarabun' => [
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
        $totalIn = DB::table('wallet_transactions')->where('type', 'I')->where('member_id',$member_id)->sum('amount');
        $totalOut = DB::table('wallet_transactions')->where('type', 'O')->where('member_id',$member_id)->sum('amount');
        $netBalance = $totalIn - $totalOut;

        return $netBalance;
    }

}
