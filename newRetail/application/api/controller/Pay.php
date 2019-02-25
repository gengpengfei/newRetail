<?php
namespace app\api\controller;

use app\api\model\StoreClearModel;
use app\api\model\StoreModel;
use app\api\model\StoreOrderModel;
use app\api\model\StoreOrderPayModel;
use app\api\model\StorePushMessageModel;
use app\api\model\StoreRebateLogModel;
use app\api\model\StoreRebateRuleModel;
use app\api\model\StoreUserModel;
use app\api\model\StoreVoucherModel;
use app\api\model\UserCouponsModel;
use app\api\model\UserMoneyLogModel;
use app\api\model\UserRechargeModel;
use app\api\model\UserRechargePayModel;
use app\api\model\UsersModel;
use app\api\model\UserVoucherModel;
use app\api\model\UserVoucherRefundModel;
use app\api\service\ClientService;
use app\api\service\PayService;
use app\api\service\RewardService;
use app\shop\model\StoreAuditModel;
use think\Log;
use think\Queue;
use think\Request;

class Pay extends Common {
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;

    public function test(PayService $payService)
    {
        //-- 微信支付
        $config = [
            'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatOrderNotify'
        ];
        $order = [
            'out_trade_no' =>'123',
            'body' => '新零售-订单支付',
            'total_fee' => '1',//--单位是分
        ];
        $wechat = $payService->setConfig($config)->alipayApp($order);
        $this->jkReturn('1','支付预下单成功',$wechat);
    }
    /*
     * explain:店铺订单支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 14:47
     */
    public function payOrder(Request $request,StoreOrderModel $storeOrderModel,PayService $payService,StoreOrderPayModel $storeOrderPayModel)
    {
        $param = $request->param();
        //-- 支付金额判断
        $orderInfo = $storeOrderModel->where('order_id',$param['order_id'])->find();
        $orderInfo->order_state != 'T01' && $this->jkReturn('-1','该订单已支付,请刷新重试',[]);
        if($orderInfo->buy_price !== $param['pay_price'] || $orderInfo->buy_price == '0.00'){
            $this->jkReturn('-1','支付金额有误,请刷新重试',[]);
        }
        $data = $param;
        $data['pay_sn'] = $this->paySn();
        if(!$storeOrderPayModel->allowField(true)->create($data)){
            $this->jkReturn('-1','网络延时,请刷新重试',[]);
        }
        switch($param['pay_type']){
            case 0 :
                //-- 余额支付
                $this->jkReturn('1','支付预下单成功',$data['pay_sn']);
                break;
            case 1:
                //-- 支付宝支付
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/Api/pay/alipayOrderNotify'
                ];
                $order = [
                    'out_trade_no' => $data['pay_sn'],
                    'body' => '新零售-订单支付',
                    'total_fee' => $param['pay_price'],
                ];
                $alipay = $payService->setConfig($config)->alipayApp($order);
                $this->jkReturn('1','支付预下单成功',$alipay);
                break;
            case 2:
                //-- 微信支付
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatOrderNotify'
                ];
                $order = [
                    'out_trade_no' =>$data['pay_sn'],
                    'body' => '新零售-订单支付',
                    'total_fee' => $param['pay_price']*100,//--单位是分
                ];
                $wechat = $payService->setConfig($config)->wechatApp($order);
                $this->jkReturn('1','支付预下单成功',$wechat);
                break;
            default:
                $this->jkReturn('-1','支付预下单失败',[]);
                break;
        }
    }

    /*
     * explain:店铺订单余额支付回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 17:39
     */
    public function moneyOrderNotify(Request $request, UsersModel $usersModel,StoreOrderPayModel $storeOrderPayModel,UserMoneyLogModel $userMoneyLogModel,StoreOrderModel $storeOrderModel , UserVoucherModel $userVoucherModel,StoreVoucherModel $storeVoucherModel,UserCouponsModel $userCouponsModel,StoreClearModel $storeClearModel)
    {
        $param = $request->param();
        $userInfo = $usersModel->where('user_id',$param['user_id'])->find();
        if(empty($userInfo)|| empty($userInfo->pay_password) || $userInfo->pay_password != $param['pay_password']){
            $this->jkReturn('-1','支付密码错误',[]);
        }
        $payInfo = $storeOrderPayModel->where(['pay_sn'=>$param['pay_sn']])->find();
        empty($payInfo) && $this->jkReturn('-1','网络延时,请稍后重试',[]);
        if($payInfo->pay_state == 1){
            $this->jkReturn('-1','该订单已经支付,请刷新重试',[]);
        }
        $orderInfo = $storeOrderModel->where('order_id',$payInfo->order_id)->find();
        if($orderInfo->order_state != 'T01'){
            $this->jkReturn('-1','该订单已经支付,请刷新重试',[]);
        }
        //-- 开启事物
        $storeOrderPayModel->startTrans();
        //-- 扣除用户余额
        if($userInfo->user_money<$payInfo['pay_price']){
            $storeOrderPayModel->rollback();
            $this->jkReturn('-1','您的余额不足',[]);
        }
        if(!$usersModel->where('user_id',$payInfo->user_id)->setDec('user_money',$payInfo['pay_price'])){
            $storeOrderPayModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试1',[]);
        }
        //-- 记录用户账户变动日志
        if (!$userMoneyLogModel->create(['money'=>-$payInfo['pay_price'],'type'=>1,'desc'=>'订单支付:'.$param['pay_sn'],'user_id'=>$payInfo->user_id])){
            $storeOrderPayModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试2',[]);
        }
        //-- 支付成功之后的通用逻辑处理
        $param['out_trade_no'] = $param['pay_sn'];
        $param = json_decode(json_encode($param));
        if(!$this->orderSuccess($param,$storeOrderPayModel,$storeOrderModel,$usersModel,$storeVoucherModel,$userCouponsModel,$userVoucherModel,$storeClearModel)){
            $storeOrderPayModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试3',[]);
        }

        $storeOrderPayModel->commit();
        $this->jkReturn('1','支付成功',$payInfo['order_id']);
    }

    /*
     * explain:店铺订单支付宝支付回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 17:39
     */
    public function alipayOrderNotify(UsersModel $usersModel,PayService $payService,StoreOrderPayModel $storeOrderPayModel,StoreOrderModel $storeOrderModel , UserVoucherModel $userVoucherModel,StoreVoucherModel $storeVoucherModel,UserCouponsModel $userCouponsModel,StoreClearModel $storeClearModel)
    {
        //验签就这么简单！
        if($param = $payService->alipayVerify()){
            //-- 验证商户来源
            $appId = $this->getConfig('alipay_app_id');
            if($param->app_id != $appId){
                return false;
            }
            if($param->trade_status == 'TRADE_SUCCESS'|| $param->trade_status == 'TRADE_FINISHED'){
                $payInfo = $storeOrderPayModel->where('pay_sn',$param->out_trade_no)->find();
                //-- 订单已经支付
                if (!empty($payInfo) && $payInfo->pay_state == 1) {
                    $orderInfo = $storeOrderModel->where('order_id', $payInfo->order_id)->find();
                    if ($orderInfo->order_state != 'T01') {
                        $payService->alipaySuccess();
                        exit;
                    }
                }
                if(!empty($payInfo) && $payInfo->pay_state == 0 && $payInfo->pay_type == 1){
                    if ($payInfo->pay_price == $param->total_fee) {
                        //-- 开启事物
                        $storeOrderPayModel->startTrans();
                        if (!$this->orderSuccess($param, $storeOrderPayModel, $storeOrderModel, $usersModel, $storeVoucherModel, $userCouponsModel, $userVoucherModel,$storeClearModel)) {
                            $storeOrderPayModel->rollback();
                            return false;
                        }
                        $storeOrderPayModel->commit();
                        $payService->alipaySuccess();
                        exit;
                    }
                }
            }
        }
    }

    /*
     * explain:店铺订单微信支付回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 17:39
     */
    public function wechatOrderNotify(UsersModel $usersModel, PayService $payService, StoreOrderPayModel $storeOrderPayModel, StoreOrderModel $storeOrderModel, UserVoucherModel $userVoucherModel, StoreVoucherModel $storeVoucherModel, UserCouponsModel $userCouponsModel,StoreClearModel $storeClearModel)
    {
        if ($data = $payService->wechatVerify()) {
//            $data = '{a:16:{s:5:"appid";s:18:"wx78049fb712bb08c6";s:9:"bank_type";s:3:"CFT";s:8:"cash_fee";s:1:"1";s:8:"fee_type";s:3:"CNY";s:12:"is_subscribe";s:1:"N";s:6:"mch_id";s:10:"1504996321";s:9:"nonce_str";s:16:"LDFlqWTcL4DqrTi2";s:6:"openid";s:28:"oOrKo1dqRLk0ULf1bNwJX0cuRqQE";s:12:"out_trade_no";s:19:"2018053012542612464";s:11:"result_code";s:7:"SUCCESS";s:11:"return_code";s:7:"SUCCESS";s:4:"sign";s:32:"29A6BA805FCDA1D2003F2752339B2D84";s:8:"time_end";s:14:"20180530125433";s:9:"total_fee";s:1:"1";s:10:"trade_type";s:3:"APP";s:14:"transaction_id";s:28:"4200000150201805301426965857";}}';
//            $data = [
//                "appid"=>"wx78049fb712bb08c6",
//                "mch_id"=>"1504996321",
//                "out_trade_no"=>"2018053015342423145",
//                "result_code"=>"SUCCESS",
//                "return_code"=>"SUCCESS",
//                "total_fee"=>"1"
//            ];
//            $data = json_decode(json_encode($data));
            //-- 检验 app_id 来源
            if ($data->appid == $this->getConfig('weixin_app_id') && $data->mch_id == $this->getConfig('weixin_merch_id')) {
                //-- 检验支付状态
                if ($data->result_code == 'SUCCESS' || $data->result_code == 'FINISHED') {
                    $payInfo = $storeOrderPayModel->where('pay_sn', $data->out_trade_no)->find();
                    //-- 订单已经支付
                    if (!empty($payInfo) && $payInfo->pay_state == 1) {
                        $orderInfo = $storeOrderModel->where('order_id', $payInfo->order_id)->find();
                        if ($orderInfo->order_state != 'T01') {
                            $payService->wechatSuccess();
                            exit;
                        }
                    }
                    if(!empty($payInfo) && $payInfo->pay_state == 0 && $payInfo->pay_type == 2){
                        if ($payInfo->pay_price*100 == $data->total_fee) {
                            //-- 开启事物
                            $storeOrderPayModel->startTrans();
                            if (!$this->orderSuccess($data, $storeOrderPayModel, $storeOrderModel, $usersModel, $storeVoucherModel, $userCouponsModel, $userVoucherModel,$storeClearModel)) {
                                $storeOrderPayModel->rollback();
                                return false;
                            }

                            $storeOrderPayModel->commit();
                            $payService->wechatSuccess();
                            exit;
                        }
                    }
                }
            }
        }
    }

    /*
     * explain:订单支付成功之后的逻辑处理
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/21 10:14
     */
    public function orderSuccess($param,$storeOrderPayModel,$storeOrderModel,$usersModel,$storeVoucherModel,$userCouponsModel,$userVoucherModel,$storeClearModel)
    {
        $payInfo = $storeOrderPayModel->where(['pay_sn'=>$param->out_trade_no])->find();
        $orderInfo = $storeOrderModel->where('order_id',$payInfo->order_id)->find();
        //-- 改变预下单支付状态
        $payTime = $this->getTime();
        $data = [
            'pay_state'=>1,
            'pay_time'=>$payTime,
            'total_fee'=>$payInfo->pay_price,
            'pay_info'=>serialize($param),
        ];
        if(!$storeOrderPayModel->update($data,['pay_sn'=>$param->out_trade_no])){
            $storeOrderPayModel->rollback();
            return false;
        }

        //-- 店铺赠送积分计算
        $storeModel = new StoreModel();
        $storeRebateRuleModel = new StoreRebateRuleModel();
        $storeRebateList = $storeRebateRuleModel->where('disabled=1')->select();
        $storeScore = 0;
        if(!$storeRebateList->isEmpty()){
            $storeInfo = $storeModel->where("store_id=$orderInfo->store_id")->find();
            foreach ($storeRebateList AS $item){
                $storeScore += $this->storeScoreRule($item,$storeInfo,$orderInfo);
            }
        }
        //-- 记录店铺积分返利日志
        $storeRebateLog = new StoreRebateLogModel();
        if(!$storeRebateLog->create(['store_id'=>$orderInfo->store_id,'score'=>$storeScore,'desc'=>'线上下单:'.$orderInfo->order_sn])){
            $storeOrderPayModel->rollback();
            return false;
        }

        //-- 店铺赠送积分
        if($storeScore>0){
            if(!$storeModel->where(['store_id'=>$orderInfo->store_id])->setInc('store_score',$storeScore)){
                $storeOrderPayModel->rollback();
                return false;
            }
        }
        //--  记录用户省了多少钱
        $save_money = $orderInfo->user_voucher_price+$orderInfo->coupons_price;
        if($save_money>0){
            if(!$usersModel->where('user_id',$payInfo->user_id)->setInc('user_save_money',$save_money)){
                $storeOrderPayModel->rollback();
                return false;
            }
        }
        //-- 线上下单
        if($orderInfo->order_type == 0){
            //-- 改变用户抵用券状态
            if(!$userVoucherModel->update(['used_state'=>'C02'],['order_id'=>$payInfo['order_id']])){
                $storeOrderPayModel->rollback();
                return false;
            }
            //-- 增加销售量
            if(!$storeVoucherModel->where('voucher_id',$orderInfo->voucher_id)->setInc('sell_num',$orderInfo->voucher_num)){
                $storeOrderPayModel->rollback();
                return false;
            }
            //-- 更新订单状态
            if(!$storeOrderModel->update(['pay_type'=>$payInfo->pay_type,'pay_time'=>$payTime,'order_state'=>'T02','store_give_score'=>$storeScore],['order_id'=>$payInfo['order_id']])){
                $storeOrderPayModel->rollback();
                return false;
            }
            //-- 执行用户下单行为
            $data = [
                'user_id'=>$payInfo->user_id,
                'code'=>'orderUpLine',
                'order_id'=>$payInfo->order_id,
                'store_id'=>$orderInfo->store_id
            ];
            $request = Queue::push('app\job\ExecBehavior', serialize($data) , $queue = "ExecBehavior");
            if (!$request){
                $storeOrderPayModel->rollback();
                return false;
            }
        }else{
            //-- 线下下单支付
            //-- 改变订单状态
            if(!$storeOrderModel->update(['pay_type'=>$payInfo->pay_type,'pay_time'=>$payTime,'order_state'=>'T03','store_give_score'=>$storeScore],['order_id'=>$payInfo['order_id']])){
                $storeOrderPayModel->rollback();
                return false;
            }
            //-- 使用优惠券
            if($orderInfo->user_coupons_id>0){
                $data = [
                    'used_state'=>'C03',
                    'used_time'=>$this->getTime()
                ];
                if(!$userCouponsModel->update($data,['user_coupons_id'=>$orderInfo->user_coupons_id])){
                    $storeOrderPayModel->rollback();
                    return false;
                }
            }
            //-- 使用店铺优惠券
            if($orderInfo->user_voucher_id>0){
                $data = [
                    'used_state'=>'C03',
                    'used_time'=>$this->getTime()
                ];
                if(!$userVoucherModel->update($data,['user_voucher_id'=>$orderInfo->user_voucher_id])){
                    $storeOrderPayModel->rollback();
                    return false;
                }
                //-- 查询使用的优惠券详情
                $userVoucherInfo = $userVoucherModel->where(['user_voucher_id'=>$orderInfo->user_voucher_id])->find();
                //-- 查询该优惠券未使用数量
                $userVouherCount = $userVoucherModel->where(['order_id'=>$userVoucherInfo->order_id,'used_state'=>'C02'])->count();
                if($userVouherCount==0){
                    //-- 更新订单状态
                    if(!$storeOrderModel->update(['order_state'=>'T03'],['order_id'=>$userVoucherInfo->order_id])){
                        $storeOrderPayModel->rollback();
                        return false;
                    }
                }
                //-- 增加优惠券使用数量
                if(!$storeVoucherModel->where(['voucher_id'=>$userVoucherInfo->voucher_id])->setDec('used_num',1)){
                    $storeOrderPayModel->rollback();
                    return false;
                }
            }
            //-- 添加店铺结算表
            $clearData = [
                'order_id'=>$orderInfo->order_id,
                'order_sn'=>$orderInfo->order_sn,
                'user_id'=>$orderInfo->user_id,
                'order_type'=>$orderInfo->order_type,
                'store_id'=>$orderInfo->store_id,
                'pay_type'=>$payInfo->pay_type,
                'user_voucher_id'=>$orderInfo->user_voucher_id,
                'order_price'=>$orderInfo->order_price,
                'user_voucher_price'=>$orderInfo->user_voucher_price,
                'discount_price'=>$orderInfo->coupons_price,
                'clear_price'=>$orderInfo->order_price-$orderInfo->user_voucher_price,
                'clear_desc'=>'线下支付:'.$orderInfo->order_sn,
                'clear_state'=>0
            ];
            if(!$storeClearModel->save($clearData)){
                $storeOrderPayModel->rollback();
                return false;
            }
            //-- 消息推送
            $userInfo = $usersModel->where(['user_id'=>$orderInfo->user_id])->find();
            $storeUserModel = new StoreUserModel();
            $storeAdmin = $storeUserModel->where(['store_id'=>$orderInfo->store_id])->find();
            $info = [
                'user_name'=>$userInfo->user_name ,
                'head_img'=>$userInfo->head_img,
                'clear_price'=>$clearData['clear_price'],
                'clear_time'=>$this->getTime(),
                'clear_desc'=>$clearData['clear_desc'],
                'order_sn'=>$clearData['order_sn'],
                'pay_type'=>$payInfo->pay_type,
                'admin_name'=>$storeAdmin->user_name,
                'mobile'=>$storeAdmin->mobile
            ];
            $data = [
                'store_id'=>$orderInfo->store_id,
                'message_type'=>1,
                'message_cont'=>"已收到*".substr($userInfo->user_name,-1).'的付款,到账金额'.$clearData['clear_price']."元",
                'message_data'=>json_encode($info,JSON_UNESCAPED_UNICODE)
            ];
            $storePushMessageModel = new StorePushMessageModel();
            if(!$storePushMessageModel->allowField(true)->save($data)){
                $storeOrderPayModel->rollback();
                return false;
            }
            $clientService = new ClientService();
            $receiver['alias'] = array('store'.$orderInfo->store_id);//接收者
            $data['id'] = $storePushMessageModel->getLastInsID();
            $data['create_time'] = $this->getTime();
            $clientService->push($data['message_cont'],$receiver,'收款消息',json_encode($data));

            $rewardService = new RewardService();
            $rewardService->giveUserReward($orderInfo->user_id,$orderInfo->buy_price);

            //-- 执行用户下单行为
            $data = [
                'user_id'=>$orderInfo->user_id,
                'code'=>'orderDownLine',
                'order_id'=>$orderInfo->order_id,
                'store_id'=>$orderInfo->store_id
            ];
            $request = Queue::push('app\job\ExecBehavior', serialize($data) , $queue = "ExecBehavior");
            if (!$request){
                $storeOrderPayModel->rollback();
                return false;
            }
        }
        return true;
    }

    /*
     * explain:充值支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 14:48
     */
    public function payRecharge(Request $request,PayService $payService,UserRechargeModel $userRechargeModel,UserRechargePayModel $userRechargePayModel)
    {
        $param = $request->param();
        //-- 支付金额判断
        $orderInfo = $userRechargeModel->where('recharge_id',$param['recharge_id'])->find();
        $orderInfo->pay_state == 1 && $this->jkReturn('-1','该订单已支付,请刷新重试',[]);
        if($orderInfo->recharge_price !== $param['pay_price'] || $orderInfo->recharge_price == '0.00'){
            $this->jkReturn('-1','支付金额有误,请刷新重试',[]);
        }
        $data = $param;
        $data['pay_sn'] = $this->paySn();
        //-- 充值预下单
        if(!$userRechargePayModel->allowField(true)->create($data)){
            $this->jkReturn('-1','网络延时,请刷新重试',[]);
        }
        switch($param['pay_type']){
            case 1:
                //-- 支付宝支付
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/Api/pay/alipayRechargeNotify'
                ];
                $order = [
                    'out_trade_no' => $data['pay_sn'],
                    'body' => '新零售-充值支付',
                    'total_fee' => $param['pay_price'],
                ];
                $payInfo = $payService->setConfig($config)->alipayApp($order);
                break;
            case 2:
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatRechargeNotify'
                ];
                $order = [
                    'out_trade_no' => $data['pay_sn'],
                    'body' => '新零售-充值支付',
                    'total_fee' =>$param['pay_price']*100,// 单位分
                ];
                //-- 微信支付
                $payInfo = $payService->setConfig($config)->wechatApp($order);
                break;
            default:
                $this->jkReturn('-1','参数错误',[]);
                break;
        }
        $this->jkReturn('1','充值预下单成功',$payInfo);
    }

    /*
     * explain:充值订单支付宝支付回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 17:39
     */
    public function alipayRechargeNotify(PayService $payService,UserRechargeModel $userRechargeModel,UserRechargePayModel $userRechargePayModel)
    {
        if($data = $payService->alipayVerify()){
            //-- 检验 app_id 来源
            if($data->app_id == $this->getConfig('alipay_app_id')){
                if($data->trade_status == 'TRADE_SUCCESS'|| $data->trade_status == 'TRADE_FINISHED') {
                    $payInfo = $userRechargePayModel->where('pay_sn', $data->out_trade_no)->find();
                    if (!empty($payInfo) && $payInfo->pay_state == 0 && $payInfo->pay_type == 1) {
                        //-- 检验支付金额
                        if ($payInfo->pay_price == $data->total_fee) {
                            //-- 检验订单状态
                            $rechargeInfo = $userRechargeModel->where('recharge_id', $payInfo->recharge_id)->find();
                            if ($rechargeInfo->pay_type == 1 && $rechargeInfo->pay_state == 0) {
                                //-- 充值业务逻辑
                                if ($this->rechargeSuccess($data)) {
                                    $payService->alipaySuccess();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * explain:充值订单微信支付回调
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 17:39
     */
    public function wechatRechargeNotify(PayService $payService, UserRechargeModel $userRechargeModel, UserRechargePayModel $userRechargePayModel)
    {
        if ($data = $payService->wechatVerify()) {
//            $data = '{a:16:{s:5:"appid";s:18:"wx78049fb712bb08c6";s:9:"bank_type";s:3:"CFT";s:8:"cash_fee";s:1:"1";s:8:"fee_type";s:3:"CNY";s:12:"is_subscribe";s:1:"N";s:6:"mch_id";s:10:"1504996321";s:9:"nonce_str";s:16:"LDFlqWTcL4DqrTi2";s:6:"openid";s:28:"oOrKo1dqRLk0ULf1bNwJX0cuRqQE";s:12:"out_trade_no";s:19:"2018053012542612464";s:11:"result_code";s:7:"SUCCESS";s:11:"return_code";s:7:"SUCCESS";s:4:"sign";s:32:"29A6BA805FCDA1D2003F2752339B2D84";s:8:"time_end";s:14:"20180530125433";s:9:"total_fee";s:1:"1";s:10:"trade_type";s:3:"APP";s:14:"transaction_id";s:28:"4200000150201805301426965857";}}';
//            $data = [
//                "appid"=>"wx78049fb712bb08c6",
//                "mch_id"=>"1504996321",
//                "out_trade_no"=>"2018053012542612464",
//                "result_code"=>"SUCCESS",
//                "return_code"=>"SUCCESS",
//                "total_fee"=>"1"
//            ];
//            $data = json_decode(json_encode($data));
            //-- 检验 app_id 来源
            if ($data->appid == $this->getConfig('weixin_app_id') && $data->mch_id == $this->getConfig('weixin_merch_id')) {
                //-- 检验支付状态
                if ($data->result_code == 'SUCCESS' || $data->result_code == 'FINISHED') {
                    //-- 检验支付单号来源
                    $payInfo = $userRechargePayModel->where('pay_sn', $data->out_trade_no)->find();
                    if(!empty($payInfo) && $payInfo->pay_state == 1){
                        $payService->wechatSuccess();
                        exit;
                    }
                    if (!empty($payInfo) && $payInfo->pay_state == 0 && $payInfo->pay_type == 2) {
                        //-- 检验支付金额
                        if ($payInfo->pay_price*100 == $data->total_fee) {
                            //-- 检验订单状态
                            $rechargeInfo = $userRechargeModel->where('recharge_id', $payInfo->recharge_id)->find();
                            if (!empty($rechargeInfo) && $rechargeInfo->pay_state == 0) {
                                //-- 充值业务逻辑
                                if ($this->rechargeSuccess($data)) {
                                    $payService->wechatSuccess();
                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * explain:充值支付成功之后的业务逻辑
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/18 12:01
     */
    public function rechargeSuccess($data)
    {
        $userRechargePayModel = new UserRechargePayModel();
        //-- 开启事物
        $userRechargePayModel->startTrans();
        $payInfo = $userRechargePayModel->where('pay_sn',$data->out_trade_no)->find();
        //-- 更新充值预下单状态
        $time = $this->getTime();
        $param = $data;
        $param->pay_state = 1;
        $param->pay_info = serialize($data);
        $param->pay_time = $time;
        if(!$userRechargePayModel->update($param,['pay_sn'=>$data->out_trade_no])){
            $userRechargePayModel->rollback();
            return false;
        }
        //-- 更新充值订单状态
        $userRechargeModel = new UserRechargeModel();
        if(!$userRechargeModel->update(['pay_state'=>1,'pay_type'=>$payInfo->pay_type,'pay_time'=>$time],['recharge_id'=>$payInfo->recharge_id])){
            $userRechargePayModel->rollback();
            return false;
        }
        //-- 改变用户余额
        $rechargeInfo = $userRechargeModel->where('recharge_id',$payInfo->recharge_id)->find();
        $usersModel = new UsersModel();
        if(!$usersModel->where('user_id',$payInfo->user_id)->setInc('user_money',$rechargeInfo->recharge_amount)){
            $userRechargePayModel->rollback();
            return false;
        }
        //-- 记录用户账户变动日志
        $userMoneyLogModel = new UserMoneyLogModel();
        if (!$userMoneyLogModel->create(['money'=>$rechargeInfo->recharge_price,'type'=>0,'desc'=>'用户余额充值','user_id'=>$rechargeInfo->user_id])){
            $userRechargePayModel->rollback();
            return false;
        }
        $userRechargePayModel->commit();
        return true;
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
                    //-- 改变退款列表状态
                    if($userVoucherRefundModel->update(['refund_state'=>'D04',"refund_info"=>serialize($data)],['refund_no'=>$data->out_refund_no])){
                        $payService->alipaySuccess();
                    }
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
                    //-- 改变退款列表状态
                    if($userVoucherRefundModel->update(['refund_state'=>'D04',"refund_info"=>serialize($data)],['refund_no'=>$data->out_refund_no])){
                        $payService->wechatSuccess();
                    }
                }
            }
        }
    }

    /*
     * explain:店铺赠送积分规则判断
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 11:07
     */
    protected function storeScoreRule($action,$storeInfo,$orderInfo)
    {
        $score = 0;
        if($action->rule_range){
            switch ($action->rule_range){
                case 0:
                    if(in_array($storeInfo->nav_id ,explode(',',$action->rule_range_info))){
                        $score = $this->getScore($action,$orderInfo);
                    }
                    break;
                case 1:
                    if(in_array(end(explode(',',$storeInfo->category_id)),explode(',',$action->rule_range_info))){
                        $score = $this->getScore($action,$orderInfo);
                    }
                    break;
                case 2:
                    if($storeInfo->store_credit>=$action->rule_range_info){
                        $score = $this->getScore($action,$orderInfo);
                    }
                    break;
                case 3:
                    if(in_array($storeInfo->store_id ,explode(',',$action->rule_range_info))){
                        $score = $this->getScore($action,$orderInfo);
                    }
                    break;
                default:
                    break;
            }
        }
        return $score;
    }

    /*
     * explain:积分计算
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/24 16:35
     */
    protected function getScore($action,$orderInfo){
        switch($action->rule_type){
            case 0:
                return 0;
                break;
            case 1:
                return $action->rule_info;
                break;
            case 2:
                return sprintf('%.0f',$action->rule_info*$orderInfo->buy_price/100);
                break;
            default:
                return 0;
        }
    }
}