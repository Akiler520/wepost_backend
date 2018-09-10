<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/3
 * Time: 10:36 AM
 */

namespace App\Models;


class App extends Base
{
    protected $table = "post_app";
    protected $primaryKey = "app_id";


    /**
     * create new
     *
     * @param $data
     * @return bool
     */
    public static function createOne($data){
        $insert = new self();

        foreach ($data as $key => $value) {
            $insert->$key = $value;
        }

        return $insert->save();
    }

    /**
     * 根据用户appid获取信息
     * @param $appID
     *
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public static function getByAppID($appID){
        $info = self::query()->where("appid", "=", $appID)->first();

        return $info;
    }

    public static function isExist($appID){
        $count = self::query()->where("appid", "=", $appID)->count();

        return $count;
    }

    public static function updateByAppID($appID, $clientInfo){
        $appInfo = self::getByAppID($appID);

        foreach ($clientInfo as $key=>$client) {
            $appInfo->$key = $client;
        }
        $ret = $appInfo->save();

        return $ret;
    }

}