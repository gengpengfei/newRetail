<?php
namespace app\shopapi\controller;

use app\shopapi\model\StoreClearModel;
use app\shopapi\model\StoreModel;
use app\shopapi\model\StoreOrderModel;
use app\shopapi\model\StoreOrderPayModel;
use app\shopapi\model\StoreRebateLogModel;
use app\shopapi\model\StoreRebateRuleModel;
use app\shopapi\model\StoreVoucherModel;
use app\shopapi\model\UserCouponsModel;
use app\shopapi\model\UserMoneyLogModel;
use app\shopapi\model\UserScoreLogModel;
use app\shopapi\model\UsersModel;
use app\shopapi\model\UserVoucherModel;
use app\shopapi\model\UserVoucherRefundModel;
use app\shopapi\service\PayService;
use app\shop\model\StoreAuditModel;
use think\Queue;
use think\Request;

class Pay extends Common {
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\GetConfig;

    public function test(PayService $payService)
    {
        //-- 微信支付
        $config = [
            'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatOrderNotify'
        ];
        $order = [
            'out_trade_no' => $this->paySn(),
            'total_fee' => '1', // **单位：分**
            'body' => 'test body - 测试',
        ];
        $wechat = $payService->setConfig($config)->wechatApp($order);
        var_dump($wechat);die;
    }
    /*
     * explain:退款提交
     * authors:Mr.Geng
     * addTime:2017/11/16 14:34
     */
    public function refundSub(Request $request,StoreOrderModel $storeOrderModel,StoreClearModel $storeClearModel,UsersModel $usersModel,UserMoneyLogModel $userMoneyLogModel,PayService $payService,UserScoreLogModel $userScoreLogModel)
    {
        $param = $request->param();
        $refundPrice = $param['refund_price'];
        $storeClear = $storeClearModel->where(['order_sn'=>$param['order_sn'],'store_id'=>$param['store_id']])->find();
        if(empty($orderSn)) $this->jkReturn('-1','网络延时请稍后重试',[]);
        //-- 订单和支付详情
        $orderInfo = $storeOrderModel
            ->alias('o')
            ->field('o.order_sn,o.refund_price,o.buy_price,o.pay_type,o.user_give_score,o.voucher_num,o.store_give_score,p.pay_sn,p.total_fee')
            ->where(['o.order_sn'=>$storeClear->order_sn,'p.pay_state'=>1])
            ->join('new_store_order_pay p','p.order_id=o.order_id','left')
            ->find();
        //-- 判断订单金额
        if($refundPrice>($orderInfo->buy_price-$orderInfo->refund_price)){
            $this->jkReturn('-1','退款金额不能大于支付金额',[]);
        }
        //-- 判断店铺剩余结算金额
        $totalPrice = $storeClearModel->where(['store_id'=>$param['store_id'],'clear_state'=>0])->sum('order_price');
        if($refundPrice>$totalPrice){
            $this->jkReturn('-1','退款金额不能大于待结算金额',[]);
        }
        $storeOrderModel->startTrans();
        switch ($orderInfo->pay_type){
            case 0:
                //-- 余额支付
                if(!$usersModel->where('user_id',$orderInfo->user_id)->setInc('user_money',$refundPrice)){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                //-- 记录用户账户变动日志
                if (!$userMoneyLogModel->create(['money'=>$refundPrice,'type'=>2,'desc'=>'订单退款:'.$orderInfo->order_sn,'user_id'=>$orderInfo->user_id])){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                break;
            case 1:
                //-- 支付宝支付
                $order = [
                    'out_trade_no' => $orderInfo->pay_sn,
                    'total_fee' => $orderInfo->total_fee,
                    'refund_fee' => $refundPrice,
                    'refund_desc' => '新零售-订单退款:'.$orderInfo->order_sn,
                    'type' => 'app'
                ];
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/shopapi/pay/alipayRefundNotify'
                ];
                $payRefund = $payService->setConfig($config)->alipayRefund($order);
                if($payRefund->result_code != 'SUCCESS'){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                break;
            case 2:
                //-- 微信支付
                $order = [
                    'out_trade_no' => $orderInfo->pay_sn,
                    'total_fee' => $orderInfo->total_fee*100,
                    'refund_fee' => $refundPrice*100,
                    'refund_desc' => '新零售-订单退款:'.$orderInfo->order_sn,
                    'type' => 'app'
                ];
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/shopapi/pay/wechatRefundNotify'
                ];
                $payRefund = $payService->setConfig($config)->wechatRefund($order);
                if($payRefund->result_code != 'SUCCESS'){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                break;
        }
        //-- 订单退款金额
        if(!$storeOrderModel->where(['order_id'=>$orderInfo->order_id])->setInc('refund_price',$refundPrice)){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 判断是否改变订单状态
        if($refundPrice == ($orderInfo->buy_price-$orderInfo->refund_price)){
            if(!$storeOrderModel->update(['order_state'=>'T04'],['order_id'=>$orderInfo->order_id])){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        //-- 如果用户有赠送积分,扣除用户积分
        if($orderInfo->user_give_score>0){
            $delScore = ceil($orderInfo->user_give_score*($refundPrice/$orderInfo->total_fee));
            if($orderInfo->user_give_score-$delScore<0){
                $delScore = $orderInfo->user_give_score;
            }
            //-- 更新订单增送积分
            if(!$storeOrderModel->where(['order_id'=>$orderInfo->order_id])->setDec('user_give_score',$delScore)){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 扣除积分并记录日志
            if (!$usersModel->where('user_id',$orderInfo->user_id)->setDec('user_score',$delScore)) {
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 记录日志
            if (!$userScoreLogModel->save(['score'=>-$delScore,'desc'=>'订单退款,订单:'.$orderInfo->order_sn,'user_id'=>$orderInfo->user_id])) {
                $storeOrderModel->rollback();
                $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
            }
        }
        //-- 添加退款结算记录
        $discount_price = sprintf('%.2f',($orderInfo->coupons_price/$orderInfo->buy_price)*$refundPrice);
        $clearData = [
            'order_id'=>$orderInfo->order_id,
            'order_sn'=>$orderInfo->order_sn,
            'user_id'=>$orderInfo->user_id,
            'order_type'=>$orderInfo->order_type,
            'store_id'=>$orderInfo->store_id,
            'pay_type'=>$orderInfo->pay_type,
            'user_voucher_id'=>0,
            'order_price'=>-$refundPrice,
            'user_voucher_price'=>0,
            'discount_price'=>-$discount_price,
            'clear_price'=>-($refundPrice+$discount_price),
            'clear_desc'=>'支付订单退款:'.$orderInfo->order_sn,
            'clear_state'=>0
        ];
        if(!$storeClearModel->save($clearData)){
            $storeOrderModel->rollback();
            return false;
        }
        $storeOrderModel->commit();
        $this->jkReturn('1','退款成功',[]);
    }
    /*
     * explain:订单退款支付宝回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/14 14:26
     */
    public function alipayRefundNotify(PayService $payService,UserVoucherRefundModel $userVoucherRefundModel)
    {
        if ($data = $payService->alipayVerify()) {
            //-- 检验 app_id 来源
            if ($data->appid == $this->getConfig('alipay_app_id')) {
                //-- 检验退款状态
                if ($data->refund_status == 'SUCCESS' || $data->refund_status == 'REFUNDCLOSE') {
                    $payService->alipaySuccess();
                }
            }
        }
    }

    /*
     * explain:订单退款微信回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/14 14:26
     */
    public function wechatRefundNotify(PayService $payService,UserVoucherRefundModel $userVoucherRefundModel)
    {
        if ($data = $payService->wechatVerify()) {
            //-- 检验 app_id 来源
            if ($data->appid == $this->getConfig('weixin_app_id') && $data->mch_id == $this->getConfig('weixin_merch_id')) {
                //-- 检验退款状态
                if ($data->refund_status == 'SUCCESS' || $data->refund_status == 'REFUNDCLOSE') {
                    $payService->wechatSuccess();
                }
            }
        }
    }

}