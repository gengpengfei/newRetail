<?php
namespace app\api\controller;

use app\api\model\StoreModel;
use app\api\model\StoreOrderModel;
use app\api\model\StoreProtectModel;
use app\api\model\StoreVoucherModel;
use app\api\model\UserCouponsModel;
use app\api\model\UserVoucherModel;
use think\cache\driver\Redis;
use think\Config;
use think\Queue;
use think\Request;

/**
    +----------------------------------------------------------
     * @explain 店铺订单队列类
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
class Order extends Common {
    use \app\api\traits\BuildParam;

    protected $user_id;
    protected $voucher_id;
    protected $user_coupons_id;
    //-- 新零售优惠(如果有红包id , 就是红包金额,如果没有,就是新零售优惠)
    protected $coupons_price;
    protected $voucher_num;
    protected $voucherStockKey;
    protected $request;
    protected $redis;
    protected $storeVoucherModel;
    protected $userVoucherModel;
    /*
     * explain:初始化参数
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/29 14:26
     */
    public function __construct(Request $request,StoreVoucherModel $storeVoucherModel,UserVoucherModel $userVoucherModel)
    {
        $param = $request->param();
        $redis = new Redis(Config::get('queue'));
        $this->redis = $redis->handler();
        /*if(empty($this->redis->socket)){
            $this->jkReturn('-1','系统繁忙,请稍后重试',[]);
        }*/
        $this->storeVoucherModel = $storeVoucherModel;
        $this->userVoucherModel = $userVoucherModel;
        if($voucherId = $param['voucher_id']){
            $this->voucher_id = $voucherId;
            $this->voucherStockKey = 'voucher'.$voucherId;
        }
        $this->user_id = $param['user_id'];
        $this->voucher_num = $param['voucher_num']??1;
        $this->user_coupons_id = $param['user_coupons_id']?? null;
        $this->coupons_price = empty($param['coupons_price'])?0:$param['coupons_price'];
    }

    /*
     * explain:订单确认页
     * params :@voucher_id
     * authors:Mr.Geng
     * addTime:2018/3/26 14:21
     */
    public function checkOrder(Request $request,StoreVoucherModel $storeVoucherModel,StoreProtectModel $storeProtectModel)
    {
        $param = $request->param();
        //-- 库存队列判断
        $this->beforeDetail();
        $voucherInfo = $storeVoucherModel
            ->field(['store_id','voucher_id','voucher_name','voucher_desc','voucher_img','voucher_price','voucher_amount','limit_time'])
            ->where('voucher_id',$param['voucher_id'])
            ->find();
        $protectList = $storeProtectModel->where('disabled','1')->order('sort_order','asc')->select();
        $data = [
            'voucher_info'=>$voucherInfo,
            'protect_list'=>$protectList
        ];
        $this->jkReturn(1,'订单确认',$data);
    }

    /*
     * explain:创建订单(对列形式,半成品)
     * params :@user_id @voucher_id @voucher_num
     * authors:Mr.Geng
     * addTime:2018/3/29 18:26
     */
    public function createOrderQueue(){
        $this->beforeDetail();
        !$this->user_id && $this->jkReturn(-1,'用户未登录');
        $voucherId = $this->voucher_id;
        $time = $this->getTime();
        $voucherInfo = $this->storeVoucherModel
            ->field(['voucher_stock','limit_time'])
            ->where(" voucher_id=$voucherId and sell_start_date<'".$time."' and sell_end_date>'".$time."' and disabled=1")
            ->find();
        !$voucherInfo && $this->jkReturn(-1,"对不起当前商品已下架！");
        //- 判断用户购买数量限制
        $userNum = $this->userVoucherModel
            ->where("user_id=$this->user_id and (used_state='C02' or used_state='C01') and use_end_date>'".$time."'")
            ->count();
        if($voucherInfo['limit_time']>0){
            if(($userNum+$this->voucher_num)>$voucherInfo['limit_time']){
                $num = $voucherInfo['limit_time']-$userNum;
                $this->jkReturn(-1,"您当前可购买数量为$num!");
            }
        }
        $redis = $this->redis;
        $voucherStockKey = $redis->llen("{$this->voucherStockKey}");
        if ($voucherStockKey<$this->voucher_num){
            $this->jkReturn(-1,"当前库存不足!");
        }
        //-- 开启redis事务
        $redis->multi();
        for ($i=0;$i<$this->voucher_num;$i++){
            $voucherStockKey = $redis->lpop("{$this->voucherStockKey}");
            if(!$voucherStockKey)
                $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        $redis->exec();
        // 插入抢购用户信息
        $order_sn = $this->getOrderSn();
        $userinfo = array(
            "user_id" => $this->user_id,
            'voucher_id'=>$this->voucher_id,
            'voucher_num'=>$this->voucher_num,
            'order_sn'=>$order_sn,
            'user_coupons_id'=>$this->user_coupons_id,
            "create_time" => time()
        );
        $job = 'app\job\CreateOrder';
        $request = Queue::push($job, serialize($userinfo) , $queue = "CreateOrder");
        !$request && $this->jkReturn(-1,"系统繁忙,请重试!");
        $this->jkReturn(1,"正在下单中!",$order_sn);
    }

    /*
     * explain:创建订单(非队列形式)
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/25 11:47
     */
    public function createOrder(Request $request,StoreOrderModel $storeOrderModel,UserCouponsModel $userCouponsModel)
    {
        $time = $this->getTime();
        $this->beforeDetail();
        !$this->user_id && $this->jkReturn(-1,'用户未登录');

        $myOrder = $storeOrderModel->where(['user_id'=>$this->user_id, 'order_state'=>'T01', 'order_type'=>0])->find();
        if (count($myOrder) >= 1) {
            $data = [
                'order_info'=>$myOrder
            ];
            $this->jkReturn('2',"对不起,您有未结算的订单！",$data);
        }
        $voucherId = $this->voucher_id;
        $voucherInfo = $this->storeVoucherModel
            ->where(" voucher_id=$voucherId and sell_start_date<'".$time."' and sell_end_date>'".$time."' and disabled=1")
            ->find();
        !$voucherInfo && $this->jkReturn(-1,"对不起当前商品已下架！");
        //- 判断用户购买数量限制
        $userNum = $this->userVoucherModel
            ->where("user_id=$this->user_id and voucher_id=$voucherInfo->voucher_id and (used_state='C02' or used_state='C01') and use_end_date>'".$time."'")
            ->count();
        if($voucherInfo->limit_time<>0){
            if(($userNum+$this->voucher_num)>$voucherInfo['limit_time']){
                $num = $voucherInfo['limit_time']-$userNum;
                $num  = $num<0? 0:$num;
                $this->jkReturn(-1,"您当前可购买数量为$num!");
            }
        }
        $redis = $this->redis;
        $voucherStockKey = $redis->llen("{$this->voucherStockKey}");
        if ($voucherStockKey<$this->voucher_num){
            $this->jkReturn(-1,"当前库存不足!");
        }
        $amount = $this->voucher_num*$voucherInfo['voucher_price'];
        if($this->user_coupons_id){
            //-- 判断红包是否可用
            $userCoupons = $userCouponsModel
                ->where("user_id=$this->user_id and used_state='C02' and use_start_date<'".$time."' and use_end_date>'".$time."' and min_amount<=$amount and user_coupons_id=$this->user_coupons_id")
                ->order('create_time','desc')
                ->find();
            if(empty($userCoupons)){
                $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试!");
            }
            if($userCoupons->use_method === 0){
                //-- 满减
                if($userCoupons->use_method_info != $this->coupons_price){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试!");
                }
            }else{
                //-- 满折
                if((100-$userCoupons->use_method_info)*$amount != $this->coupons_price){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试!");
                }
            }
        }
        //-- 开启订单事物
        $storeOrderModel->startTrans();
        //-- 开启redis事务
        $redis->multi();
        for ($i=0;$i<$this->voucher_num;$i++){
            $voucherStockKey = $redis->lpop("{$this->voucherStockKey}");
            if(!$voucherStockKey)
                $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        //-- 订单数据
        $order_sn = $this->getOrderSn();
        $orderData = $voucherInfo->toArray();
        $orderData['user_id'] = $this->user_id;
        $orderData['voucher_num'] = $this->voucher_num;
        $orderData['voucher_img'] = serialize(array_splice($voucherInfo->voucher_img,1));
        $orderData['voucher_id'] = $this->voucher_id;
        $orderData['order_price'] = $amount;
        $orderData['buy_price'] = $amount-$this->coupons_price;
        $orderData['coupons_price'] = $this->coupons_price;
        $orderData['user_coupons_id'] = $this->user_coupons_id;
        $orderData['order_sn'] = $order_sn;
        $orderData['order_type'] = 0;
        $orderData['order_state'] = $orderData['voucher_type']===1? 'T01':'T02';
        //-- 创建订单
        if(!$storeOrderModel->allowField(true)->create($orderData)){
            $storeOrderModel->rollback();
            $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        $orderId = $storeOrderModel->getLastInsID();
        //-- 创建用户抵用券
        for ($i=0;$i<$this->voucher_num;$i++){
            $userDate = $voucherInfo->toArray();
            $userDate['voucher_img'] = serialize(array_splice($voucherInfo->voucher_img,1));
            $userDate['order_id'] = $orderId;
            $userDate['order_sn'] = $order_sn;
            $userDate['user_id'] = $this->user_id;
            $userDate['voucher_sn'] = $this->getVoucherSn();
            $userDate['used_state'] = $orderData['voucher_type']===1? 'C01':'C02';
            $userDate['coupons_price'] = sprintf("%.2f",$this->coupons_price/$this->voucher_num);
            $userDate['buy_price'] = sprintf("%.2f",$orderData['buy_price']/$this->voucher_num);
            if(!$this->userVoucherModel->allowField(true)->create($userDate)){
                $storeOrderModel->rollback();
                $this->jkReturn(-1,"系统繁忙,请重试!");
            }
        }
        //-- 增加出售数量
        if(!$this->storeVoucherModel->where('voucher_id',$this->voucher_id)->setInc('sell_num',$this->voucher_num)){
            $storeOrderModel->rollback();
            $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        //-- 减库存
        if(!$this->storeVoucherModel->where('voucher_id',$this->voucher_id)->setDec('voucher_stock',$this->voucher_num)){
            $storeOrderModel->rollback();
            $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        //-- 使用优惠券
        if($this->user_coupons_id??0){
            $data = [
                'used_state'=>'C03',
                'used_time'=>$this->getTime()
            ];
            if(!$userCouponsModel->update($data,['user_coupons_id'=>$this->user_coupons_id])){
                $storeOrderModel->rollback();
                $this->jkReturn(-1,"系统繁忙,请重试!");
            }
        }
        $storeInfo = StoreModel::where(['store_id'=>$voucherInfo->store_id])->find();
        $orderData['order_id'] = $orderId;
        $orderData['voucher_img'] = $voucherInfo->voucher_img;
        $data = [
            'store_info'=>$storeInfo,
            'order_info'=>$orderData
        ];
        $storeOrderModel->commit();
        $redis->exec();
        $this->jkReturn(1,"下单成功!",$data);
    }

    /*
     * explain:抵用券库存判定
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/29 13:54
     */
    protected function beforeDetail(){
        $voucherId = $this->voucher_id;
        $time = $this->getTime();
        $voucherInfo = $this->storeVoucherModel
            ->field(['voucher_stock'])
            ->where(" voucher_id=$voucherId and sell_start_date<'".$time."' and sell_end_date>'".$time."' and disabled=1")
            ->find();
        if(empty($voucherInfo)){
            $this->jkReturn(-1,'对不起当前商品已下架！');
        }
        //-- 此处有一个漏洞,库存队列已经为零,但是下单队列未走完,则实际库存未减,再有用户下单时,会重新生成库存队列(待定)
        if($voucherInfo['voucher_stock'] < 0){
            /*$redis = $this->redis;
            $resetRedis = $redis->llen("{$this->voucherStockKey}");
            if(!$resetRedis){
                for ($i = 0; $i < $voucherInfo['voucher_stock']; $i ++) {
                    $redis->lpush("{$this->voucherStockKey}", 1);
                }
            }
            $resetRedis = $redis->llen("{$this->voucherStockKey}");
            !$resetRedis && $this->jkReturn(-1,'系统繁忙,请稍后重试!');*/
            $this->jkReturn(-1,'该商品已经被买完!');
        }
    }
}