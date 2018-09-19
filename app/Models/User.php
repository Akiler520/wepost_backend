<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/3
 * Time: 10:36 AM
 */

namespace App\Models;


class User extends Base
{
    protected $table = "post_user";


    /**
     * get list of env
     * @param $params
     * @return mixed
     */
    public function getList($params = []){
        $page       = $params['page'] ?? 1;
        $pageSize   = $params['page_size'] ?? 10;

        $query = self::select();
//        isset($params['project_id']) && $query = $query->where("project_id", $params['project_id']);

        $query->where("is_super", "<>", 1);

        $list = $query->orderBy("id", "DESC")->paginate($pageSize, null, null, $page);

        return $list;
    }

    public function getListByID($userIDs = []){
        $list = self::whereIn("id", $userIDs)->get();

        return $list;
    }

    /**
     * create new env
     *
     * @param $data
     * @return bool
     */
    public function createOne($data){
        if (!isset($data["username"]) || empty($data['username'])) {
            return false;
        }

        $element = self::where("username", $data['username'])->first();
        if ($element) {
            throw new \Exception("用户已经存在，请换其他名称");
        }

        $insert = new self();

        foreach ($data as $key => $value) {
            $insert->$key = $value;
        }

        return $insert->save();
    }

    /**
     * delete one record
     *
     * @param $envID
     * @return mixed
     */
    public function deleteOne($envID){
        $element = self::find($envID);

        if (!$element) {
            throw new \Exception("用户不存在，请检测!");
        }

        return $element->delete();
    }

    /**
     * 更新
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateOne($id, $data){
        $element = self::find($id);

        if (!$element) {
            throw new \Exception("用户不存在，请检测!");
        }

        foreach ($data as $key => $value) {
            if (!is_numeric($value) && $value == null) {
                continue;
            }

            $element->$key = $value;
        }

        return $element->save();
    }

    public function login($username, $password){
        if (!$username || !$password) {
            return false;
        }

        $element = self::where("username", $username)->first();

        if (empty($element)) {
            throw new \Exception("用户不存在，请检测!");
        }

        if ($element->password != md5($password)) {
            return false;
        }

        // save token and return it
        $tokenString    = md5($element->username . $element->id . uniqid() . time());
        $expireTime     = time() + 3600 * 24 * 30; //30天

        $token = [
            "token"         => $tokenString,
            "token_expire"  => $expireTime
        ];

        $this->updateOne($element->id, $token);

        $result = [
            "token"     => $tokenString,
            "is_super"  => $element->is_super,
            "user_id"   => $element->id,
            "username"  => $element->username,
            "nickname"  => $element->nickname,
            "header"    => env("APP_URL") . $element->header_img,
        ];

        $_SERVER['userInfo'] = $element;

        Log::saveLog("/user/login", "用户登录: " . $element->username);

        return $result;
    }

    public function loginCheck($token) {
        $userInfo = self::where("token", $token)->where("token_expire", ">", time())->first();

        return $userInfo;
    }

    public static function getByUsername($name)
    {
        $userInfo = self::query()->where("username", $name)->first();

        return $userInfo;
    }
}