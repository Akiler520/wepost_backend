<?php

namespace App\Http\Controllers;

use App\Business\MessageBusiness;
use App\Lib\MtHttpClient;
use App\Lib\MTResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function sendNotice(){
        $ret = MessageBusiness::notice("大牛消息测试");

        MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $ret);
    }

    public function sendSMS(Request $request)
    {
        $phone = $request->input("phone");
        $type = $request->input("type");    // 1=注册，2=找回密码

        if (empty($phone)) {
            MTResponse::jsonResponse("手机号码不能为空，请检查", RESPONSE_ERROR);
        }

        $templateParam = [
            "code"      => mt_rand(1000, 9999),
            "product"   => "大牛一键分享"
        ];

        $ret = MessageBusiness::sms($phone, $type, $templateParam);

        MTResponse::jsonResponse($ret ? "ok" : "error happened", $ret ? RESPONSE_SUCCESS : RESPONSE_ERROR);
    }


}
