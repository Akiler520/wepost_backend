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
    public static function _notice($message)
    {
        $tuiObj = new Getui();

        // 透传
        $data1 = [
            'template_type' => 4,
            'template_data' => [
                'transmission_type' => 1, // 是否立即启动应用：1 立即启动 2 等待客户端自启动，必填
                'transmission_content' => '{"title":"新产品发布","content":"iPhone 新品发布会正式开始了，谢谢大家关注！"}', // 透传内容，不支持转义字符，string(2048), 必填
                'is_ios' => false, // 是否支持 ios （默认不支持），boolean
                'is_content_available' => false, // 推送是否直接带有透传数据（默认否）, boolean
                'badge' => '1', // 应用icon上显示的数字，int
                'sound' => 'default', // 通知铃声文件名，string
                'category' => '', // 在客户端通知栏触发特定的action和button显示，string
                'custom_msg' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ], // 增加自定义的数据
                'title' => '[title]just for message', // 通知标题，string
                'text' => "[text]message information", // 通知内容，string
            ],
//            'cid' => 'target cid', // 推送通知至指定用户时填写
//            'cid_list' => ['cid1','cid2'], // 推送通知至指定用户列表时填写
        ];

        // 通知，ok
        $data2 = [
            'template_type' => 1,
            'template_data' => [
                'title' => '新产品发布', // 通知标题，string(40), 必填
                'text'  => 'iPhone 新品发布会正式开始了，谢谢大家关注!!', // 通知内容，string(600), 必填
                'logo'  => 'not_vip_big_logo.png', // 通知图标名称，string(40), 必填
                'logo_url'  => 'https://dev.getui.com/top_bar_common_img/not_vip_big_logo.png', // 通知图标url地址，string(100), 必填
                'transmission_type'    => 1, // 是否立即启动应用：1 立即启动 2 等待客户端自启动，必填
                'transmission_content' => '{"title":"新产品发布","content":"iPhone 新品发布会正式开始了，谢谢大家关注！"}', // 透传内容，不支持转义字符，string(2048), 必填
            ]
//        'cid' => 'target cid', // 推送通知至指定用户时填写
//        'cid_list' => ['cid1','cid2',...], // 推送通知至指定用户列表时填写
        ];

        $data = [
            'template_type' => 1,
            'template_data' => [
                'title' => 'Laravel Getui',
                'text' => 'May you succeed.',
                'transmission_type' => 1,
                'transmission_content' => 'It is transmission content',
                'is_ring' => false,
                'is_clearable' => false,
                'begin_at' => date('Y-m-d H:i:s'),
                'end_at' => date('Y-m-d H:i:s', strtotime("+1 day")),
            ],
            'cid' => 'target cid',
        ];

        $ret = $tuiObj->pushMessageToApp($data2);

        return $ret;
    }

    public static function notice($message)
    {
        $tuiObj = new Getui();

        $messageData = [
            'title'     => "大牛分享",
            'content'   => $message
        ];

        // 通知，ok
        $data2 = [
            'template_type' => 1,
            'template_data' => [
                'title' => $messageData['title'], // 通知标题，string(40), 必填
                'text'  => $messageData['content'], // 通知内容，string(600), 必填
                'logo'  => '48x48.png', // 通知图标名称，string(40), 必填
                'logo_url'  => 'http://app.da-niu.cn/images/48x48.png', // 通知图标url地址，string(100), 必填
                'transmission_type'    => 1, // 是否立即启动应用：1 立即启动 2 等待客户端自启动，必填
                'transmission_content' => json_encode($messageData), // 透传内容，不支持转义字符，string(2048), 必填
            ]
        ];

        $ret = $tuiObj->pushMessageToApp($data2);

        return $ret;
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