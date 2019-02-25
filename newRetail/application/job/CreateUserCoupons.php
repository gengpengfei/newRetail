<?php
namespace app\job;
/**
+----------------------------------------------------------
 * @explain 创建用户红包的队列任务(失败3次以上移除该任务)
+----------------------------------------------------------
 * @access php think queue:work --daemon --queue CreateUserCoupons
+----------------------------------------------------------
 * @return class
+----------------------------------------------------------
 * @acter Mr.jlcr
+----------------------------------------------------------
 **/
use think\Db;
use think\queue\Job;

class CreateUserCoupons{
    use \app\admin\traits\BuildParam;
    public function fire(Job $job, $data){
        if($this->createuserCoupons($data)){
            $job->delete();
        }else{
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            if ($job->attempts() > 3) {
                //记录错误日志
                $errorqueue=array(
                    'queue'=>'CreateUserCoupons',
                    'onload'=>$data,
                    'attempts'=>$job->attempts(),
                    'create_time'=>date('Y-m-d H:i:s')
                );
                $getdata=unserialize($data);
                $userinfo=Db::table('new_user')->field(['user_id','user_name'])->where('user_id',$getdata['user_id'])->find();
                $couponsinfo=Db::table('new_coupons')->field(['coupons_id','coupons_name'])->where('coupons_id',$getdata['coupons_id'])->find();
                $errorqueue['fail_desc']='发送红包失败。接收红包失败的会员id:'.$getdata['user_id'].'；用户名为:'.$userinfo['user_name'].'；红包id为:'.$getdata['coupons_id'].'；红包名为:'.$couponsinfo['coupons_name'];
                if (!Db::table('new_queue_fail_log')->insert($errorqueue)){
                    return false;
                }
                $job->delete();
            }
        }
    }

    protected function createuserCoupons($data)
    {
        $data= unserialize($data);
        $couponsData = Db::table('new_coupons')
            ->field(['coupons_id','coupons_name','coupons_desc','coupons_img','coupons_type','coupons_price','use_rank','min_amount','use_scope','use_scope_info','use_method','use_method_info','use_start_date','use_end_date'])
            ->where('coupons_id',$data['coupons_id'])->find();
        $usercouponsData=$couponsData;
        $usercouponsData['user_id']=$data['user_id'];
        $usercouponsData['user_state']='C01';
        $usercouponsData['coupons_sn']=$this->getOrderSn();
        if (!Db::table('new_user_coupons')->insert($usercouponsData)){
            return false;
        }
        return true;
    }

}