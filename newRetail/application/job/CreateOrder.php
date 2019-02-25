<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 创建订单的队列任务(失败3次以上移除该任务)
    +----------------------------------------------------------
     * @access php think queue:work --daemon --queue CreateOrder
+----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use think\Db;
use think\queue\Job;

class CreateOrder{
    use \app\api\traits\BuildParam;
    public function fire(Job $job, $data){
        if($this->createOrder($data)){
            $job->delete();
        }else{
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            if ($job->attempts() > 3) {
                $job->delete();
            }
        }
    }

    protected function createOrder($data)
    {
        $data = unserialize($data);
        //-- 判断订单是否存在
        if($this->checkOrder($data['order_sn'])){
            return true;
        }
        $couponsPrice = 0;
        if($data['user_coupons_id']){
            //-- 判断红包是否可用
            $couponsPrice = 10;
        }
        //-- 订单数据
        $voucherData = Db::table('new_store_voucher')
            ->field(['store_id','voucher_id','voucher_name','voucher_desc','voucher_img','voucher_type','voucher_price','voucher_amount','use_method','use_method_info','is_pay_used','min_amount','use_start_date','use_end_date'])
            ->find($data['voucher_id']);
        $orderData = $voucherData;
        $orderData['user_id'] = $data['user_id'];
        $orderData['voucher_num'] = $data['voucher_num'];
        $orderData['voucher_id'] = $data['voucher_id'];
        $orderData['order_price'] = $data['voucher_num']*$orderData['voucher_price'];
        $orderData['buy_price'] = $data['voucher_num']*$orderData['voucher_price']-$couponsPrice;
        $orderData['coupons_price'] = $couponsPrice;
        $orderData['user_coupons_id'] = $data['user_coupons_id'];
        $orderData['order_sn'] = $data['order_sn'];
        $orderData['order_type'] = $voucherData['voucher_type'];
        $orderData['order_state'] = $orderData['voucher_type']===1? 'T01':'T02';
        $orderData['create_time'] = $data['create_time'];
        //-- 创建订单
        Db::startTrans();
        if(!Db::table('new_store_order')->insert($orderData)){
            Db::rollback();
            return false;
        }
        $orderId = Db::getLastInsID();
        //-- 创建用户抵用券
        for ($i=0;$i<$data['voucher_num'];$i++){
            $userDate = $voucherData;
            $userDate['order_id'] = $orderId;
            $userDate['order_sn'] = $data['order_sn'];
            $userDate['user_id'] = $data['user_id'];
            $userDate['voucher_sn'] = $this->getVoucherSn();
            $userDate['create_time'] = $data['create_time'];
            $userDate['used_state'] = $orderData['voucher_type']===1? 'C01':'C02';
            if(!Db::table('new_user_voucher')->insert($userDate)){
                Db::rollback();
                return false;
            }
        }
        //-- 减库存
        if(!Db::table('new_store_voucher')->where('voucher_id',$data['voucher_id'])->setDec('voucher_stock',$data['voucher_num'])){
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }
    
    /*
     * explain:确定订单是否已经下单成功
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/25 10:50
     */
    protected function checkOrder($orderSn){
        $order = Db::table('new_store_order')->where('order_sn',$orderSn)->find();
        if(count($order)>0)
            return true;
        return false;
    }

}