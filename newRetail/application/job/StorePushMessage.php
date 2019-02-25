<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 店铺推送活动队列任务(失败3次以上重新插入对列执行该任务)
    +----------------------------------------------------------
     * @access php think queue:work --daemon --queue StorePushMessage
+----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use think\Db;
use think\queue\Job;
use app\api\service\ClientService;
class StorePushMessage{
    use \app\api\traits\GetConfig;
    public function fire(Job $job, $data){
        if($this->StorePushMessage($data)){
            $job->delete();
        }else{
            //-- 失败超过3次, 重新插入对列
            if ($job->attempts() > 3) {
                $job->release();
            }
        }
    }
    public function failed($data){
        // ...任务达到最大重试次数后，失败了
    }
    public function StorePushMessage($data)
    {
        //数据源
        // $messageInfo = array(
        //     'store_id'=>84,
        //     'message_type'=>3,
        //     'message_cont'=>'活动报名通知',
        //     'message_data'=>array(
        //         'activity_list_id'=>$pushMessage['activity_list_id'],
        //         'activity_list_name'=>$activityList->activity_list_name,
        //         'activity_list_desc'=>$activityList->activity_list_desc,
        //         'start_time'=>$activityList->start_time,
        //         'end_time'=>$activityList->end_time,
        //     ),
        //     'message_state'=>0,
        //     'create_time'=>$this->getTime()
        // );
        $messageInfo = unserialize($data);
        if ($messageInfo['message_type'] == 3) {
            $storeActivity = array(
                'store_id'=>$messageInfo['store_id'],
                'activity_list_id'=>$messageInfo['message_data']['activity_list_id'],
                'create_time'=>date("Y-m-d H:i:s",time())
            );
            if(!Db::table('new_store_activity')->insert($storeActivity)){
                return false;
            }
        }
        $messageInfo['message_data'] = json_encode($messageInfo['message_data'],JSON_UNESCAPED_UNICODE);
        //-- 添加推送活动表
        if(!Db::table('store_push_message')->insert($messageInfo)){
            return false;
        }
        //极光推送
        $clientService = new ClientService();
        $receiver['alias'] = array('store'.$messageInfo['store_id']);//接收者
        $messageInfo['id'] = DB::table('store_push_message')->getLastInsID();
        $messageInfo['create_time'] = $storeActivity['create_time'];
        $clientService->push($messageInfo['message_cont'],$receiver,'活动通知',json_encode($messageInfo));
        return true;
    }
}