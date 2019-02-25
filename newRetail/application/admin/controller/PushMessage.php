<?php
namespace app\admin\controller;


class PushMessage extends Common
{
    /*
     * explain:系统推送
     * params :
     * authors:Mr.Geng
     * addTime:2018/7/9 17:14
     */
    public function PushMessage(QueueJobsFailModel $queueJobsFailModel)
    {
        //-- 获取所有店铺数据(此处有bug ,队列存储失败 ,将无法进行统计,并且未做失败记录)
        $storeInfo = $storeModel->field('store_id')->where("store_type=1")->order('store_id','asc')->select();
        for ($i=0;$i<$storeInfo->count();$i++){
            $job = 'app\job\StoreReport';
            if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreReport")){
                if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreReport")){
                    //-- 插入失败队列
                    $data = [
                        'queue'=>'StoreReport',
                        'job'=>$job,
                        'data'=>$storeInfo[$i]->store_id
                    ];
                    $queueJobsFailModel->save($data);
                    echo 'fail';
                    exit;
                }
            }
        }
        echo 'ok';
        exit;
    }
}
