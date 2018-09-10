<?php
/**
 * Created by PhpStorm.
 * User: yumin
 * Date: 2018/5/3
 * Time: 10:36 AM
 */

namespace App\Models;

use DB;

class Article extends Base
{
    protected $table = "post_article";


    /**
     * get list of env
     * @param $params
     * @return mixed
     */
    public function getList($params = []){
        $page       = $params['page'] ?? 1;
        $pageSize   = $params['page_size'] ?? 10;

        $query      = self::select("*");

        isset($params['user_id']) && $query = $query->where("user_id", $params['user_id']);

        $list       = $query->orderBy("id", "DESC")->paginate($pageSize, null, null, $page);

        // get images of each article
        if ($list) {
            $list = $list->toArray();

            $articleIDs = array_column($list['data'], "id");
            $userIDs = array_unique(array_column($list['data'], "user_id"));

            $imageObj = new ArticleImage();
            $imageList = $imageObj->getList($articleIDs);

            $userObj = new User();
            $userList = $userObj->getListByID($userIDs);

            $loginUserID = 0;
            $isSuper = 0;

            if (isset($_SERVER['userInfo'])) {
                $loginUserID = $_SERVER['userInfo']->id;
                if ($_SERVER['userInfo']->is_super == 1) {
                    $isSuper = 1;
                }
            }
            $loginInfo = $_SERVER['userInfo'] ?? [];
            $loginUserID = empty($loginInfo) ? 0 : $loginInfo->id;

            foreach ($list['data'] as &$article) {
                $article['username'] = "";
                $article['nickname'] = "";
                $article['can_delete'] = 0;
                $article['image'] = [];

                if ($article['user_id'] == $loginUserID || $isSuper) {
                    $article['can_delete'] = 1;
                }

                foreach ($userList as $user) {
                    if ($user['id'] == $article['user_id']) {
                        $article['username'] = $user['username'];
                        $article['nickname'] = $user['nickname'];
                        $headerCusmtom = $_SERVER['DOCUMENT_ROOT']. $user['header_img'];
                        $headerDefault = $_SERVER['DOCUMENT_ROOT']. "/images/header.png";
                        $article['header_img'] = is_file($headerCusmtom) ? env("APP_URL") . $user['header_img'] : ((is_file($headerDefault)) ?  env("APP_URL") . "/images/header.png" : "images/logo.png");
                        break;
                    }
                }

                foreach ($imageList as $image) {
                    if ($image['article_id'] == $article['id']) {
                        $image['url'] = env("APP_URL") . $image['url'];
                        $article['image'][] = $image;
                    }
                }
            }
        }


        return $list;
    }

    /**
     * create new env
     *
     * @param $data
     * @return bool
     */
    public function createOne($data){
        if (!isset($data["content"])) {
            return false;
        }

        $userInfo = $_SERVER["userInfo"];
        $data['user_id'] = $userInfo->id;
        $data['shared_count'] = mt_rand(1, 10);;

        $insert = new self();

        foreach ($data as $key => $value) {
            $insert->$key = $value;
        }

        $save_ret = $insert->save();

        return ($save_ret) ? DB::getPdo()->lastInsertId() : false;
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
            throw new \Exception("文章不存在，请检测!");
        }

        $userInfo = $_SERVER["userInfo"];
        if ($userInfo->is_super != 1 && $userInfo->id != $element->user_id) {
            throw new \Exception("无权限操作!");
        }

        $deleteArticle = $element->delete();

        if ($deleteArticle) {
            // delete images of article
            $ret = ArticleImage::deleteByArticle($envID);
        }

        return $deleteArticle;
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
            throw new \Exception("文章不存在，请检测!");
        }

        foreach ($data as $key => $value) {
            $element->$key = $value;
        }

        return $element->save();
    }

    public function share($ID){
        $element = self::query()->find($ID);
        if (!$element) {
            throw new \Exception("文章不存在，请检测!");
        }

        $amount = mt_rand(1, 10);

        return $element->increment("shared_count", $amount);
    }

    public static function getNewArticle($time){
        $info = self::query()->where("created_at", ">", $time)->orderBy("created_at", "desc")->first();

        return $info;
    }
}