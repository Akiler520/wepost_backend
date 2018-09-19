<?php
namespace App\Business;

use App\Lib\MtHttpClient;
use App\Lib\MTResponse;
use App\Models\App;
use App\Models\Log;
use App\Models\Sms;
use App\Models\User;
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

        Log::saveLog("/MessageBusiness/notice", json_encode($ret));

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

    /**
     *
     * @param $phone
     * @param $type
     * @param $templateParam = ["code" => 2983, "product" => "大牛一键分享"]
     *
     * @return bool
     * @throws \Exception
     */
    public static function sms($phone, $type, $templateParam)
    {
        !in_array($type, [1, 2]) && MTResponse::jsonResponse("当前不支持您选择的短信类型！", RESPONSE_ERROR);

        // 检测1分钟内不能重复发送短信给同一个电话号码
        $hasSend = Sms::getByPhone($phone, $type, 60);

        $hasSend && MTResponse::jsonResponse("请勿在1分钟内重复发短信，谢谢支持！", RESPONSE_ERROR);


        //账号：daniu
        //密码：123q678
        //UID:2200
        //http://121.199.50.122:8888

        //参数
        //        userid	企业id	企业ID
        //account	发送用户帐号	用户帐号，由系统管理员
        //password	发送帐号密码	用户账号对应的密码
        //mobile	全部被叫号码	发信发送的目的号码.多个号码之间用半角逗号隔开
        //content	发送内容	短信的内容，内容需要UTF-8编码
        //sendTime	定时发送时间	为空表示立即发送，定时发送格式2010-10-24 09:08:10
        //action	发送任务命令	设置为固定的:send
        //extno	扩展子号	请先询问配置的通道是否支持扩展子号，如果不支持，请填空。子号只能为数字，且最多5位数。

        $signName = "【大牛分享】";
        $host = "http://121.199.50.122:8888/sms.aspx";
        $smsData = [
            "userid"    => 2200,
            "account"   => "daniu",
            "password"  => "123q678",
            "mobile"    => $phone,
            "content"   => "",
            "sendTime"  => "",
            "action"    => "send",
            "extno"     => ""
        ];

        switch ($type) {
            case 1: // 注册

                //检测当前电话是否已经被注册了，如果已经注册了，则不发短信；
                $info = User::getByUsername($phone);
                $info && MTResponse::jsonResponse("亲，您已经注册过了，请使用密码直接登录！", RESPONSE_ERROR);

                $smsData['content'] = $signName . "验证码：{$templateParam['code']}，您正在注册成为{$templateParam['product']}的用户，谢谢您的支持！";
                break;
            case 2:// 找回密码

                // 如果该电话没有注册，则不发短信；
                $info = User::getByUsername($phone);
                !$info && MTResponse::jsonResponse("亲，您还没有注册，请先注册吧！", RESPONSE_ERROR);

                $smsData['content'] = $signName . "验证码：{$templateParam['code']}，您正在找回{$templateParam['product']}的用户密码，请在5分钟内使用，切勿泄露给他人！";
                break;

            default:
                break;
        }

        $ret = MtHttpClient::getInstance()->httpBuilder([
                    'path'   => $host,
                    'method' => 'POST',
                    'body' => [],
                    'query'   => $smsData,
                    'responseType'  => "xml"
                ]);

        if (stripos($ret, "Success")) {
            // 保存或者更新短信到数据库
            $smsInfo = [
                'phone'     => $phone,
                'code'      => $templateParam['code'],
                'expire_time'   => time() + 60 * 5,
                'type'      => $type
            ];

            $ret = Sms::createOrUpdate($smsInfo);

            return true;
        }

        return false;
    }

}