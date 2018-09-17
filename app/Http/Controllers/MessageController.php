<?php

namespace App\Http\Controllers;

use App\Business\MessageBusiness;
use App\Lib\MTResponse;

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


    public function send(){
        $ret = MessageBusiness::notice("大牛消息测试");

        MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $ret);
    }

}
