<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/5
 * Time: 14:31
 */

namespace app\shopapi\controller;

use app\shopapi\model\StorePushMessageModel;
use app\shopapi\service\ClientService;
use think\Request;
class Message extends Common
{
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\GetConfig;

    public function messagePush(StorePushMessageModel $storePushMessageModel)
    {
        $info = ['data'=>'系统推送内容','title'=>'这个是系统推送','create_time'=>$this->getTime()];
        $info = ['user_name'=>'会员名称' ,'head_img'=>'会员头像' , 'clear_price'=>'应得金额','clear_time'=>'2018-02-01 12:00:00','clear_desc'=>'结算说明','order_sn'=>'201850641611231','pay_type'=>'1','admin_name'=>'店铺主姓名','mobile'=>'店铺主'];
        $info = ['user_name'=>'会员名称' ,'head_img'=>'会员头像' , 'clear_price'=>'应得金额','clear_time'=>'2018-02-01 12:00:00','clear_desc'=>'结算说明','order_sn'=>'201850641611231'];
        $info = ['activity_list_name'=>'活动名称' ,'activity_list_desc'=>'活动说明' , 'start_time'=>'2018-02-01 12:00:00','end_time'=>'2018-02-01 12:00:00'];
        $info = ['store_name'=>'店铺名称' ,'voucher_name'=>'优惠券券名', 'state'=>'1','reason'=>'拒绝原因','activity_list_name'=>'活动名称' ,'activity_list_desc'=>'活动说明' , 'start_time'=>'2018-02-01 12:00:00','end_time'=>'2018-02-01 12:00:00',''];
//        $info = ['pay_price'=>'12.5' ,'clear_start_time'=>'2018-02-01 12:00:00' , 'clear_end_time'=>'2018-02-01 12:00:00','pay_end_time'=>'2018-02-01 12:00:00','create_time'=>'2018-02-01 12:00:00'];
        $data = [
            'store_id'=>55,
            'message_type'=>5,
            'message_cont'=>'您的账单已经生成',
            'message_data'=>json_encode($info,JSON_UNESCAPED_UNICODE)
        ];
        $storePushMessageModel->allowField(true)->save($data);
        //极光推送
        $clientService = new ClientService();
        $title   =  '你们好';
        $content =  '祝，天天开心哦！';
        $receiver['alias'] = array('store');//接收者
        $messages = $clientService->push($receiver,$title,$content);
        print_r($messages);die;

    }
    /*
      * explain:店铺消息推送列表
      * addTime:2018/6/7 14:10
      */
    public function messageList(Request $request,StorePushMessageModel $storePushMessageModel) {
        $storeId = $request->param('store_id');
        if(empty($storeId)){
            $this->jkReturn(1,'店铺消息推送列表',['system_message'=>[],'store_message'=>[]]);
        }
        $storePushMessage = $storePushMessageModel
            ->where(['store_id'=>$storeId,'message_type'=>0])
            ->order('create_time','desc')
            ->select();
        $storePushMessage1 = $storePushMessageModel
            ->where("store_id=$storeId and message_type<>0")
            ->order('create_time','desc')
            ->select();
        $this->jkReturn(1,'店铺消息推送列表',['system_message'=>$storePushMessage,'store_message'=>$storePushMessage1]);
    }
    /*
    * explain:店铺消息改为已读
    * params :@id
    *
    * addTime:2018/6/7 15:10
    */
    public function editMessage(Request $request,StorePushMessageModel $storePushMessageModel) {
        $message_id = $request->param('id');
        $storePushMessage = $storePushMessageModel
            ->where(['id'=>$message_id])
            ->find();
        if ($storePushMessage) {
            $upWhere['id'] = $message_id;
            $message_state['message_state'] = 1;
            $result = $storePushMessageModel->update($message_state,$upWhere);
            $message = $storePushMessageModel
                ->where(['store_id'=>$storePushMessage['store_id']])
                ->select();
            if($result){
                $this->jkReturn(1,'修改成功',$message);
            }else{
                $this->jkReturn(-1,'修改失败',$message);
            }
        }else{
            $this->jkReturn(1,'推送消息不存在');
        }
    }
}