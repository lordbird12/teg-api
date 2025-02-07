<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\member;
use App\Models\Device;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

class LoginController extends Controller
{
    public $key = "key";

    public function genToken($id, $name)
    {
        $payload = array(
            "iss" => "key",
            "aud" => $id,
            "lun" => $name,
            "iat" => Carbon::now()->timestamp,
            // "exp" => Carbon::now()->timestamp + 86400,
            "exp" => Carbon::now()->timestamp + 31556926,
            "nbf" => Carbon::now()->timestamp,
        );

        $token = JWT::encode($payload, $this->key);
        return $token;
    }

    public function checkLogin(Request $request)
    {
        $header = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $header);

        try {

            if ($token == "") {
                return $this->returnError('Token Not Found', 401);
            }

            $payload = JWT::decode($token, $this->key, array('HS256'));
            $payload->exp = Carbon::now()->timestamp + 86400;
            $token = JWT::encode($payload, $this->key);

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'Active',
                'data' => [],
                'token' => $token,
            ], 200);
        } catch (\Firebase\JWT\ExpiredException $e) {

            list($header, $payload, $signature) = explode(".", $token);
            $payload = json_decode(base64_decode($payload));
            $payload->exp = Carbon::now()->timestamp + 86400;
            $token = JWT::encode($payload, $this->key);

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'Token is expire',
                'data' => [],
                'token' => $token,
            ], 200);

        } catch (Exception $e) {
            return $this->returnError('Can not verify identity', 401);
        }
    }

    public function login(Request $request)
    {
        if (!isset($request->username)) {
            return $this->returnErrorData('[username] ไม่มีข้อมูล', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('[password] ไม่มีข้อมูล', 404);
        }

        $user = User::where('username', $request->username)
            ->where('password', md5($request->password))
            ->first();

        if ($user) {

            //log
            $username = $user->username;
            $log_type = 'เข้าสู่ระบบ';
            $log_description = 'ผู้ใช้งาน ' . $username . ' ได้ทำการ ' . $log_type;
            $this->Log($username, $log_description, $log_type);
            //

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'data' => $user,
                'token' => $this->genToken($user->id, $user),
            ], 200);
        } else {
            return $this->returnError('รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง', 401);
        }

    }

    public function loginMember(Request $request)
    {
        if (!isset($request->importer_code)) {
            return $this->returnErrorData('[importer_code] ไม่มีข้อมูล', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('[password] ไม่มีข้อมูล', 404);
        }

        $user = member::where('importer_code', $request->importer_code)
            ->where('password', md5($request->password))
            ->first();

        if ($user) {
            if($user->image){
                $user->image = url($user->image);
            }

            $deviceNo = $request->device_no;
            $notifyToken = $request->notify_token;

            if ($deviceNo && $notifyToken) {
                //check device
                $deviceIsExist =  Device::where('device_no', $deviceNo)
                    // ->where('notify_token', $notifyToken)
                    ->where('user_id',  $user->id)
                    ->first();

                if (!$deviceIsExist) {
                    //add
                    $device = new Device();
                    $device->user_id =  $user->id;
                    $device->device_no =  $deviceNo;
                    $device->notify_token =  $notifyToken;
                    $device->status =  true;
                    $device->save();
                } else {
                  
                    //update
                    $deviceIsExist->user_id =  $user->id;
                    $deviceIsExist->device_no =  $deviceNo;
                    $deviceIsExist->notify_token =  $notifyToken;
                    $deviceIsExist->status =  true;
                    $deviceIsExist->save();
                }
                //

            }

            if($user){
                $title = 'แจ้ง Member';
                $body = "มีการเข้าสู่ระบบ";
                $target_id = $user->id;
                $type = 'member';
                $this->sendNotifyAll($title, $body, $target_id, $type);
            }

            //log
            $username = $user->importer_code;
            $log_type = 'เข้าสู่ระบบ';
            $log_description = 'ผู้ใช้งาน ' . $username . ' ได้ทำการ ' . $log_type;
            $this->Log($username, $log_description, $log_type);
            //

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'data' => $user,
                'token' => $this->genToken($user->id, $user),
            ], 200);
        } else {
            return $this->returnError('รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง', 401);
        }
    }

    public function refreshToken(Request $request)
    {
        if (!isset($request->member_id)) {
            return $this->returnErrorData('[member_id] ไม่มีข้อมูล', 404);
        }

        $user = member::find($request->member_id);

        if ($user) {
            if($user->image){
                $user->image = url($user->image);
            }

            $deviceNo = $request->device_no;
            $notifyToken = $request->notify_token;

            if ($deviceNo && $notifyToken) {
                //check device
                $deviceIsExist =  Device::where('device_no', $deviceNo)
                    // ->where('notify_token', $notifyToken)
                    ->where('user_id',  $user->id)
                    ->first();

                if (!$deviceIsExist) {
                    //add
                    $device = new Device();
                    $device->user_id =  $user->id;
                    $device->device_no =  $deviceNo;
                    $device->notify_token =  $notifyToken;
                    $device->status =  true;
                    $device->save();
                } else {
                  
                    //update
                    $deviceIsExist->user_id =  $user->id;
                    $deviceIsExist->device_no =  $deviceNo;
                    $deviceIsExist->notify_token =  $notifyToken;
                    $deviceIsExist->status =  true;
                    $deviceIsExist->save();
                }
                //

            }

            if($user){
                $title = 'แจ้ง Member';
                $body = "มีการเข้าสู่ระบบ";
                $target_id = $user->id;
                $type = 'member';
                $this->sendNotifyAll($title, $body, $target_id, $type);
            }

            //log
            $username = $user->email;
            $log_type = 'เข้าสู่ระบบ';
            $log_description = 'ผู้ใช้งาน ' . $username . ' ได้ทำการ ' . $log_type;
            $this->Log($username, $log_description, $log_type);
            //

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'data' => $user,
                'token' => $this->genToken($user->id, $user),
            ], 200);
        } else {
            return $this->returnError('รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง', 401);
        }
    }

}
