<?php

namespace App\Http\Controllers;

use App\Business\MessageBusiness;
use App\Lib\MTResponse;
use App\Libraries\Ak\AkUploader;
use App\Models\App;
use App\Models\Article;
use App\Models\Sms;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
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

    public function getList(Request $request){
        if ($_SERVER['userInfo']->is_super != 1) {
            MTResponse::jsonResponse("您没有操作权限，请联系管理员", RESPONSE_ERROR);
        }

        $params = $request->all();
        $deployEnvObj = new  User();
        $list = $deployEnvObj->getList($params);

        MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $list);
    }

    public function create(Request $request){
        if ($_SERVER['userInfo']->is_super != 1) {
            MTResponse::jsonResponse("您没有操作权限，请联系管理员", RESPONSE_ERROR);
        }

        $username = $request->input("username");
        $password = $request->input("password");

        $insertData = [
            "username"        => $username,
            "nickname"        => $username,
            "password"        => md5($password),
        ];

        $deployEnvObj = new  User();

        $ret_insert = $deployEnvObj->createOne($insertData);

        if ($ret_insert) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS);
        } else {
            MTResponse::jsonResponse("error", RESPONSE_ERROR);
        }
    }

    public function update(Request $request, $id){
        if ($_SERVER['userInfo']->id != $id && $_SERVER['userInfo']->is_super != 1) {
            MTResponse::jsonResponse("您没有操作权限，请联系管理员", RESPONSE_ERROR);
        }

        $password = $request->input("password", null);
        $nickname = $request->input("nickname", null);

        $insertData = [
            "password"  => $password ? md5($password) : null,
            "nickname"  => $nickname
        ];

        $resultData = [];

        if (!empty($_FILES)) {
            foreach ($_FILES as $fileData) {
                // upload and save images of article
                $uploader = new AkUploader($fileData);

                $rootPath = $_SERVER['DOCUMENT_ROOT'];
                $savePathProject = "/upload/" . date("Ymd") . "/";
                $savePath = $rootPath . $savePathProject;
                $uploader->setSavePath($savePath);

                $uploader->uploadAll();

                $errorInfo = $uploader->getError();

                if ($errorInfo) {
                    MTResponse::jsonResponse("部分图片上传失败，请检查！", RESPONSE_ERROR);
                }

                $imageInfo = $uploader->getResult();
                $insertData['header_img'] = $savePathProject . $imageInfo['save_name'][0];

                $resultData["header"] = env("APP_URL") . $insertData['header_img'];
            }
        }

        $deployEnvObj = new  User();

        $ret_insert = $deployEnvObj->updateOne($id, $insertData);

        if ($ret_insert) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $resultData);
        } else {
            MTResponse::jsonResponse("error", RESPONSE_ERROR);
        }
    }

    public function delete(Request $request, $env_id){
        if ($_SERVER['userInfo']->is_super != 1) {
            MTResponse::jsonResponse("您没有操作权限，请联系管理员", RESPONSE_ERROR);
        }

        $deployEnvObj = new  User();
        $ret_insert = $deployEnvObj->deleteOne($env_id);

        if ($ret_insert) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS);
        } else {
            MTResponse::jsonResponse("error", RESPONSE_ERROR);
        }
    }

    public function login(Request $request){
        $username = $request->input("username");
        $password = $request->input("password");

        $deployEnvObj = new  User();
        $loginToken = $deployEnvObj->login($username, $password);

        if ($loginToken) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $loginToken);
        } else {
            MTResponse::jsonResponse("error", RESPONSE_ERROR);
        }
    }

    public function message(Request $request){
        $appID = $request->input("appid");

        $appInfo = App::getByAppID($appID);

        if (!$appID || !$appInfo) {
            MTResponse::jsonResponse("null", RESPONSE_ERROR);
        }

        $messaged_at = $appInfo->messaged_at;
        if (!$messaged_at) {
            $messaged_at = time() - 3600;
        }

        $newArticle = Article::getNewArticle($messaged_at);

        if ($newArticle) {
            // update messaged_at of app
            App::updateByAppID($appID, ["messaged_at" => strtotime($newArticle->created_at)]);

            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $newArticle);
        }else{
            MTResponse::jsonResponse("null", RESPONSE_NO_FOUND);
        }


    }

    public function getUserInfo(Request $request){
        $userInfo = $_SERVER['userInfo'];

        if ($userInfo) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS, $userInfo);
        }else{
            MTResponse::jsonResponse("null", RESPONSE_ERROR);
        }
    }

    /**
     * 处理客户端信息
     * @param \Illuminate\Http\Request $request
     */
    public function client(Request $request){
        $params = $request->all();

        $param['client_id'] = $params['client_id'];
        $param['client_token'] = $params['client_token'];
        $param['appid'] = $params['appid'];
        $param['appkey'] = $params['appkey'];

        $param['user_id'] = !empty($_SERVER['userInfo']) ? $_SERVER['userInfo']->id : 0;

        $ret = MessageBusiness::checkClient($param);

        if ($ret) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS);
        }else{
            MTResponse::jsonResponse("null", RESPONSE_ERROR);
        }
    }

    public function register(Request $request)
    {
        $phone = $request->input("username");
        $password = $request->input("password");
        $verifyCode = $request->input("code");

        $codeInfo = Sms::getValid($phone, 1);

        if (!$codeInfo || $codeInfo->code != $verifyCode) {
            MTResponse::jsonResponse("验证码错误", RESPONSE_ERROR);
        }

        $newData = [
            'username'  => $phone,
            'nickname'  => $phone,
            'password'  => md5($password)
        ];

        $deployEnvObj = new  User();

        $ret_insert = $deployEnvObj->createOne($newData);

        if ($ret_insert) {
            MTResponse::jsonResponse("注册成功", RESPONSE_SUCCESS);
        } else {
            MTResponse::jsonResponse("注册失败，请重试！", RESPONSE_ERROR);
        }
    }

}
