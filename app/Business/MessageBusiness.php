<?php
namespace App\Business;

use App\Models\App;
use Cncal\Getui\Getui;

class MessageBusiness
{
    public function __construct()
    {
    }

    /**
     * send message to all user
     * @param $message
     *
     * @return bool
     */
    public static function notice($message)
    {
        $tuiObj = new Getui();

        $data = [
            'template_type' => 4,
            'template_data' => [
                'transmission_type' => 2, // 是否立即启动应用：1 立即启动 2 等待客户端自启动，必填
                'transmission_content' => 'just for you to get the message', // 透传内容，不支持转义字符，string(2048), 必填
                'is_ios' => true, // 是否支持 ios （默认不支持），boolean
                'is_content_available' => true, // 推送是否直接带有透传数据（默认否）, boolean
                'badge' => '', // 应用icon上显示的数字，int
                'sound' => '', // 通知铃声文件名，string
                'category' => '', // 在客户端通知栏触发特定的action和button显示，string
                'custom_msg' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ], // 增加自定义的数据
                'title' => 'just for message', // 通知标题，string
                'text' => "message information", // 通知内容，string
            ],
//            'cid' => 'target cid', // 推送通知至指定用户时填写
//            'cid_list' => ['cid1','cid2'], // 推送通知至指定用户列表时填写
        ];

        $tuiObj->pushMessageToApp($data);

        return true;
    }

    public static function checkClient($clientInfo){
        if (!isset($clientInfo['appid'])) {
            return false;
        }

        $appID = $clientInfo['appid'];

        if (App::isExist($appID)){
            // update
            $ret = App::updateByAppID($appID, $clientInfo);
        } else {
            // create new
            $ret = App::createOne($clientInfo);
        }

        return $ret;
    }
}