<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\admin\service;


use JPush\Client;
use think\Db;
class ClientService
{
    private $app_key;            //待发送的应用程序(appKey)，只能填一个。
    private $master_secret;        //主密码
    private $url = "https://api.jpush.cn/v3/push";      //推送的地址

    public function __construct(){
        $client = config('client');
        $this->app_key = $client['appKey'];
        $this->master_secret = $client['masterSecret'];
    }

    public function clientPush(){
        $client = new Client($this->app_key,$this->master_secret);
    }



    /**
     * @param string $receiver 接收者的信息
     *
     * all 字符串 该产品下面的所有用户. 对app_key下的所有用户推送消息
     * tag(20个)Array标签组(并集): tag=>array('昆明','北京','曲靖','上海');
     * tag_and(20个)Array标签组(交集): tag_and=>array('广州','女');
     * alias(1000)Array别名(并集): alias=>array('93d78b73611d886a74*****88497f501','606d05090896228f66ae10d1*****310');
     * registration_id(1000)注册ID设备标识(并集): registration_id=>array('20effc071de0b45c1a**********2824746e1ff2001bd80308a467d800bed39e');
     *
     * @param string $content 推送的内容
     * @param string $m_type  推送附加字段的类型(可不填) http,tips,chat....
     * @param string $m_txt   推送附加字段的类型对应的内容(可不填) 可能是url,可能是一段文字。
     * @param string $m_time  保存离线时间的秒数默认为一天(可不传)单位为秒
     * @return bool
     *
     * @Author: guanyl
     * @Date: 2018/8/9
     *
     */
    /*public function push($receiver='all',$content='',$m_type='',$m_txt='',$m_time='86400'){
        $base64 = base64_encode("$this->app_key:$this->master_secret");
        $header=array("Authorization:Basic $base64","Content-Type:application/json");

        $data = array();
        $data['platform'] = 'all';          //推送平台设置 ,目标用户终端手机的平台类型android,ios,winphone
        $data['audience'] = $receiver;      //推送设备指定 ,目标用户
        //用于防止 api 调用端重试造成服务端的重复推送而定义的一个标识符
        $data['cid'] = "8103a4c628a0b98974ec1949-711261d4-5f17-4d2f-a855-5e5a8909b26e";

        $data['notification'] = array(//通知内容体。是被推送到客户端的内容。与 message 一起二者必须有其一，可以二者并存
            //统一的模式--标准模式
            "alert"=>$content,
            //安卓自定义
            "android"=>array(
                "alert"=>$content,
                "title"=>"",
                "builder_id"=>1,//Android SDK 可设置通知栏样式，这里根据样式 ID 来指定该使用哪套样式
                //默认为0，还有1，2，3可选，用来指定选择哪种通知栏样式，其他值无效。
                //有三种可选分别为bigText=1，Inbox=2，bigPicture=3
                "style"=>1,
                "alert_type"=>1,//可选范围为 -1 ～ 7
                "big_text"=>"",//当 style = 1 时可用，内容会被通知栏以大文本的形式展示出来
                "inbox"=>"(JSONObject)",//当 style = 2 时可用， json 的每个 key 对应的 value 会被当作文本条目逐条展示
                //当 style = 3 时可用，可以是网络图片 url，或本地图片的 path，目前支持.jpg和.png后缀的图片。
                //图片内容会被通知栏以大图片的形式展示出来。
                //如果是 http／https 的url，会自动下载；
                //如果要指定开发者准备的本地图片就填sdcard 的相对路径
                "big_pic_path"=>"picture url",
                "extras"=>array("type"=>$m_type, "txt"=>$m_txt)//这里自定义 JSON 格式的 Key/Value 信息，以供业务使用
            ),
            //ios的自定义
            //content-available(推送的时候携带"content-available":true 说明是 Background Remote Notification，
            //如果不携带此字段则是普通的Remote Notification)
            //mutable-content(推送的时候携带”mutable-content":true 说明是支持iOS10的UNNotificationServiceExtension，
            //如果不携带此字段则是普通的Remote Notification)
            "ios"=>array(
                "alert"=>$content,
                //如果不填，表示不改变角标数字；否则把角标数字改为指定的数字；为 0 表示清除
                "badge"=>"1",//应用角标
                //如果无此字段，则此消息无声音提示；
                //有此字段，如果找到了指定的声音就播放该声音，
                //否则播放默认声音,如果此字段为空字符串
                //iOS 7 为默认声音，iOS 8及以上系统为无声音
                "sound"=>"default",//通知提示声音
                "extras"=>array("type"=>$m_type, "txt"=>$m_txt)
            ),
        );

        //苹果自定义---为了弹出值方便调测
        //应用内消息。或者称作：自定义消息，透传消息,消息内容体。是被推送到客户端的内容。
        //与 notification 一起二者必须有其一，可以二者并存
        $data['message'] = array(
            "msg_content"=>$content,//消息内容本身
            "content_type"=> "text",//消息内容类型
            "title"=> "msg",//消息标题
            "extras"=>array("type"=>$m_type, "txt"=>$m_txt)
        );
        //短信渠道补充送达内容体。短信补充
        $data['sms_message'] = array(
            //短信补充的内容模板 ID。没有填写该字段即表示不使用短信补充功能。
            "temp_id"=>1250,
            //短信模板中的参数。
            "temp_para"=>array("code"=>"123456"),
            //单位为秒，不能超过24小时。设置为0，表示立即发送短信。
            //该参数仅对 android 和 iOS 平台有效，Winphone 平台则会立即发送短信
            "delay_time"=>3600
        );

        //附加选项 ,推送参数
        $data['options'] = array(
            "sendno"=>time(),//用来作为 API 调用标识,段取值范围为非 0 的 int.
            "time_to_live"=>$m_time, //保存离线时间的秒数默认为一天,最长 10 天。 0 不保留离线消息，只推送当前在线的用户
            //True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            "apns_production"=>1,        //指定 APNS 通知发送环境：0开发环境，1生产环境。
            "apns_collapse_id"=>"jiguang_test_201706011100"//collapse id 长度不可超过 64 bytes。
        );
        $param = json_encode($data);
        $res = $this->push_curl($param,$header);

        if($res){       //得到返回值--成功已否后面判断
            return $res;
        }else{          //未得到返回值--返回失败
            return false;
        }
    }*/
}