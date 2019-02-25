<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\api\service;


use app\api\model\StoreConfigModel;
use app\api\model\JPushResultModel;
use JPush\Client;
use think\Db;
class ClientService
{
    private $app_key;            //待发送的应用程序(appKey)，只能填一个。
    private $master_secret;        //主密码
    private $url = "https://api.jpush.cn/v3/push";      //推送的地址
    private $client;

    public function __construct(){
        header("Content-type: text/html; charset=utf-8");
        $client = config('client');
        $this->app_key = $client['appKey'];
        $this->master_secret = $client['masterSecret'];
        $this->client = new Client($this->app_key,$this->master_secret);
    }
    //极光推送的类
    //文档见：http://docs.jpush.cn/display/dev/Push-API-v3
    public function storePush($data){
        $storeConfig = new StoreConfigModel();
        $receive_message_push = $storeConfig->where(['store_id'=>$data['store_id'],'code'=>'receive_message_push'])->find();
        $night_message_push = $storeConfig->where(['store_id'=>$data['store_id'],'code'=>'night_message_push'])->find();
        if ($night_message_push->value == 1){
            $newData = date('H:i:s');
            $date = explode(':',$newData);
            if ($date[0] >= '22' || $date[0] <= '07'){
                return ;
            }
        }
        if ($receive_message_push['value'] == 1) {
            $this->push($data['content'],$data['receiver']);
        }
    }

    /**
     * 极光推送
     *
     * @param $tagIds array('','','')
     * @param $data array('title'=>'','content'=>'')
     * @Author: guanyl
     * @Date: 2018/8/10
     */
    public function clientPush($tagIds,$data){
        $push = $this->client->push();
        $push->setPlatform('all');

        $push->addAllAudience();
        //ios端推送设置
        $ios_notification = array('sound' => 'sound','badge' => '+1','extras' => array('key' => 'value'));
        $push->iosNotification($data['title'], $ios_notification);
        //android端推送设置
        $android_notification = array('title' => $data['title'],'builder_id' => 0,'style' => 0,'alert_type' => -1,'extras' => array('key' => 'value'));
        $push->androidNotification($data['title'], $android_notification);

        $message = array('title' => 'Hello','content_type' => 'text','extras' => array('key' => 'value'));
        $push->message($data['content'], $message);
        $push->send();

    }

    public function clientReport(){
        $report = $this->client->report();
        $received = $report->getReceived('63050395203778681');
        return $received;
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
     * @param string $title   推送的标题
     * @param string $m_type  推送附加字段的类型(可不填) http,tips,chat....
     * @param string $m_txt   推送附加字段的类型对应的内容(可不填) 可能是url,可能是一段文字。
     * @param string $m_time  保存离线时间的秒数默认为一天(可不传)单位为秒
     * @return bool
     *
     * @Author: guanyl
     * @Date: 2018/8/9
     *
     */
    public function push($content='',$receiver='all',$title='新零售',$m_txt='',$m_type='',$m_time='86400'){
        $base64 = base64_encode("$this->app_key:$this->master_secret");
        $header=array("Authorization:Basic $base64","Content-Type:application/json");

        $data = array();
        $data['platform'] = 'all';          //推送平台设置 ,目标用户终端手机的平台类型android,ios,winphone
        $data['audience'] = $receiver;      //推送设备指定 ,目标用户
        $data['notification'] = array(//通知内容体。是被推送到客户端的内容。与 message 一起二者必须有其一，可以二者并存
            //统一的模式--标准模式
            "alert"=>$content,
            //安卓自定义
            "android"=>array(
                "alert"=>$content,
                "title"=>$title,
                "extras"=>array("type"=>$m_type, "txt"=>$m_txt)//这里自定义 JSON 格式的 Key/Value 信息，以供业务使用
            ),
            //ios的自定义
            "ios"=>array(
                "alert"=>$content,
                "badge"=>"1",//应用角标
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
            "title"=> $title,//消息标题
            "extras"=>array("type"=>$m_type, "txt"=>$m_txt)
        );

        //附加选项 ,推送参数
        $data['options'] = array(
            "sendno"=>time(),//用来作为 API 调用标识,段取值范围为非 0 的 int.
            "time_to_live"=>intval($m_time), //保存离线时间的秒数默认为一天,最长 10 天。 0 不保留离线消息，只推送当前在线的用户
            //True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            "apns_production"=>1      //指定 APNS 通知发送环境：0开发环境，1生产环境。
        );
        $param = json_encode($data);
        $res = $this->push_curl($param,$header);

        if($res){       //得到返回值--成功已否后面判断
            $jPushResultModel = new JPushResultModel();
            $result = json_decode($res);
            if (!empty($result->error)) {
                $dataResult = array(
                    'sendno'=>$result->sendno,
                    'msg_id'=>$result->msg_id,
                    'error'=>1,
                    'code'=>$result->error->code,
                    'message'=>$result->error->message,
                );
                $jPushResultModel->create($dataResult);
                return false;
            }else{
                $dataResult = array(
                    'sendno'=>$result->sendno,
                    'msg_id'=>$result->msg_id
                );
                $jPushResultModel->create($dataResult);
                return $result;
            }
        }else{          //未得到返回值--返回失败
            return false;
        }
    }

    //推送的Curl方法
    public function push_curl($param="",$header="") {
        if (empty($param)) { return false; }
        $postUrl = $this->url;
        $curlPost = $param;
        $ch = curl_init();                                      //初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);                 //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);                    //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);                      //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);           // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);                                 //运行curl
        curl_close($ch);
        return $data;
    }
}