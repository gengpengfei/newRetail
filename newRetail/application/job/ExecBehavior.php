<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 赠送积分和活跃并更新用户等级的队列任务
    +----------------------------------------------------------
     * @access
     * 开启队列 php think queue:work --daemon --queue ExecBehavior
     * 插入队列 Queue::push('app\job\ExecBehavior',serialize($data),$queue="ExecBehavior")
    +----------------------------------------------------------
     * @return (失败3次以上移除该任务)
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\service\BehaviorService;
use think\queue\Job;

class ExecBehavior{
    public function fire(Job $job, $data){
        $data = unserialize($data);
        $behaviorServer = new BehaviorService($data);
        if($behaviorServer->execBehavior()){
            $job->delete();
        }else{
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            if ($job->attempts() > 3) {
                $job->delete();
            }
        }
    }

}