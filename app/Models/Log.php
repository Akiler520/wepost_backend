<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/3
 * Time: 10:36 AM
 */

namespace App\Models;

class Log extends Base
{
    protected $table = "post_log";

    /**
     * 保存日志
     *
     * @param $type
     * @param $content
     *
     * @return bool
     */
    public static function saveLog($type, $content){
        $userID = isset($_SERVER['userInfo']) ? $_SERVER['userInfo']->id : 0;

        $data = [
            "user_id"   => $userID,
            "type"      => $type,
            "content"   => $content
        ];

        $element = new self();

        foreach ($data as $key => $value) {
            $element->$key = $value;
        }

        return $element->save();
    }

}