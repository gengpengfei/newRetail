<?php
namespace app\api\controller;

use app\admin\model\ShippingModel;
use app\api\model\OrderModel;
use app\api\model\OrderProModel;
use app\api\model\OrderRefundLogModel;
use app\api\model\OrderRefundModel;
use app\api\model\ProAttrModel;
use app\api\model\ProCartModel;
use app\api\model\ProCategoryModel;
use app\api\model\ProCommentModel;
use app\api\model\ProductModel;
use app\api\model\ProNavModel;
use app\api\model\ProScoreIntervalModel;
use app\api\model\RankModel;
use app\api\model\RefundReasonModel;
use app\api\model\UserAddressModel;
use app\api\model\UserScoreLogModel;
use app\api\model\UsersModel;
use app\api\service\LogisticsService;
use app\api\service\ShopService;
use app\api\service\UserService;
use think\Db;
use think\Request;

class Shop extends Common
{
    use \app\api\traits\GetConfig;
    use \app\api\traits\BuildParam;

    /*
     * explain:导航列表
     * params :null
     * authors:Mr.Geng
     * addTime:2018/4/2 15:38
     */
    public function shopNavList(ProNavModel $proNavModel)
    {
        $navList = $proNavModel->where(["disabled"=>1])->order('sort_order','desc')->select();
        $this->jkReturn(1,"积分商城导航列表",$navList);
    }
    
    /*
     * explain:获取分类下的子分类
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/12 14:51
     */
    public function proCategory(Request $request,ProCategoryModel $proCategoryModel)
    {
        $categoryId = $request->param('pro_category_id')??0;
        $categoryList = $proCategoryModel->where("parent_id=$categoryId and disabled=1 ")->order("sort_order","desc")->select();
        $this->jkReturn(1,"顶级分类列表",$categoryList);
    }
    
    /*
     * params :获取商品分类列表
     * explain:@pro_category_id
     * authors:Mr.Geng
     * addTime:2018/3/14 15:01
     */
    public function proCategoryList(Request $request,ProCategoryModel $proCategoryModel)
    {
        $param = $request->param();
        $categary_list = $proCategoryModel->getAllCategory(['pro_category_id'=>$param['pro_category_id']??0]);
        $this->jkReturn(1,"子分类列表",$categary_list);
    }

    /*
     * explain:推荐分类
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/4 14:58
     */
    public function recommendCategory(ProCategoryModel $proCategoryModel)
    {
        $list = $proCategoryModel->where('is_recommend','1')->select();
        $this->jkReturn(1,"推荐分类列表",$list);
    }
    
    /*
     * explain:推荐积分区间
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/4 14:58
     */
    public function scoreInterval(ProScoreIntervalModel $proScoreIntervalModel)
    {
        $list = $proScoreIntervalModel->where('disabled','1')->select();
        $this->jkReturn('1','推荐积分区间',$list);
    }
    
    /*
     * explain:推荐商品等级
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/4 14:59
     */
    public function recommendRank(Request $request,RankModel $rankModel)
    {
        $param = $request->param();
        if($param['is_recommend']??0){
            $list = $rankModel->where('is_recommend','1')->order('rank_num','ASC')->select();
        }else{
            $list = $rankModel->order('rank_num','ASC')->select();
        }
        $this->jkReturn(1,"推荐等级列表",$list);
    }
    
	/*
	 * explain:积分兑换列表
	 * params :@pro_score @rank_id @pro_category_id
	 * authors:Mr.Geng
	 * addTime:2018/4/2 18:38
	 */
	public function proList(Request $request,ProductModel $productModel,ProScoreIntervalModel $proScoreIntervalModel)
	{
	    $param = $request->param();
        $where = ' disabled=1 ';
        $order = [];
        if($param['pro_score']??0){
            $order['pro_score'] = $param['pro_score'];
        }
        if($param['rank_id']??0){
            $where .= ' and rank_id='.$param['rank_id'];
        }
        if($param['score_interval_id']??0){
            $info = $proScoreIntervalModel->where('score_interval_id',$param['score_interval_id'])->find();
            $where .= ' and pro_score>= '.$info->min_score.' and pro_score<='.$info->max_score ;
        }
        if($param['pro_category_id']){
            $where  .= " and find_in_set(".$param['pro_category_id'].",pro_category_id) ";
        }
        $product = $productModel
            ->where($where)
            ->limit($param["limit"]??30000)
            ->page($param['page']??1)
            ->order($order)
            ->select();
		$this->jkReturn(1,'积分兑换列表',$product);
	}

	/*
	 * explain:商品推荐
	 * params :null
	 * authors:Mr.Geng
	 * addTime:2018/4/4 11:10
	 */
    public function indexShow(ProductModel $productModel)
    {
        $list = $productModel
            ->where(['disabled'=>1,'is_push'=>1,'delete_time'=>null])
            ->select();
        $this->jkReturn(1,'商品推荐',$list);
	}
	
	/*
	 * explain:积分兑换详情页
	 * params :@product_id
	 * authors:Mr.Geng
	 * addTime:2018/4/3 17:04
	 */
	public function productInfo(Request $request,ProductModel $productModel,ProAttrModel $proAttrModel,RankModel $rankModel)
	{
        $param = $request->param();
        //-- 商品详情
        $product = $productModel->find($param['product_id']??0);
        if(empty($product)){
            $this->jkReturn(-1,"该商品不存在!",[]);
        }
        //-- 商品属性
        $proAttr = $proAttrModel
            ->alias('a')
            ->field('r.attr_name,a.attr_value')
            ->where(['a.product_id'=>$param['product_id']??0,'disabled'=>1])
            ->join('new_pro_attr_rule r','a.attr_rule_id=r.attr_rule_id','left')
            ->select();
        //-- 等级详情
        $rankInfo = $rankModel->where(['rank_id'=>$product->rank_id])->select();
        $data = [
            'product'=>$product,
            'pro_attr'=>$proAttr,
            'rank_info'=>$rankInfo
        ];
        $this->jkReturn(1,'商品详情',$data);
	}

	/*
	 * explain:商品评论列表
	 * params :@product_id @limit @page
	 * authors:Mr.Geng
	 * addTime:2018/4/3 17:58
	 */
	public function proComment(Request $request,ProCommentModel $proCommentModel)
	{
	    $param = $request->param();
		//--------商品评价数量统计
        $all = $proCommentModel->where(['product_id'=>$param['product_id']])->count();
        $com1 = $proCommentModel->where(['product_id'=>$param['product_id'],'pro_comment'=>1])->count();
        $com2 = $proCommentModel->where(['product_id'=>$param['product_id'],'pro_comment'=>2])->count();
        $com3 = $proCommentModel->where(['product_id'=>$param['product_id'],'pro_comment'=>3])->count();
        $com4 = $proCommentModel->where(['product_id'=>$param['product_id'],'pro_comment'=>4])->count();
        $com5 = $proCommentModel->where(['product_id'=>$param['product_id'],'pro_comment'=>5])->count();
        $img = $proCommentModel->where(['product_id'=>$param['product_id'],'has_img'=>1])->count();
        $where = ['product_id'=>$param['product_id']];
        if ($param['has_img']??0) $where['has_img'] = 1;
        if ($param['pro_comment']??0) $where['pro_comment'] = $param['pro_comment'];
        $comment = $proCommentModel
            ->where($where)
            ->limit($param['limit']??3)
            ->page($param['page']??1)
            ->order('create_time','desc')
            ->select();
		$data = [
			'pro_comment'=>$comment,//商品评价列表
			'all'=>$all,
			'com1'=>$com1,
			'com2'=>$com2,
            'com3'=>$com3,
			'com4'=>$com4,
			'com5'=>$com5,
            'has_img'=>$img
		];
		$this->jkReturn(1,'积分兑换商品评论',$data);
	}
    
	/*
	 * explain:商品加入购物车
	 * params :@user_id @product_id
	 * authors:Mr.Geng
	 * addTime:2018/4/4 16:07
	 */
    public function setCart(Request $request,ProCartModel $proCartModel,UserService $userService,ShopService $shopService,ProductModel $productModel)
    {
        $param = $request->param();
        //-- 用户详情判定
        $userService ->judgeUser();
        $product = $productModel->where(['product_id'=>$param['product_id']])->find();
        //-- 商品详情判定
        $res = $shopService->judgeGoods($product);
        if($res['msg']??0){
            $this->jkReturn(-1,$res['msg'],[]);
        }
        //-- 商品库存判断
        if($param['pro_num']>0){
            $res = $shopService->judgeStock($product);
            if($res['msg']??0){
                $this->jkReturn(1,$res['msg'],[]);
            }
            //-- 商品等级判断
            $res = $shopService->judgeRank($product);
            if($res['msg']??0){
                $this->jkReturn(-1,$res['msg'],[]);
            }
        }
        //-- 是否加入购物车
        if($cartInfo = $shopService->isCart())
        {
            $cartInfo->cart_num = $param['pro_num']+$cartInfo->cart_num;
            if($cartInfo->cart_num<=0){
                $proCartModel->where(['product_id'=>$param['product_id'],'user_id'=>$param['user_id']])->delete();
            }else{
                $cartInfo->save();
            }
        }
        else
        {
            $proCartModel
                ->data(['product_id'=>$param['product_id'],'user_id'=>$param['user_id'],'cart_num'=>$param['pro_num']])
                ->save();
        }
        $this->jkReturn(1,'添加购物车成功',[]);
    }

    /*
     * explain:移除购物车
     * params :@product_id @user_id
     * authors:Mr.Geng
     * addTime:2018/4/8 12:48
     */
    public function removeCart(Request $request,ProCartModel $proCartModel)
    {
        $param = $request->param();
        foreach ($param['product_id'] as $product_id){
            $proCartModel->where(['product_id'=>$product_id,'user_id'=>$param['user_id']])->delete();
        }
        $this->jkReturn(1,'已移除购物车',$param['product_id']);
    }
	
    /*
     * explain:购物车详情
     * params :@user_id
     * authors:Mr.Geng
     * addTime:2018/4/8 13:41
     */
    public function cartInfo(Request $request,ProCartModel $proCartModel,ShopService $shopService,UserService $userService)
    {
        $param = $request->param();
        //-- 用户详情判定
        $userService ->judgeUser();
        $cartPro = $proCartModel
            ->alias('c')
            ->field('p.*,c.cart_num,c.cart_id')
            ->join('new_product p','p.product_id=c.product_id','left')
            ->where('c.user_id',$param['user_id'])
            ->select();

        $msg = '';
        foreach ($cartPro as &$v){
            $v->is_check = true;
            //-- 商品详情判定
            $res = $shopService->judgeGoods($v);
            if($res['msg']??0){
                $v->is_check = false;
                $msg .= '<br/>'.$res['msg'];
            }
            //-- 商品等级判断
            $res = $shopService->judgeRank($v);
            if($res['msg']??0){
                $v->is_check = false;
                $msg .= '<br/>'.$res['msg'];
            }
            //-- 商品库存判断
            if($v->pro_stock<=0){
                $v->is_check = false;
                $v->cart_num = 0;
                $msg .= '<br/>'.'商品'.$v->pro_name.'库存不足';
                $proCartModel->save(['cart_num'=>0],['cart_id'=>$v->cart_id]);
            }
            if($v->pro_stock>0 && $v->pro_stock<$v->cart_num){
                $v->cart_num = $v->pro_stock;
                $msg .= '<br/>'.'商品'.$v->pro_name.'库存不足,您最多可以购买'.$v->pro_stock.'件,';
                $proCartModel->save(['cart_num'=>$v->pro_stock],['cart_id'=>$v->cart_id]);
            }
        }
        $data = [
            'msg'=>$msg,
            'cart_pro'=>$cartPro,
        ];
		$this->jkReturn(1,'购物车详情',$data);
    }

    /*
     * explain:订单详情页面
     * params :@user_id @cart_pro
     * authors:Mr.Geng
     * addTime:2018/4/8 14:50
     */
    public function checkOrder(Request $request,UserService $userService,ShopService $shopService,UserAddressModel $userAddressModel,ProductModel $productModel)
    {
        $param = $request->param();
        //-- 用户详情判定
        $userService ->judgeUser();
        $orderScore = 0;
        $cartOld = $param['cart_pro'];
        if(empty($cartOld)){
            $this->jkReturn(-1,'您未选择任何商品', []);
        }
        //-- 商品变动判定
        foreach($cartOld as $key=>$v)
        {
            $product = $productModel
                ->where('product_id',$v['product_id'])
                ->find();
            //-- 商品详情判定
            $res = $shopService->judgeGoods($product);
            if($res['msg']??0){
                $this->jkReturn(-1,$res['msg'],[]);
            }
            //-- 商品等级判断
            $res = $shopService->judgeRank($product);
            if($res['msg']??0){
                $this->jkReturn(-1,$res['msg'],[]);
            }
            //-- 商品库存判断
            if($product->pro_stock<=0){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'库存不足,请刷新重试',[]);
            }
            if($product->pro_stock>0 && $product->pro_stock<$v['cart_num']){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'库存不足,您最多可以购买'.$v['pro_stock'].'件,请刷新重试',[]);
            }
            //-- 商品积分判定
            if($product->pro_score != $v['pro_score']){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'购买的积分有变动,请刷新重试',[]);
            }
            $orderScore += $product->pro_score*$v['cart_num'];
        }
        //-- 获取默认地址
        $address = $userAddressModel->where('address_id',request()->user->address_id)->find();
        $data = [
            'address'=>$address,//用户地址
            'cart_pro'=>$cartOld,//商品详情
            'freight_score'=>0
        ];
        $this->jkReturn(1,'订单详情',$data);
    }

    /*
     * explain:创建订单
     * params :@user_id @address @cart_pro @order_score
     * authors:Mr.Geng
     * addTime:2018/4/9 11:51
     */
    public function createOrder(Request $request,UserService $userService,ProCartModel $proCartModel,ShopService $shopService,OrderModel $orderModel,OrderProModel $orderProModel,ProductModel $productModel,UserScoreLogModel $userScoreLogModel)
    {
        $param = $request->param();
        //-- 用户详情判定
        $userInfo= request()->user;
        $userService ->judgeUser();
        $orderScoreOld = $param['order_score'];
        $orderScore = 0;
        $cartOld = $param['cart_pro'];
        //-- 商品变动判定
        foreach($cartOld as $key=>$v)
        {
            $product = $productModel
                ->where('product_id',$v['product_id'])
                ->find();
            //-- 商品详情判定
            $res = $shopService->judgeGoods($product);
            if($res['msg']??0){
                $this->jkReturn(-1,$res['msg'],[]);
            }
            //-- 商品等级判断
            $res = $shopService->judgeRank($product);
            if($res['msg']??0){
                $this->jkReturn(-1,$res['msg'],[]);
            }
            //-- 商品库存判断
            if($product->pro_stock<=0){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'库存不足,请刷新重试',[]);
            }
            if($product->pro_stock>0 && $product->pro_stock<$v['cart_num']){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'库存不足,您最多可以购买'.$v['pro_stock'].'件,请刷新重试',[]);
            }
            //-- 商品积分判定
            if($product->pro_score != $v['pro_score']){
                $this->jkReturn(-1,'商品'.$v['pro_name'].'购买的积分有变动,请刷新重试',[]);
            }
            $orderScore += $product->pro_score*$v['cart_num'];
        }
        //-- 订单总积分判定
        if($orderScore != $orderScoreOld){
            $this->jkReturn(-1,'订单总积分有变动,请刷新重试',[]);
        }
        //-- 开启事物
        $orderModel->startTrans();
        //-- 清空购物车
        foreach ($cartOld as $v) {
            //-- 此判断用于区分是否是立即购买
            if (empty($v['type'])) {
                $res = $proCartModel->where(['product_id' => $v['product_id'], 'user_id' => $param['user_id']])->delete();
                if (!$res) {
                    $orderModel->rollback();
                    $this->jkReturn(-1, '网络延时,请稍后刷新重试,对您造成的不便敬请谅解6', []);
                }
            }
        }
        //-- 获取地址详情
        $addressInfo = $param['address']['address_name'].$param['address']['address'];
        //-- 生成订单
        $orderSn = $this->getOrderSn();
        $data = array(
            'order_sn' => $orderSn,
            'user_id' => $param['user_id'],
            'address_id' => $param['address']['address_id'],
            'order_score' => $orderScore,
            'buy_score' => $orderScore,
            'freight_score'=>$param['freight_score'],
            'pay_time' => $this->getTime(),
            'order_state' => 'Q02',
            'message' => $param['message'],
            'address_cont' => $addressInfo,
            'address_mobile' => $param['address']['mobile'],
            'user_name' => $param['address']['user_name']
        );
        if (!$orderModel->save($data)) {
            $orderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后刷新重试,对您造成的不便敬请谅解5',[]);
        }
        $orderId = $orderModel->getLastInsID();
        //-- 生成订单商品并更新库存
        foreach($cartOld as $v){
            $proData = [
                'order_sn'=>$orderSn,
                'order_id'=>$orderId,
                'user_id'=>$param['user_id'],
                'product_id'=>$v['product_id'],
                'pro_name'=>$v['pro_name'],
                'pro_desc'=>$v['pro_desc'],
                'pro_code'=>$v['pro_code'],
                'pro_img'=>serialize(array_splice($v['pro_img'],1)),
                'pro_num'=>$v['cart_num'],
                'pro_score'=>$v['pro_score']
            ];
            $orderPro = $orderProModel->create($proData,true);
            if(!$orderPro) {
                $orderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后刷新重试,对您造成的不便敬请谅解4',[]);
            }
            if(!Db::table('new_product')->where('product_id',$v['product_id'])->setDec('pro_stock',$v['cart_num'])){
                $orderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后刷新重试,对您造成的不便敬请谅解3',[]);
            }
        }
        //-- 扣除会员积分
        if ($userInfo['user_score'] < $orderScore) {
            $orderModel->rollback();
            $this->jkReturn('-1',"您的积分剩余{$userInfo['user_score']},不足以支付该订单",[]);
        }
        if (!Db::table('new_users')->where('user_id',$param['user_id'])->setDec('user_score',$orderScore)) {
            $orderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后刷新重试,对您造成的不便敬请谅解2',[]);
        }
        //-- 记录日志
        if (!$userScoreLogModel->save(['score'=>-$orderScore,'desc'=>'兑换商品:'.$orderSn,'user_id'=>$userInfo->user_id])) {
            $orderModel->rollback();
            $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
        }
        $result = [
            'order_id'=>$orderId,
        ];
        $orderModel->commit();
        $this->jkReturn(1,'您的订单下单成功了',$result);
    }

	/*
	 * explain:订单列表
	 * params :@user_id @pay_state @order_state @refund_state @comment_state @page @limit
	 * authors:Mr.Geng
	 * addTime:2018/4/9 11:55
	 */
    public function orderList(Request $request,OrderModel $orderModel,OrderProModel $orderProModel)
    {
        $param = $request->param();
        $where = 'user_id='.$param['user_id'];
        if ($param['order_state']??0){
            $where .= ' and order_state="'.$param['order_state'].'"';
        }
        $orderList = $orderModel
            ->where($where)
            ->limit($param['limit']??300)
            ->page($param['page']??1)
            ->order('create_time','desc')
            ->select();
        foreach ($orderList as &$v){
            $v->order_pro = $orderProModel->where(['order_id'=>$v['order_id']])->select();
            $v->pro_num = $orderProModel->where('order_sn',$v->order_sn)->sum('pro_num');
        }
        $this->jkReturn(1,'订单列表',$orderList);
    }

    /*
     * explain:订单详情
     * params :@order_id @order_sn @user_id
     * authors:Mr.Geng
     * addTime:2018/4/9 12:01
     */
    public function orderInfo(Request $request ,OrderModel $orderModel,OrderProModel $orderProModel)
    {
        $param = $request->param();
        $orderInfo = $orderModel
            ->where(['user_id'=>$param['user_id'],'order_id'=>$param['order_id']??0])
            ->find();
        if(!empty($orderInfo)){
            $orderProList = $orderProModel->append('refund_state')->where("order_id=".$param['order_id']??0)->select();
        }
        //-- Q01 待付款;Q02 待发货;Q03	待收货;Q04 已完成;Q05 退款关闭;Q06 取消关闭
        switch ($orderInfo->order_state){
            case 'Q01':
                $times = $this->getConfig('order_auto_close');
                $autoClose = $this->buffTime($this->getTime(),$this->getTimeMinuteX($times,$orderInfo->create_time));
                $orderTitle = '待付款';
                $orderCont = '还剩'.$autoClose.'自动关闭';
                break;
            case 'Q02':
                $orderTitle = '等待卖家发货';
                $orderCont = '卖家会及时发货';
                break;
            case 'Q03':
                $times = $this->getConfig('order_auto_confirma');
                $autoConfirma = $this->buffTime($this->getTime(),$this->getTimeX($times,$orderInfo->shipping_time));
                $orderTitle = '卖家已发货';
                $orderCont = '还剩'.$autoConfirma.'后自动确认收货';
                break;
            case 'Q04':
                $orderTitle = '交易成功';
                $orderCont = '感谢您的信任';
                break;
            case 'Q05':
                $orderTitle = '退款关闭';
                $orderCont = '';
                break;
            case 'Q06':
                $orderTitle = '交易关闭';
                $orderCont = '';
                break;
        }
        $data = array(
            'order_info'=>$orderInfo,
            'order_pro'=>$orderProList??[],
            'order_title'=>$orderTitle,
            'order_cont'=>$orderCont
        );
        $this->jkReturn(1,'订单详情',$data);
    }
    
    /*
     * explain:取消订单
     * params :
     * authors:Mr.Geng
     * addTime:2018/7/16 11:17
     */
    public function cancelOrder(Request $request,OrderModel $orderModel,ProductModel $productModel,OrderRefundModel $orderRefundModel,OrderProModel $orderProModel,UsersModel $usersModel,UserScoreLogModel $userScoreLogModel)
    {
        $param = $request->param();
        $orderInfo = $orderModel->where("order_id=".$param['order_id']." and user_id=".$param['user_id']." and order_state='Q02'")->find();
        if(empty($orderInfo)) $this->jkReturn('-1','该订单已发货,您可以选择退款功能!',[]);
        //-- 查看订单是否有进行中的退款请求
        $count = $orderRefundModel->where("order_id={$param['order_id']} and refund_state<>'W06'")->count();
        if($count>0){
            $this->jkReturn('-1','您有退款中商品,请取消退款申请或等待退款完成!',[]);
        }
        //-- 开启事物
        $orderModel->startTrans();
        //-- 编辑订单
        if(!$orderModel->update(['order_state'=>'Q06'],['order_id'=>$param['order_id']])){
            $orderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 增加订单商品库存
        $proList = $orderProModel->where("order_id={$param['order_id']}")->select();
        foreach ($proList as $v){
            if(!$productModel->where('product_id',$v->product_id)->setInc('pro_stock',$v->pro_num)){
                $orderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        //-- 返还用户积分
        if (!$usersModel->where('user_id',$param['user_id'])->setInc('user_score',$orderInfo->buy_score)) {
            $orderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 记录日志
        if (!$userScoreLogModel->save(['score'=>$orderInfo->buy_score,'desc'=>'取消订单:'.$orderInfo->order_sn,'user_id'=>$param['user_id']])) {
            $orderModel->rollback();
            $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
        }
        $orderModel->commit();
        $this->jkReturn('1','取消成功',[]);
    }
    
    /*
     * explain:确认收货
     * params :@user_id @order_id
     * authors:Mr.Geng
     * addTime:2018/4/9 14:33
     */
    public function takePro(Request $request,OrderModel $orderModel,OrderRefundModel $orderRefundModel)
    {
        $param = $request->param();
        //-- 查看订单是否有进行中的退款请求
        $count = $orderRefundModel->where("order_id={$param['order_id']} and refund_state<>'W06' and refund_state<>'W05'")->count();
        if($count>0){
            $this->jkReturn('-1','您有退款中商品,请取消退款申请或等待退款完成!',[]);
        }
        if (!$orderModel->update(['order_state'=>'Q04'],['order_id'=>$param['order_id']]))
            $this->jkReturn('-1', '网络延时请稍后重试',[]);
        $this->jkReturn(1,'您已确认收货',[]);
    }

    /*
     * explain:提交用户订单商品评价
     * params :@user_id @order_id
     * authors:Mr.Geng
     * addTime:2018/4/9 14:42
     */
    public function subComment(Request $request,ProCommentModel $proCommentModel,OrderModel $orderModel)
    {
        $param = $request->param();
        if (!empty($cont = $param['comment'])) {
            $proCommentModel->startTrans();
            foreach ($cont as $v) {
                $v['user_id'] = $param['user_id'];
                $v['has_img'] = empty($v['comment_img'])? 0: 1;
                $v['comment_img'] = urldecode($v['comment_img']);
                if (!$proCommentModel->allowField(true)->create($v))
                {
                    $proCommentModel->rollback();
                    $this->jkReturn('-1', '网络延时,请稍后重试', []);
                }
            }
        }
        if (!$orderModel->update(['order_state'=>'Q06'],['order_id'=>$param['order_id']])){
            $proCommentModel->rollback();
            $this->jkReturn('-1', '网络延时,请稍后重试', []);
        }
        $proCommentModel->commit();
        $this->jkReturn('1', '感谢您的评价', []);
    }

    /*
     * explain:申请退货
     * authors:Mr.Geng
     * addTime:2017/11/16 14:16
     */
    public function refundPro(Request $request,OrderProModel $orderProModel,RefundReasonModel $refundReasonModel)
    {
        $param = $request->param();
        $orderModel = new OrderModel();
        $orderInfo = $orderModel->where(['order_id' =>$param['order_id']])->find();
        $proInfo = $orderProModel->where(['order_pro_id'=>$param['order_pro_id']])->find();
        $reasonList = $refundReasonModel->select();
        $data = array(
            'pro_info'=>$proInfo,
            'order_info'=> $orderInfo,
            'reason'=>$reasonList,
            'refund_price'=>$proInfo->pro_score*$proInfo->pro_num
        );
        $this->jkReturn('1','退款确认',$data);
    }

    /*
     * explain:申请退货提交
     * authors:Mr.Geng
     * addTime:2017/11/16 14:34
     */
    public function refundSub(Request $request,OrderProModel $orderProModel,OrderRefundModel $orderRefundModel,OrderRefundLogModel $orderRefundLogModel,RefundReasonModel $refundReasonModel)
    {
        $param = $request->param();
        if (empty($param['user_id'])) {
            $this->jkReturn('-1','用户未登录',[]);
        }
        //-- 添加退款记录
        $orderPro = $orderProModel->append('refund_state')->where(['order_pro_id'=>$param['order_pro_id']])->find();
        if ($orderPro->refund_state != '0'&& $orderPro->refund_state != 'W06') {
            $this->jkReturn('-1','正在退款中,请联系客服确认', []);
        }
        $orderRefundData = $orderPro->toArray();
        $orderRefundData['reason_id'] = $param['reason_id'];
        $orderRefundData['refund_desc'] = $param['refund_desc'];
        $orderRefundData['is_refund_pro'] = $param['is_refund_pro'];
        $orderRefundData['refund_price'] = $param['refund_price'];
        $orderRefundData['refund_img'] = $param['refund_img'];
        $orderRefundData['refund_state'] = 'W01';
        $orderRefundModel->startTrans();
        if($orderPro->refund_state === 'W06'){
            if(!$orderRefundModel->update($orderRefundData,['order_pro_id'=>$param['order_pro_id']])){
                $orderRefundModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试', []);
            }
        }else{
            $orderRefundData['refund_time'] = $this->getTime();
            $orderRefundData['pro_img'] = serialize(array_splice($orderRefundData['pro_img'],1));
            $orderRefundData['refund_sn'] = $this->getOrderRefundSn();
            if(!$orderRefundModel->create($orderRefundData)){
                $orderRefundModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试', []);
            }
        }
        //-- 写入协商记录
        $log = $orderPro->toArray();
        $log['consult_title'] = '买家('.$request->user->user_name.')创建了售后申请';
        $reasonInfo = $refundReasonModel->where('reason_id',$param['reason_id'])->find();
        $log['consult_cont'] = '原因:'.$reasonInfo->reason_desc.'. 说明:'.$param['refund_desc'];
        $log['consult_name'] = '自己';
        if(!$orderRefundLogModel->create($log)){
            $orderRefundModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        $orderRefundModel->commit();
        $this->jkReturn('1', '退货申请成功,请耐心等候客服处理',[]);
    }
    
    /*
     * explain:退货列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/23 10:19
     */
    public function refundList(Request $request,OrderRefundModel $orderRefundModel)
    {
        $param = $request->param();
        $list = $orderRefundModel
            ->alias('o')
            ->field('o.*,r.reason_desc')
            ->where('user_id',$param['user_id'])
            ->join('new_refund_reason r','r.reason_id=o.reason_id','left')
            ->order('create_time','desc')->select();
        $this->jkReturn(1,'退款列表',$list);
    }

    /*
     * explain:退货详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/23 10:19
     */
    public function refundInfo(Request $request,OrderRefundModel $orderRefundModel,OrderRefundLogModel $orderRefundLogModel)
    {
        $param = $request->param();
        $list = $orderRefundModel
            ->alias('o')
            ->field('o.*,r.reason_desc')
            ->where('order_pro_id',$param['order_pro_id'])
            ->join('new_refund_reason r','r.reason_id = o.reason_id','left')
            ->order('create_time','desc')
            ->find();
        !$list && $this->jkReturn('-1','网络延时,请稍后重试',[]);
        //-- W01 退款申请中;W02 待买家退货;W03 待卖家收货;W04 待退款;W05 退款完成;W06 退款关闭
        switch ($list->refund_state){
            case 'W01':
                $refundTitle = '请等待卖家处理';
                $refundCont = '卖家会尽快处理您的退款申请';
                break;
            case 'W02':
                $refundTitle = '卖家同意退款退货,请将商品寄回';
                $refundCont = '请将商品以及赠品一并寄回';
                break;
            case 'W03':
                $refundTitle = '待卖家收货';
                $refundCont = '卖家收到货后会及时进行售后';
                break;
            case 'W04':
                $refundTitle = '售后申请通过,待卖家退款';
                $refundCont = '卖家会尽快处理您的退款申请';
                break;
            case 'W05':
                $refundTitle = '退款成功';
                $refundCont = $list->refund_pay_time."成功";
                break;
            case 'W06':
                $refundTitle = '退款关闭';
                $refundCont = "退款关闭";
                break;
        }
        $list->refund_title = $refundTitle;
        $list->refund_cont = $refundCont;
        $refundLog = $orderRefundLogModel->where('order_pro_id',$param['order_pro_id'])->order('create_time','desc')->select();
        $data = [
            'refund_info'=>$list,
            'refund_log'=>$refundLog
        ];

        $this->jkReturn('1','退款详情',$data);
    }

    /*
     * explain:取消退款申请
     * params :
     * authors:Mr.Geng
     * addTime:2018/7/17 10:29
     */
    public function cancelRefund(Request $request,OrderRefundModel $orderRefundModel,OrderRefundLogModel $orderRefundLogModel)
    {
        $param = $request->param();
        $refundInfo = $orderRefundModel->field('refund_state,order_id,order_sn,order_pro_id')->where(['order_pro_id'=>$param['order_pro_id'],'user_id'=>$param['user_id']])->find();
        if(empty($refundInfo)|| $refundInfo->refund_state != 'W01'){
            $this->jkReturn('-1','您的退款请求已处理,不能取消',[]);
        }
        if(!$orderRefundModel->update(['refund_state'=>'W06'],['order_pro_id'=>$param['order_pro_id'],'user_id'=>$param['user_id']])){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 写入协商记录
        $log = $refundInfo->toArray();
        $log['consult_title'] = '买家('.$request->user->user_name.')取消了退款申请';
        $log['consult_cont'] = '原因:买家手动取消退款申请';
        $log['consult_name'] = '自己';
        if(!$orderRefundLogModel->create($log)){
            $orderRefundModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        $this->jkReturn('1','您的退款已取消',[]);
    }


    /*
     * explain:添加物流信息
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/26 15:44
     */
    public function refundShipping(Request $request,OrderRefundModel $orderRefundModel,OrderProModel $orderProModel,OrderRefundLogModel $orderRefundLogModel)
    {
        $param = $request->param();
        $data = ['refund_shipping'=>$param['refund_shipping'],'shipping_sn'=>$param['shipping_sn'],'refund_state'=>'W03'];
        $orderRefundModel->startTrans();
        if(!$orderRefundModel->update($data,['order_pro_id'=>$param['order_pro_id']])){
            $orderRefundModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        //-- 写入协商记录
        $log = $orderProModel->where(['order_pro_id'=>$param['order_pro_id']])->find()->toArray();
        $log['consult_title'] = '买家('.$request->user->user_name.')寄回了商品';
        $log['consult_cont'] = '物流公司:'.$param['refund_shipping'].'. 退货单号:'.$param['shipping_sn'];
        $log['consult_name'] = '自己';
        if(!$orderRefundLogModel->create($log)){
            $orderRefundModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        $orderRefundModel->commit();
        $this->jkReturn('1','感谢您的合作',[]);
    }

    /*
     * explain:物流查询
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/3 12:01
     */
    public function shippingInfo(Request $request,LogisticsService $logisticsService,ShippingModel $shippingModel)
    {
        $sn = $request->param('shipping_sn');
        $shippingId = $request->param('shipping_id');
        $shippingInfo = $shippingModel->where('shipping_id',$shippingId)->find();
        $result= $logisticsService->logisticsInfo($sn);
        $data = [
            'log'=>$result,
            'shipping_info'=>$shippingInfo
        ];
        $this->jkReturn('1','物流详情',$data);
    }

}