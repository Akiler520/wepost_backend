<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/24
 * Time: 10:11 PM
 */

namespace App\Lib;


define("RESPONSE_SUCCESS",  200);
define("RESPONSE_ERROR",    500);
define("RESPONSE_NO_FOUND", 404);
define("RESPONSE_NO_LOGIN", 401);

class MTResponse
{
    public static function jsonResponse($message, $status, $data = []){
        $returnData = [
            "message"   => $message,
            "status"    => $status,
            "data"      => $data
        ];

        echo json_encode($returnData);

        exit;
    }
}