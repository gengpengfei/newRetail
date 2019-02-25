<?php
namespace app\shop\controller;
use app\shop\model\CategoryModel;
use app\shop\model\NavModel;
use app\shop\model\RefundReasonModel;
use app\shop\model\StoreClearModel;
use app\shop\model\StoreClearRuleModel;
use app\shop\model\StoreModel;
use app\shop\model\StoreOrderModel;
use app\shop\model\StoreReportModel;
use app\shop\model\UsersModel;
use app\shop\model\UserVoucherModel;
use app\shop\model\UserVoucherRefundModel;
use geohash\Geohash;
use think\Request;

use think\Db;
use think\Queue;
use think\Session;


/**
 * Created by PhpStorm.
 * User: jlcr
 * Date: 2018/4/4
 * Time: 15:31
 */
class Order extends Common
{
    use \app\api\traits\BuildParam;

    /*
     * 店铺订单模块
     * */
    //店铺交易详情
    public function storeOrderInfo(NavModel $navmodel,Request $request,CategoryModel $categorymodel,StoreClearRuleModel $clearrulemodel,StoreClearModel $storeclearmodel,StoreReportModel $storereportmodel,StoreModel $storemodel){
        $parms=$request->param();
        $parms['store_id'] = Session::get('shop_id');
        if (empty($parms['store_id'])){
            $this->error("store_id无效",'/shop/Order/store_orderlist');
        }
        $store_id=$parms['store_id'];
        $storeinfo=$storemodel->where('store_id ='.$store_id)->find();
        if (empty($storeinfo)){
            $this->error("店铺信息无效",'/shop/Order/store_order_list');
        }
        //所属行业
        $navinfo=$navmodel->field('nav_name')->where('nav_id = '.$storeinfo->nav_id)->find();
        //获取分类
        $category_id=$storeinfo->category_id;
        $categoryarr=explode(',',$category_id);
        $categorystr='';
        foreach ($categoryarr as $value){
            $categoryinfo=$categorymodel->field('category_name')->where('category_id='.$value)->find();
            $categorystr.=$categoryinfo->category_name.' ';
        }
        //店铺订单统计
        $storereport=$storereportmodel->where('store_id = '.$store_id)->find();
        //冻结及可提现金额
        //冻结金额和未冻结金额
        $store_credit=$storeinfo->store_credit;
        $category_arr=explode(',',$storeinfo->category_id);
        $nav_id=$storeinfo->nav_id;
        $clearday=10;
        //获取提现规则
        $clearrulelist=$clearrulemodel->where('disabled = 1')->order('rule_range desc,id desc')->select()->toArray();
        foreach ($clearrulelist as $keytwo =>$valtwo){
            $ruleinfo=$valtwo['rule_range_info'];
            $arr_rule_range=explode(',',$ruleinfo);
            if ($valtwo['rule_range']==3){
                if (in_array($store_id,$arr_rule_range)){
                    $clearday=$valtwo['rule_info'];
                    break;
                }
            }elseif($valtwo['rule_range']==2){
                if ($store_credit>=$arr_rule_range[0]&&$store_credit<=$arr_rule_range[1]){
                    $clearday=$valtwo['rule_info'];
                    break;
                }
            }elseif($valtwo['rule_range']==1){
                if (array_intersect($category_arr,$arr_rule_range)){
                    $clearday=$valtwo['rule_info'];
                    break;
                }
            }elseif($valtwo['rule_range']==0){
                if (in_array($nav_id,$arr_rule_range)){
                    $clearday=$valtwo['rule_info'];
                    break;
                }
            }
        }
        $freezesql="DATE_SUB(CURDATE(), INTERVAL $clearday DAY) < date(create_time) and clear_state=0 and store_id= $store_id";
        $unfreezesql="DATE_SUB(CURDATE(), INTERVAL $clearday DAY) >= date(create_time) and clear_state=0 and store_id= $store_id";
        $freezeinfo=$storeclearmodel->field('clear_price')->where($freezesql)->sum('clear_price');
        $unfreezeinfo=$storeclearmodel->field('clear_price')->where($unfreezesql)->sum('clear_price');
        $this->assign('storeinfo',$storeinfo);
        $this->assign('navinfo',$navinfo);
        $this->assign('categorystr',$categorystr);
        $this->assign('storereport',$storereport);
        $this->assign('freezeinfo',$freezeinfo);
        $this->assign('unfreezeinfo',$unfreezeinfo);
        // 模板输出
        return view("Order/store_order_info");
    }
    //商品订单列表(线上)
    public function storeForOrderList(Request $request,StoreClearModel $storeClearModel){
        $storeClearModel->data($request->param());
        if (empty($storeClearModel->store_id)){
            $storeClearModel->store_id = Session::get('shop_id');;
        }
        $store_id=$storeClearModel->store_id;
        //获取分页数
        if (!empty($storeClearModel->show_count)){
            $show_count = $storeClearModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1 and order_price > 0 and store_id ='.$store_id;
        $orderWhere = '1=1 and order_price > 0 and store_id ='.$store_id;
        if (!empty($storeClearModel->order_type)) {
            $where.=" and order_type =".($storeClearModel->order_type-1);
        }
        if(!empty($storeClearModel->keywords)){
            $keywords = $storeClearModel->keywords;
            $where .= " and (order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%')";
        }
        if(!empty($storeClearModel->datemin)){
            $where .= " and a.create_time > '" . $storeClearModel->datemin . "'";
            $orderWhere .= " and create_time > '" . $storeClearModel->datemin . "'";
        }
        if(!empty($storeClearModel->datemax)){
            $datemax = strtotime($storeClearModel->datemax);
            $datemax = date('Y-m-d',$datemax);
            $where .= " and a.create_time < '" . $datemax . "'";
            $orderWhere .= " and create_time < '" . $datemax . "'";
        }
        if(!empty($storeClearModel->datemin)&&!empty($storeClearModel->datemax)&&$storeClearModel->datemin>$storeClearModel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($storeClearModel->orderBy)){
            $orderBy = $storeClearModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeClearModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeClearModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $orderList = $storeClearModel
            ->field('a.*,u.mobile')
            ->alias('a')
            ->join('new_users u','a.user_id = u.user_id','LEFT')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //buy_price
        if (!empty($orderList)) {
            foreach ($orderList as &$order) {
                //实付金额
                $order['buy_price'] = $order['order_price'] - $order['user_voucher_price'] - $order['discount_price'];
            }
        }
        //分页带参数
        $parmas = request()->param();
        $page = $orderList->appends($parmas)->render();

        //店铺今日订单统计
        //-- 商品订单数
        $validOrderNum = Db::table('new_store_clear')
            ->where($orderWhere . " and order_type = 0")
            ->count();
        $this->assign('validOrderNum',$validOrderNum);
        //-- 商品订单金额
        $validOrderPrice = Db::table('new_store_clear')
            ->where($orderWhere . " and order_type = 0")
            ->sum('clear_price');
        $this->assign('validOrderPrice',$validOrderPrice);
        //-- 支付订单数
        $offlineOrderNum = Db::table('new_store_clear')
            ->where($orderWhere . " and order_type = 1")
            ->count();
        $this->assign('offlineOrderNum',$offlineOrderNum);
        //-- 支付订单金额
        $offlineOrderPrice = Db::table('new_store_clear')
            ->where($orderWhere . " and order_type = 1")
            ->sum('clear_price');
        $this->assign('offlineOrderPrice',$offlineOrderPrice);
        //-- 店铺冻结金额
        $unFreePrice = Db::table('new_store_clear')
            ->where("store_id=$store_id and clear_state=0")
            ->sum('clear_price');
        $this->assign('unFreePrice',$unFreePrice);
        //-- 店铺补贴金额
        $unFreePrice = Db::table('new_store_clear')
            ->where("store_id=$store_id")
            ->sum('discount_price');
        $this->assign('unFreePrice',$unFreePrice);

        $this->assign('orderList',$orderList);
        $this->assign('page',$page);
        $this->assign('status',1);
        $this->assign('where', $storeClearModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('store_id', $store_id);
        //$this->assign('order_type', $storeClearModel->order_type);
        // 模板输出
        return view("Order/store_for_order_list");
    }

    public function storeTodayOrderList(StoreOrderModel $storeordermodel,Request $request,StoreClearModel $storeClearModel,UsersModel $usermodel){
        $storeordermodel->data($request->param());
        if (empty($storeordermodel->store_id)){
            $storeordermodel->store_id = Session::get('shop_id');;
        }
        $store_id=$storeordermodel->store_id;
        if (empty($storeordermodel->order_type)){
            $storeordermodel->order_type=2;
        }
        $storefield=array('order_id','order_sn','order_price','buy_price','coupons_price','voucher_num','order_state','mobile','a.create_time','refund_price');
        //获取分页数
        if (!empty($storeordermodel->show_count)){
            $show_count = $storeordermodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1 and store_id ='.$store_id;
        $where.=" and order_type =".($storeordermodel->order_type-1);
        if(!empty($storeordermodel->keywords)){
            $keywords = $storeordermodel->keywords;
            $where .= " and (order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%')";
        }
        if(!empty($storeordermodel->datemin)){
            $where .= " and a.create_time > '" . $storeordermodel->datemin . "'";
        }
        if(!empty($storeordermodel->datemax)){
            $datemax = strtotime($storeordermodel->datemax);
            $datemax = date('Y-m-d',$datemax);
            $where .= " and a.create_time < '" . $datemax . "'";
        }
        if(!empty($storeordermodel->datemin)&&!empty($storeordermodel->datemax)&&$storeordermodel->datemin>$storeordermodel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($storeordermodel->orderBy)){
            $orderBy = $storeordermodel->orderBy;
        }else{
            $orderBy = 'order_id';
        }
        if(!empty($storeordermodel->orderByUpOrDown)){
            $orderByUpOrDown = $storeordermodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $usersql=$usermodel->buildSql();
        $orderlist=$storeordermodel->alias('a')->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')->field($storefield)->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$orderlist->appends($parmas)->render();
        $this->assign('orderlist',$orderlist);
        $this->assign('page',$page);
        $this->assign('status',0);
        $this->assign('where', $storeordermodel->toArray());
        $this->assign('pronum',$orderlist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('store_id', $store_id);
        $this->assign('order_type', $storeordermodel->order_type);
        // 模板输出
        return view("Order/store_for_order_list");
    }

    //商品订单详情
    public function storeOrderListDetail(Request $request,StoreClearModel $storeClearModel){
        $storeClearModel->data($request->param());
        if (empty($storeClearModel->order_id)){
            $this->error("order_id无效",'/shop/Order/store_order_list');
        }
        $order_id = $storeClearModel->order_id;

        $orderList = $storeClearModel
            ->field('a.*,u.mobile,u.user_name,o.voucher_name')
            ->alias('a')
            ->join('new_users u','a.user_id = u.user_id','LEFT')
            ->join('new_store_order o','a.order_id = o.order_id','LEFT')
            ->where('a.order_id = '.$order_id)
            ->find()->toArray();

        $orderList['buy_price'] = $orderList['order_price'] - $orderList['user_voucher_price'] - $orderList['discount_price'];

        // 模板输出
        $this->assign('orderList',$orderList);
        return view("Order/store_order_list_detail");
    }
    /*
     * 退款模块
     * */
    //商品订单退款列表
    public function proOrderRefundList(Request $request,StoreClearModel $storeClearModel){
        $storeClearModel->data($request->param());
        if (empty($storeClearModel->store_id)){
            $storeClearModel->store_id = Session::get('shop_id');;
        }
        $store_id = $storeClearModel->store_id;
        //获取分页数
        if (!empty($storeClearModel->show_count)){
            $show_count = $storeClearModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = '1=1 and order_price < 0 and order_type = 0 and store_id ='.$store_id;

        //排序条件
        if(!empty($storeClearModel->orderBy)){
            $orderBy = $storeClearModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeClearModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeClearModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $refundList = $storeClearModel
            ->field('a.*,u.mobile')
            ->alias('a')
            ->join('new_users u','a.user_id = u.user_id','LEFT')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //buy_price
        if (!empty($refundList)) {
            foreach ($refundList as &$order) {
                //实付金额
                $order['buy_price'] = $order['order_price'] - $order['user_voucher_price'] - $order['discount_price'];
            }
        }
        //分页带参数
        $parmas = request()->param();
        $page = $refundList->appends($parmas)->render();

        //店铺今日订单统计
        //-- 退款订单数
        $validOrderNum = Db::table('new_store_clear')
            ->where($where)
            ->count();
        $this->assign('validOrderNum',$validOrderNum);
        //-- 退款订单金额
        $validOrderPrice = Db::table('new_store_clear')
            ->where($where)
            ->sum('clear_price');
        $this->assign('validOrderPrice',$validOrderPrice);
        $this->assign('refundList',$refundList);
        $this->assign('page',$page);
        $this->assign('status',1);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Order/pro_order_refund_list");
    }
    //商品订单退款详情
    public function proOrderRefundInfo(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UsersModel $usermodel,StoreModel $storemodel,RefundReasonModel $refundreasonmodel){
        $uservoucherrefundmodel->data($request->param());
        if(empty($uservoucherrefundmodel->id)){
           $this->error('商品退款订单id有误');
        }
        $id=$uservoucherrefundmodel->id;
        $refundinfo=$uservoucherrefundmodel->where('id ='.$id)->find();
        if (empty($refundinfo)){
            $this->error('找不到该商品退款订单');
        }
        $userinfo=$usermodel->where('user_id ='.$refundinfo->user_id)->find();
        if (empty($userinfo)){
            $this->error('找不到该用户信息');
        }
        $storeinfo=$storemodel->where('store_id ='.$refundinfo->store_id)->find();
        if (empty($storeinfo)){
            $this->error('找不到该店铺信息');
        }
        $refundreasoninfo=$refundreasonmodel->where('reason_id ='.$refundinfo->reason_id)->find();
        $refundstatearr=array(
            'D01'=>'退款申请中',
            'D02'=>'退款中',
            'D03'=>'已打款',
            'D04'=>'退款成功',
            'D06'=>'退款关闭'
        );
        $refundstate=$refundinfo->refund_state;
        $refundstate=$refundstatearr[$refundstate];
        $refund_img=$refundinfo->refund_img;
        $refund_img=substr($refund_img,6);
        $this->assign('refundinfo',$refundinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('storeinfo',$storeinfo);
        $this->assign('refundreasoninfo',$refundreasoninfo);
        $this->assign('refundstate',$refundstate);
        $this->assign('refund_img',$refund_img);
        // 模板输出
        return view("Order/pro_order_refund_info");
    }
    //商品订单退款操作
    public function proOrderRefund(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UsersModel $usermodel,UserVoucherModel $uservouchermodel,StoreOrderModel $storeordermodel,UserMoneyLogModel $usermoneylogmodel,UserScoreLogModel $userscorelogmodel){
        $uservoucherrefundmodel->data($request->param());
        if(empty($uservoucherrefundmodel->id)){
            $this->error('商品退款订单id有误');
        }
        $id=$uservoucherrefundmodel->id;
        $refundinfo=$uservoucherrefundmodel->where('id',$id)->find();
        if ($refundinfo->refund_state!='D01'){
            $this->error('该商品状态不能退款');
        }
        if (empty($refundinfo)){
            $this->error('找不到该商品退款订单');
        }
        $user_id=$refundinfo->user_id;
        $userinfo=$usermodel->where('user_id',$user_id)->find();
        if (empty($userinfo)){
            $this->error('找不到用户信息');
        }
        $user_voucher_id=$refundinfo->user_voucher_id;
        $uservoucherinfo=$uservouchermodel->where('user_voucher_id ='.$user_voucher_id)->find();
        if (empty($uservoucherinfo)){
            $this->error('找不到用户抵用券信息');
        }
        $olduserscore=$userinfo->user_score;
        $oldusermoney=$userinfo->user_money;
        $deductscore=$uservoucherinfo->user_give_score;   //返还积分
        $backmoney=$refundinfo->refund_price;  //退款金额
        //需要更改的用户金额和积分
        $nowuserscore=$olduserscore-$deductscore;
        $nowusermoney=$oldusermoney+$backmoney;
        //订单退款金额
        $storeorderinfo=$storeordermodel->where('order_id',$refundinfo->order_id)->find();
        $order_refund_price=$storeorderinfo->refund_price;
        $change_refund_price=$order_refund_price+$backmoney;
        // 启动事务
        Db::startTrans();
        try{
            Db::table('new_user_voucher_refund')->where('id',$id)->update(['refund_state'=>'D04']);
            Db::table('new_users')->where('user_id',$user_id)->update(['user_score'=>$nowuserscore,'user_money'=>$nowusermoney]);
            Db::table('new_user_voucher')->where('user_voucher_id',$user_voucher_id)->update(['used_state'=>'C04']);
            Db::table('new_store_order')->where('order_id',$refundinfo->order_id)->update(['refund_price'=>$change_refund_price]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        //记日志
        $moneylogarr=array(
            'user_id'=>$user_id,
            'type'=>2,
            'desc'=>"商品退款,订单:".$refundinfo->order_sn,
            'money'=>$backmoney
        );
        $scorelogarr=array(
            'user_id'=>$user_id,
            'desc'=>'商品退款,订单:'.$refundinfo->order_sn,
            'score'=>'-'.$deductscore
        );
        $usermoneylogmodel->save($moneylogarr);
        $userscorelogmodel->save($scorelogarr);
        $this->setAdminUserLog("编辑","商品退款:id为$id","new_user_voucher_refund",$id);
        //判断订单状态是否需要修改
        $order_id=$refundinfo->order_id;
        $orderinfo=$storeordermodel->where('order_id',$order_id)->find();
        $voucher_num=$orderinfo->voucher_num;
        $refundcount=$uservoucherrefundmodel->where("order_id = $order_id and refund_state != 'D01'")->count();
        if ($voucher_num==$refundcount){
            if(!$storeordermodel->allowField(true)->save(['order_state'=>'T04'],['order_id'=>$order_id])){
                $this->error('退款成功,订单状态更改失败');
            }
        }
        $this->success('退款成功');
    }
}