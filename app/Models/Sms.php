<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/3
 * Time: 10:36 AM
 */

namespace App\Models;

class Sms extends Base
{
    protected $table = "post_sms";
    protected $primaryKey = "message_id";

    /**
     * 保存
     *
     * @param $data
     *
     * @return bool
     */
    public static function createOrUpdate($data){
        if (!isset($data['phone']) || !isset($data['code']) || !isset($data['expire_time']) || !isset($data['type'])) {
            return false;
        }

        $info = self::query()
            ->where("phone", $data['phone'])
            ->where("type", $data['type'])
            ->first();

        if ($info) {
            $element = $info;
        } else {
            $element = new self();
        }

        foreach ($data as $key => $value) {
            $element->$key = $value;
        }

        return $element->save();
    }

    /**
     * 根据电话和短信类型获取信息
     *
     * @param $phone
     * @param $type
     *
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public static function getValid($phone, $type)
    {
        $info = self::query()
            ->where("phone", $phone)
            ->where("type", $type)
            ->where("expire_time", ">", now())
            ->first();

        return $info;
    }

    public static function getValidInTime($phone, $type, $time)
    {
        $info = self::query()
            ->where("phone", $phone)
            ->where("type", $type)
            ->where("updated_at", ">", $time)
            ->first();

        return $info;
    }
}