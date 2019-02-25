<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 店铺账单计算队列任务(失败3次以上重新插入对列执行该任务)
    +----------------------------------------------------------
     * @access php think queue:work --daemon --queue StoreBill
+----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use think\Db;
use think\queue\Job;

class StoreBill{
    use \app\api\traits\GetConfig;
    use \app\api\traits\BuildParam;
    public function fire(Job $job, $data){
        if($this->storeBill($data)){
            $job->delete();
        }else{
            //-- 失败超过3次,记录表并推送消息
            if ($job->attempts() > 3) {
                $data = [
                    'create_time'=>$this->getTime(),
                    'onload'=>$data,
                    'attempts'=>3,
                    'fail_desc'=>''
                ];
                if(Db::table('new_queue_fail_log')->insert($data)){
                    $job->delete();
                }
            }
        }
    }
    public function storeBill($data){
        $storeId = $data;
        //-- 店铺详情
        $storeInfo = Db::table('new_store')
            ->field('nav_id,store_credit')
            ->where('store_id='.$storeId.' and store_type=1')
            ->find();
        if(empty($storeInfo['nav_id'])){
            return true;
        }
        //-- 判断该店铺 T+N 的日期
        $clearRule = Db::table('new_store_clear_rule')->where('disabled=1')->order('rule_range','desc')->select();
        foreach ($clearRule as $v){
            switch($v['rule_range']){
                case 0:
                    //-- 行业
                    if(in_array($storeInfo['nav_id'],explode(',',$v['rule_range_info']))){
                        $times = $v['rule_info'];
                    }
                    break;
                case 1:
                    //-- 店铺
                    if(in_array($storeId,explode(',',$v['rule_range_info']))){
                        $times = $v['rule_info'];
                    }
                    break;
                default:
                    break;
            }
            if($times>0){
                break;
            }
        }
        //-- 如果结算日期不存在,跳过结算账单
        if(empty($times)){
            return true;
        }
        //-- 查找最近一次结算时间
        $nearClearTime = Db::table('new_store_clear_bill')->field('clear_start_time,clear_end_time
')->where("store_id=$storeId")->order('create_time','desc')->find();
        //-- 当天时间
        $newToDay = $this->getTimeToday();
        $newDay = strtotime($newToDay);
        //-- 新店铺第一次结算没有记录
        if(!empty($nearClearTime['clear_end_time'])){
            //-- 结算时间n天后的时间(12点之后开启队列,故需要加1天)
            $timeData = strtotime($this->getTimeX($times+1,$nearClearTime['clear_end_time']));
            //-- 未到结算时间 , 跳出队列
            if($newDay<$timeData){
                return true;
            }
            $clear_start_time = $this->getTimeToday(strtotime($this->getTimeX(1,$nearClearTime['clear_end_time'])));
            $clear_end_time = $this->getTimeToday(strtotime($this->getTimeX($times,$nearClearTime['clear_end_time'])));
        }else{
            //-- 查询第一个单子的下单时间
            $res = Db::table('new_store_clear')
                ->field('create_time')
                ->where('store_id='.$storeId.' and clear_state=0')
                ->order('create_time','asc')
                ->find();
            if(empty($res['create_time'])){
                return true;
            }
            $clear_start_time = $this->getTimeToday(strtotime($res['create_time']));
            $clear_end_time = $this->getTimeToday(strtotime($this->getTimeX(-1,$newToDay)));
        }
        //-- 查询条件为结算的后一天凌晨
        $clear_check_time = $this->getTimeToday(strtotime($this->getTimeX(1,$clear_end_time)));
        //-- 结算$times天金额
        $amount = Db::table('new_store_clear')->where('store_id='.$storeId.' and clear_state=0 and create_time>"'.$clear_start_time.'" and create_time<"'.$clear_check_time.'"')->sum('clear_price');
        //-- 结算总单数
        $count = Db::table('new_store_clear')->where('store_id='.$storeId.' and clear_state=0 and create_time>"'.$clear_start_time.'" and create_time<"'.$clear_check_time.'"')->count();
        //-- 查看是否已经生成账单
        $res = Db::table('new_store_clear_bill')->where("store_id=$storeId and clear_start_time='.$clear_start_time.'")->find();
        if(!empty($res['store_id'])){
            return true;
        }
        //-- 开启事物
        Db::table('new_store_clear_bill')->startTrans();
        $time = $this->getTime();
        //-- 最迟打款时间
        $payTimes = $this->getConfig('clear_bill_pay_times');
        $pay_end_time = $this->getTimeX($payTimes,$clear_end_time);
        $data = [
            'store_id'=>$storeId,
            'pay_price'=>$amount,
            'clear_start_time'=>$clear_start_time,
            'clear_end_time'=>$clear_end_time,
            'clear_count'=>$count,
            'create_time'=>$time,
            'pay_end_time'=>$pay_end_time
        ];
        //-- 添加账单
        if(!Db::table('new_store_clear_bill')->insert($data)){
            Db::table('new_store_clear_bill')->rollback();
            return false;
        }
        //-- 改变结算表状态
        $res = Db::table('new_store_clear')->where('store_id='.$storeId.' and clear_state=0 and create_time>"'.$clear_start_time.'" and create_time<"'.$clear_check_time.'"')->update(['clear_state'=>1,'clear_time'=>$time]);
        if(!$res == $count){
            Db::table('new_store_clear_bill')->rollback();
            return false;
        }
        //-- 发送店铺主通知
        $info = [
            'pay_price'=>$amount,
            'clear_start_time'=>$clear_start_time ,
            'clear_end_time'=>$clear_end_time,
            'pay_end_time'=>$pay_end_time,
            'create_time'=>$time
        ];
        $data = [
            'store_id'=>$storeId,
            'message_type'=>5,
            'message_cont'=>"您的账单已生成",
            'message_data'=>json_encode($info),
            "create_time"=>$time
        ];
        if(!Db::table('store_push_message')->insert($data)){
            Db::table('new_store_clear_bill')->rollback();
            return false;
        }
        Db::table('new_store_clear_bill')->commit();
        return true;
    }
}