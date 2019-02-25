<?php
namespace app\api\controller;
set_time_limit(0);
use app\api\model\NavModel;
use app\api\model\OrderModel;
use app\api\model\OrderRefundModel;
use app\api\model\QueueJobsFailModel;
use app\api\model\StoreHotConfigModel;
use app\api\model\StoreModel;
use app\api\model\StoreOrderModel;
use app\api\model\StoreRebateLogModel;
use app\api\model\StoreReportDayModel;
use app\api\model\StoreVoucherModel;
use app\api\model\UserCouponsModel;
use app\api\model\UserMoneyLogModel;
use app\api\model\UserScoreLogModel;
use app\api\model\UsersModel;
use app\api\model\UserVoucherModel;
use app\api\model\UserVoucherRefundModel;
use app\api\service\PayService;
use app\api\service\RewardService;
use app\job\StoreReport;
use think\cache\driver\Redis;
use think\Config;
use think\Db;
use think\Queue;

class Task extends Common
{
    use \app\api\traits\GetConfig;
    use \app\api\traits\BuildParam;

    /**
     * @Author: guanyl
     * @Date: ${DATE} ${TIME}
     */
    public function test1(){
       /* $redis = new \Redis();
        $redis->connect('172.31.205.240');
        $redis->auth('xls1234@');
        $redis->set('aaa','111');
        print_r($redis->get('aaa'));die;*/
        $storeOrderModel = new StoreOrderModel();
        $orderInfo = $storeOrderModel->where('order_id','476')->find();
        $rewardService = new RewardService();
        $rewardService->giveUserReward($orderInfo->user_id,$orderInfo->buy_price);
        die;
    }
    public function test()
    {
        phpinfo();

        //php think queue:work --daemon --queue CreateUserCoupons,ExecBehavior,StoreBill,StoreDailyTask,StoreReport,StorePushMessage

        /*//$regions = DB::table('new_region')->where('p_id',3325)->select();
        //foreach ($regions as $region) {
            $url = "http://api.map.baidu.com/shangquan/forward/?qt=sub_area_list&ext=1&level=2&areacode=7&business_flag=0";
            $walk = file_get_contents($url);
            $walkResult = json_decode($walk,true);
            $regionResult = $walkResult['content']['sub'];
            print_r($regionResult);die;
            foreach ($regionResult as $result) {
                $merger_name =  DB::table('new_region')
                    ->where('p_id',$region['region_id'])
                    ->where('name',$result['area_name'])
                    ->find();
                DB::table('new_region')->where('region_id',$merger_name['region_id'])
                    ->update(['city_code'=>$result['area_code'],'geo'=>$result['geo']]);
            }
        //}*/
        /*echo 'ok';*/


    }


    /*
     * explain:订单未付款过期任务
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/13 18:50
     */
    public function storeOrder(StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel,StoreVoucherModel $storeVoucherModel,UserCouponsModel $userCouponsModel)
    {
        //-- 获取超时未支付订单
        $config = $this->getConfig('order_auto_close');
        $orderInfo = $storeOrderModel->where("order_state='T01' and order_type=0 and create_time<DATE_SUB(NOW(), INTERVAL $config MINUTE)")->order('create_time','ASC')->find();
        if(empty($orderInfo)) return '暂无过期订单';
        //-- 开启事物
        $storeOrderModel->startTrans();
        //-- 开启redis事务
        $redisModel = new Redis(Config::get('queue'));
        $redis = $redisModel->handler();
        $redis->multi();
        $voucherStockKey = 'voucher'.$orderInfo->voucher_id;
        for ($i=0;$i<$orderInfo->voucher_num;$i++){
            if(!$redis->lpush("$voucherStockKey",1)){
                $storeOrderModel->rollback();
                return 'redis插入库存队列错误';
            }
        }
        //-- 编辑订单
        if(!$storeOrderModel->update(['order_state'=>'T05'],['order_id'=>$orderInfo->order_id])){
            $storeOrderModel->rollback();
            return '编辑订单状态失败';
        }
        //-- 编辑用户抵用券
        if(!$userVoucherModel->update(['used_state'=>'C04'],['order_id'=>$orderInfo->order_id])){
            $storeOrderModel->rollback();
            return '编辑用户抵用券状态失败';
        }
        //-- 加库存
        if(!$storeVoucherModel->where('voucher_id',$orderInfo->voucher_id)->setInc('voucher_stock',$orderInfo->voucher_num)){
            $storeOrderModel->rollback();
            return '返还库存失败';
        }
        //-- 返还优惠券
        if($orderInfo->user_coupons_id>0){
            $data = [
                'used_state'=>'C02',
                'used_time'=>null
            ];
            if(!$userCouponsModel->update($data,['user_coupons_id'=>$orderInfo->user_coupons_id])){
                $storeOrderModel->rollback();
                return "返还优惠券失败";
            }
        }
        $storeOrderModel->commit();
        $redis->exec();
        return "成功";
    }
    
    /*
     * explain:订单自动确认收货
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/18 16:54
     */
    public function ordeAutoConfirma(OrderModel $orderModel,OrderRefundModel $orderRefundModel)
    {
        //-- 获取超过确认收货时间的订单
        $config = $this->getConfig('order_auto_confirma');
        $orderInfo = $orderModel->where("order_state='Q03' and shipping_time<DATE_SUB(NOW(), INTERVAL $config DAY)")->order('create_time','ASC')->find();
        if(empty($orderInfo)) return '暂无自动收货订单';
        $count = $orderRefundModel->where("order_id=$orderInfo->order_id and refund_state<>'W06'")->count();
        if($count>0){
            return '有退款中商品,请取消退款申请或等待退款完成!';
        }
        if (!$orderModel->update(['order_state'=>'Q04'],['order_id'=>$orderInfo->order_id]))
            return '更改订单状态失败';
        return '成功';
    }

    /*
     * explain:用户券超时未使用
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/4 14:25
     */
    public function userVoucherAutoCancel(StoreOrderModel $storeOrderModel, UserVoucherModel $userVoucherModel,UsersModel $usersModel,UserMoneyLogModel $userMoneyLogModel,UserVoucherRefundModel $userVoucherRefundModel,UserScoreLogModel $userScoreLogModel,PayService $payService,StoreModel $storeModel,StoreRebateLogModel $storeRebateLogModel)
    {
        $newTime = $this->getTimeToday();
        //-- 获取超时未使用订单
        $storeOrder = $storeOrderModel
            ->alias('o')
            ->field('o.order_id,o.order_sn,o.pay_type,o.user_give_score,o.voucher_num,o.store_give_score,p.pay_sn,p.total_fee')
            ->where("o.use_end_date<'".$newTime."' and o.order_state='T02' and o.order_type=0 and o.clear_state=0 and p.pay_state=1")
            ->join('new_store_order_pay p','p.order_id=o.order_id','left')
            ->order('o.create_time','ASC')
            ->find();
        if(empty($storeOrder)){
            return '没有超时未使用券';
        }
        //-- 获取订单券列表
        $userVoucher = $userVoucherModel
            ->append('refund_state')
            ->where('order_id',$storeOrder->order_id)
            ->select();
        //-- 开启事物
        $storeOrderModel->startTrans();
        foreach ( $userVoucher as $v){
            if($v->refund_state === '0' && $v->used_state=='C02'){
                switch ($storeOrder->pay_type){
                    case 0:
                        //-- 余额支付
                        //-- 退款列表状态
                        if(!$usersModel->where('user_id',$v->user_id)->setInc('user_money',$v->buy_price)){
                            $storeOrderModel->rollback();
                            $this->jkReturn('-1','网络延时,请稍后重试',[]);
                        }
                        //-- 记录用户账户变动日志
                        if (!$userMoneyLogModel->create(['money'=>$v->buy_price,'type'=>2,'desc'=>'订单退款:'.$v->voucher_sn,'user_id'=>$v->user_id])){
                            $storeOrderModel->rollback();
                            $this->jkReturn('-1','网络延时,请稍后重试',[]);
                        }
                        break;
                    case 1:
                        //-- 支付宝支付

                        break;
                    case 2:
                        //-- 微信支付
                        $refundNo = $this->refundSn();
                        $order = [
                            'out_trade_no' => $v->pay_sn,
                            'out_refund_no' =>$refundNo ,
                            'total_fee' => $storeOrder->total_fee*100,
                            'refund_fee' => $v->buy_price*100,
                            'refund_desc' => '新零售-订单退款:'.$v->order_sn,
                            'type' => 'app'
                        ];
                        $config = [
                            'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatRefundNotify'
                        ];
                        $payRefund = $payService->setConfig($config)->wechatRefund($order);
                        if($payRefund->result_code != 'SUCCESS'){
                            $storeOrderModel->rollback();
                            $this->jkReturn('-1','网络延时,请稍后重试',[]);
                        }
                        break;
                    default:
                        $storeOrderModel->rollback();
                        return '支付参数错误';
                        break;
                }
                //-- 插入退款列表状态
                $orderRefundData = $v->toArray();
                $refundNo = $this->refundSn();
                $orderRefundData['voucher_img'] = serialize(array_splice($orderRefundData['voucher_img'],1));
                $orderRefundData['refund_sn'] = $refundNo;
                $orderRefundData['reason_id'] = 2;
                $orderRefundData['refund_desc'] = '优惠券超时未使用,系统自动退还余额';
                $orderRefundData['refund_price'] = $v->buy_price;
                $orderRefundData['refund_state'] = 'D04';
                $orderRefundData['refund_time'] = $this->getTime();
                if(!$userVoucherRefundModel->create($orderRefundData)){
                    $userVoucherRefundModel->rollback();
                    return '添加退款列表失败';
                }
                //-- 订单退款金额
                if(!$storeOrderModel->where(['order_id'=>$v->order_id])->setInc('refund_price',$v->buy_price)){
                    $storeOrderModel->rollback();
                    return '记录订单退款金额失败';
                }
                //-- 改变用户抵用券状态
                if(!$userVoucherModel->update(['used_state'=>'C04'],['user_voucher_id'=>$v->user_voucher_id])){
                    $storeOrderModel->rollback();
                    return '改变用户券状态失败';
                }
                //-- 判断是否改变订单状态
                $orderState = 'T05';
                if($userVoucherModel->where("order_id=$v->order_id and used_state='C05'")->find()){
                    $orderState = 'T04';
                }
                if($userVoucherModel->where("order_id=$v->order_id and used_state='C04'")->find()){
                    $orderState = 'T04';
                }
                if($userVoucherModel->where("order_id=$v->order_id and used_state='C03'")->find()){
                    $orderState = 'T03';
                }
                if($userVoucherModel->where("order_id=$v->order_id and used_state='C02'")->find()){
                    $orderState = 'T02';
                }
                if($userVoucherModel->where("order_id=$v->order_id and used_state='C01'")->find()){
                    $orderState = 'T01';
                }
                if(!$storeOrderModel->update(['order_state'=>$orderState],['order_id'=>$v->order_id])){
                    $storeOrderModel->rollback();
                    return '订单状态改变失败';
                }
                //-- 如果有赠送积分,扣除用户积分
                if($storeOrder->user_give_score>0){
                    $delScore = ceil($storeOrder->user_give_score/$storeOrder->voucher_num);
                    if($storeOrder->user_give_score-$delScore<0){
                        $delScore = $storeOrder->user_give_score;
                    }
                    //-- 更新订单增送积分
                    if (!$storeOrderModel->where(['order_id'=>$v->order_id])->setDec('user_give_score',$delScore)){
                        $storeOrderModel->rollback();
                        return '更新订单赠送积分失败';
                    }
                    //-- 扣除积分并记录日志
                    if (!$usersModel->where('user_id',$v->user_id)->setDec('user_score',$delScore)) {
                        $storeOrderModel->rollback();
                        return '扣除用户积分失败';
                    }
                    //-- 记录日志
                    if (!$userScoreLogModel->save(['score'=>-$delScore,'desc'=>'订单退款,订单:'.$v->order_sn,'user_id'=>$v->user_id])){
                        $storeOrderModel->rollback();
                        return '记录积分日志失败';
                    }
                }
                //-- 如果店铺有赠送积分,扣除店铺积分
                if($storeOrder->store_give_score>0){
                    $delScore = ceil($storeOrder->store_give_score/($storeOrder->voucher_num-$userVoucherRefundCount+1));
                    if($storeOrder->store_give_score-$delScore<0){
                        $delScore = $storeOrder->store_give_score;
                    }
                    //-- 更新订单增送积分
                    if(!$storeOrderModel->where(['order_id'=>$v->order_id])->setDec('store_give_score',$delScore)){
                        $storeOrderModel->rollback();
                        $this->jkReturn('-1','网络延时,请稍后重试',[]);
                    }
                    //-- 扣除积分并记录日志
                    if (!$storeModel->where('store_id',$v->store_id)->setDec('store_score',$delScore)) {
                        $storeOrderModel->rollback();
                        $this->jkReturn('-1','网络延时,请稍后重试',[]);
                    }
                    //-- 记录日志
                    if (!$storeRebateLogModel->save(['score'=>-$delScore,'desc'=>'有用户订单退款,订单:'.$v->order_sn,'user_id'=>$v->user_id])) {
                        $storeOrderModel->rollback();
                        $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
                    }
                }
            }
        }
        $storeOrderModel->commit();
        return '自动退款成功';
    }

    /*
     * explain:店铺报表
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/18 16:11
     */
    public function storeReport(StoreModel $storeModel,QueueJobsFailModel $queueJobsFailModel)
    {
        //-- 获取所有店铺数据(此处有bug ,队列存储失败 ,将无法进行统计,并且未做失败记录)
        $storeInfo = $storeModel->field('store_id')->where("store_type=1")->order('store_id','asc')->select()->toArray();
        for ($i=0;$i<count($storeInfo);$i++){
            $job = 'app\job\StoreReport';
            if(!Queue::push($job, $storeInfo[$i]['store_id'] , $queue = "StoreReport")){
                if(!Queue::push($job, $storeInfo[$i]['store_id'] , $queue = "StoreReport")){
                    //-- 插入失败队列
                    $data = [
                        'queue'=>'StoreReport',
                        'job'=>$job,
                        'data'=>$storeInfo[$i]['store_id']
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

    /*
     * explain:
     * params :店铺人气计算
     * authors:Mr.Geng
     * addTime:2018/5/24 13:52
     */
    public function storeDailyTask(StoreModel $storeModel,QueueJobsFailModel $queueJobsFailModel)
    {
        //-- 获取所有店铺数据(此处有bug ,队列存储失败 ,将无法进行统计,并且未做失败记录)
        $storeInfo = $storeModel->field('store_id')->where("store_type=1")->order('store_id','asc')->select();
        $job = 'app\job\StoreDailyTask';
        for ($i=0;$i<$storeInfo->count();$i++){
            if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreDailyTask")){
                if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreDailyTask")){
                    //-- 插入失败队列
                    $data = [
                        'queue'=>'StoreDailyTask',
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

    /*
     * explain:店铺账单统计
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/7 10:18
     */
    public function storeBill(StoreModel $storeModel,QueueJobsFailModel $queueJobsFailModel)
    {
        //-- 获取所有店铺数据(此处有bug ,队列存储失败 ,将无法进行统计,并且未做失败记录)
        $storeInfo = $storeModel->field('store_id')->where("store_type=1")->order('store_id','asc')->select();
        for ($i=0;$i<$storeInfo->count();$i++){
            $job = 'app\job\StoreBill';
            if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreBill")){
                if(!Queue::push($job, $storeInfo[$i]->store_id , $queue = "StoreBill")) {
                    //-- 插入失败队列
                    $data = [
                        'queue' => 'StoreBill',
                        'job' => $job,
                        'data' => $storeInfo[$i]->store_id
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

    /*
     * explain:最高人气值自动更新
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/28 10:33
     */
    public function storeHotMax(NavModel $navModel,StoreReportDayModel $storeReportDayModel,StoreHotConfigModel $storeHotConfigModel)
    {
        //-- 获取有效行业
        $navList = $navModel->where('disabled',1)->select();
        foreach ($navList as $v){
            $browse_num_max = $storeReportDayModel->where("nav_id=$v->nav_id and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY),'%Y-%m-%d')")->max('store_browse_num');
            $browse_num_max = $browse_num_max>0?$browse_num_max:10000;
            $valid_order_max = $storeReportDayModel->where("nav_id=$v->nav_id and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY),'%Y-%m-%d')")->max('valid_order');
            $valid_order_max = $valid_order_max>0?$valid_order_max:10000;
            $offline_order_max = $storeReportDayModel->where("nav_id=$v->nav_id and DATE_FORMAT(`create_time`,'%Y-%m-%d') > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY),'%Y-%m-%d')")->max('offline_order');
            $offline_order_max = $offline_order_max>0?$offline_order_max:10000;
            //-- 更新人气配置
            if($storeHotConfigModel->where('nav_id',$v->nav_id)->find()){
                $storeHotConfigModel->save(['browse_num_max'=>$browse_num_max,'valid_order_max'=>$valid_order_max,'offline_order_max'=>$offline_order_max],['nav_id'=>$v->nav_id]);
            }
        }
        echo 'ok';
        exit;
    }
}