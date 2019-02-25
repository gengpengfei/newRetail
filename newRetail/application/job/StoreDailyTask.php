<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 店铺人气值计算队列任务(失败3次以上重新插入对列执行该任务)
    +----------------------------------------------------------
     * @access php think queue:work --daemon --queue StoreReport
+----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use think\Db;
use think\queue\Job;

class StoreDailyTask{
    use \app\api\traits\GetConfig;
    public function fire(Job $job, $data){
        if($this->storeDailyTask($data)){
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
    public function storeDailyTask($data)
    {
        $storeId =  $data;
        //-- 店铺详情
        $storeInfo = Db::table("new_store")->where("store_id=$storeId")->find();
        $storeHotConfig = Db::table("new_store_hot_config")->where("nav_id=".$storeInfo['nav_id'])->find();

        if(empty($storeHotConfig)){
            return true;
        }
        //-- 线上有效订单数
        $validOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=0 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY),'%Y-%m-%d')")
            ->count();
        //-- 线下有效订单数
        $offlineOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=1 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY),'%Y-%m-%d')")
            ->count();

        //-- 店铺访问量
        $storeBrowseNum = Db::table('new_store_browse_log')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 7 DAY),'%Y-%m-%d')")
            ->count();
        $storeHotConfig['browse_num_max'] = $storeHotConfig['browse_num_max']>0? $storeHotConfig['browse_num_max']:10000;
        $storeHotConfig['valid_order_max'] = $storeHotConfig['valid_order_max']>0? $storeHotConfig['valid_order_max']:10000;
        $storeHotConfig['offline_order_max'] = $storeHotConfig['offline_order_max']>0? $storeHotConfig['offline_order_max']:10000;
        $storeHot = sprintf('%.0f',(($storeBrowseNum/$storeHotConfig['browse_num_max'])*$storeHotConfig['browse_num'])+(($validOrder/$storeHotConfig['valid_order_max'])*$storeHotConfig['valid_order'])+(($offlineOrder/$storeHotConfig['offline_order_max'])*$storeHotConfig['offline_order']));
        $storeHot = $storeHot>100?100 : $storeHot;
        $storeHot = $storeHot<60 ? 60 : $storeHot;
        Db::startTrans();
        //-- 更新店铺表
        $totalData = [
            'store_hot'=>$storeHot,
            'update_time'=>date("Y-m-d H:i:s")
        ];
        if(!Db::table('new_store')->where(['store_id'=>$storeId])->update($totalData)){
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }
}