<?php

namespace app\admin\controller;

use app\admin\model\CategoryModel;
use app\admin\model\NavModel;
use app\admin\model\RefundReasonModel;
use app\admin\model\StoreClearBillModel;
use app\admin\model\StoreClearModel;
use app\admin\model\StoreClearRuleModel;
use app\admin\model\StoreModel;
use app\admin\model\StoreOrderModel;
use app\admin\model\StoreRebateLogModel;
use app\admin\model\StoreReportDayModel;
use app\admin\model\StoreReportModel;
use app\admin\model\UserModel;
use app\admin\model\UserMoneyLogModel;
use app\admin\model\UserScoreLogModel;
use app\admin\model\UserVoucherModel;
use app\admin\model\UserVoucherRefundModel;
use think\Db;
use think\Request;

class Report extends Common{
    //用户消费列表
    public function clearList(StoreClearModel $storeClearModel, Request $request)
    {
        $storeClearModel->data($request->param());
        if (!empty($storeClearModel->show_count)){
            $show_count = $storeClearModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if (!empty($storeClearModel->clear_state)){
            $clear_state = $storeClearModel->clear_state-1;
            $where .= " and b.clear_state = $clear_state ";
        }
        if(!empty($storeClearModel->keywords)){
            $keywords = $storeClearModel->keywords;
            $where .= " and  (u.user_name like '%" . $keywords . "%' or s.store_name like '%" . $keywords ."%')";
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
        $storeClearList = $storeClearModel
            ->field('b.*,u.user_name,s.store_name')
            ->alias('b')
            ->join('new_users u','b.user_id = u.user_id','left')
            ->join('new_store s','b.store_id = s.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        if (!empty($storeClearList)) {
            foreach ($storeClearList as &$order) {
                //实付金额
                $order['buy_price'] = $order['order_price'] - $order['user_voucher_price'] - $order['discount_price'];
            }
        }

        // 获取分页显示
        $page = $storeClearList->render();
        // 模板变量赋值
        $this->assign('storeClearList', $storeClearList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $storeClearModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('protectList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Report/store_clear_list");
    }
    /*
     * 店铺订单模块
     * */
    //店铺订单统计列表
    public function storeorderlist(NavModel $navmodel,Request $request,StoreClearRuleModel $clearrulemodel,StoreClearModel $storeClearModel,StoreReportModel $storereportmodel,StoreModel $storemodel){
        $storereportmodel->data($request->param());
        $storefield=array('a.store_id','u.store_name','j.nav_name','a.offline_order','a.offline_order_price','a.valid_order','a.valid_order_price','a.coupons_price','u.store_id','u.category_id','u.nav_id','u.store_credit','u.audit_state');
        //获取行业列表
        $navlist=$navmodel->where('disabled=1')->select();
        //获取分页数
        if (!empty($storereportmodel->show_count)){
            $show_count = $storereportmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='u.audit_state = 1';
        if (!empty($storereportmodel->navshow)){
            $where .= " and u.nav_id = $storereportmodel->navshow ";
        }
        if(!empty($storereportmodel->keywords)){
            $keywords = $storereportmodel->keywords;
            $where .= " and store_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($storereportmodel->orderBy)){
            $orderBy = $storereportmodel->orderBy;
        }else{
            $orderBy = 'a.store_id';
        }
        if(!empty($storereportmodel->orderByUpOrDown)){
            $orderByUpOrDown = $storereportmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storesql=$storemodel->buildSql();
        $navsql=$navmodel->buildSql();
        $storelist=$storereportmodel->alias('a')->join([$storesql=> 'u'],'a.store_id = u.store_id','LEFT')->join([$navsql=> 'j'],'u.nav_id = j.nav_id','LEFT')->field($storefield)->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$storelist->appends($parmas)->render();
        $this->assign('storelist',$storelist);
        $this->assign('page',$page);
        $this->assign('where', $storereportmodel->toArray());
        $this->assign('pronum',$storelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('navlist', $navlist);
        // 模板输出
        return view("Report/storeorderlist");
    }
    //店铺交易详情
    public function storeorderinfo(NavModel $navmodel,Request $request,CategoryModel $categorymodel,StoreClearRuleModel $clearrulemodel,StoreClearModel $storeClearModel,StoreReportModel $storereportmodel,StoreModel $storemodel){
        $parms=$request->param();
        if (empty($parms['store_id'])){
            $this->error("store_id无效",'/admin/Store/storeorderlist');
        }
        $store_id=$parms['store_id'];
        $storeinfo=$storemodel->where('store_id ='.$store_id)->find();
        if (empty($storeinfo)){
            $this->error("店铺信息无效",'/admin/Store/storeorderlist');
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
        $this->assign('storeinfo',$storeinfo);
        $this->assign('navinfo',$navinfo);
        $this->assign('categorystr',$categorystr);
        $this->assign('storereport',$storereport);
        // 模板输出
        return view("Report/storeorderinfo");
    }
    //商品订单列表(线上)
    public function storefororderlist(StoreOrderModel $storeOrderModel,Request $request,StoreClearModel $storeClearModel){
        $storeOrderModel->data($request->param());
        $storefield=array('a.order_id','a.order_sn','a.order_price','a.buy_price','a.voucher_num','a.voucher_name','a.order_state','u.mobile','a.create_time','refund_price','c.discount_price','s.store_name','c.order_type');
        //获取分页数
        if (!empty($storeOrderModel->show_count)){
            $show_count = $storeOrderModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1 ';
        if (!empty($storeOrderModel->order_type)) {
            $where.=" and c.order_type =".($storeOrderModel->order_type-1);
        }
        if(!empty($storeOrderModel->keywords)){
            $keywords = $storeOrderModel->keywords;
            $where .= " and (a.order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%' or s.store_name like '%" . $keywords . "%' )";
        }
        if(!empty($storeOrderModel->datemin)){
            $where .= " and a.create_time > '" . $storeOrderModel->datemin . "'";
        }
        if(!empty($storeOrderModel->datemax)){
            $datemax=strtotime($storeOrderModel->datemax)+86400;
            $datemax=date('Y-m-d',$datemax);
            $where .= " and a.create_time < '" . $datemax . "'";
        }
        if(!empty($storeOrderModel->datemin)&&!empty($storeOrderModel->datemax)&&$storeOrderModel->datemin>$storeOrderModel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($storeOrderModel->orderBy)){
            $orderBy = $storeOrderModel->orderBy;
        }else{
            $orderBy = 'a.order_id';
        }
        if(!empty($storeOrderModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeOrderModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $orderList = $storeOrderModel
            ->field($storefield)
            ->alias('a')
            ->join('new_users u','a.user_id = u.user_id','LEFT')
            ->join('new_store s','a.store_id = s.store_id','LEFT')
            ->join('new_store_clear c','a.order_id = c.order_id','LEFT')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$orderList->appends($parmas)->render();
        $this->assign('orderList',$orderList);
        $this->assign('page',$page);
        $this->assign('where', $storeOrderModel->toArray());
        $this->assign('pronum',$orderList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Report/storefororderlist");
    }
    //商品订单详情
    public function storeorderlistdetail(StoreOrderModel $storeOrderModel,Request $request,UserVoucherModel $uservouchermodel){
        $storeOrderModel->data($request->param());
        if (empty($storeOrderModel->order_id)){
            $this->error("order_id无效",'/admin/store/storeorderlist');
        }
        $order_id=$storeOrderModel->order_id;
        $storeField=array('a.order_id','a.order_sn','a.order_price','a.buy_price','a.voucher_num','a.order_state','u.mobile','u.user_name','a.create_time','refund_price','c.discount_price','s.store_name','c.order_type','s.store_id');

        $storeOrderInfo = $storeOrderModel
            ->field($storeField)
            ->alias('a')
            ->join('new_users u','a.user_id = u.user_id','LEFT')
            ->join('new_store s','a.store_id = s.store_id','LEFT')
            ->join('new_store_clear c','a.order_id = c.order_id','LEFT')
            ->where('a.order_id = '.$order_id)
            ->find();
        //获取商品列表
        $uservoucherlist=$uservouchermodel->where('order_id ='.$order_id)->select();
        $voucherstats=array(
            'C01'=>待激活,
            'C02'=>待使用,
            'C03'=>已使用,
            'C04'=>已失效,
            'C05'=>已退款,
        );
        foreach ($uservoucherlist as $key => $val){
            $status=$val->used_state;
            $uservoucherlist[$key]->used_state=$voucherstats[$status];
        }
        //总金额
        $uservouchersum=$uservouchermodel->where('order_id ='.$order_id)->sum('buy_price');
        // 模板输出
        $this->assign('storeOrderInfo',$storeOrderInfo);
        $this->assign('uservoucherlist',$uservoucherlist);
        $this->assign('uservouchersum',$uservouchersum);
        return view("Report/storeorderlistdetail");
    }

    /*
     * 退款模块
     * */
    //商品订单退款列表
    public function proorderrefundlist(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,StoreModel $storemodel,UserModel $usermodel,RefundReasonModel $refundreasonmodel){
        $uservoucherrefundmodel->data($request->param());
        //获取分页数
        if (!empty($uservoucherrefundmodel->show_count)){
            $show_count = $uservoucherrefundmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($uservoucherrefundmodel->keywords)){
            $keywords = $uservoucherrefundmodel->keywords;
            $where .= " and (order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%')";
        }
        if(!empty($uservoucherrefundmodel->store_name)){
            $store_name = $uservoucherrefundmodel->store_name;
            $where .= " and (w.store_name like '%" . $store_name . "%')";
        }
        //订单状态
        if (!empty($uservoucherrefundmodel->audit_state)){
            $refund_state = $uservoucherrefundmodel->audit_state;
            $where .= " and refund_state = '".$refund_state."'";
        }
        if(!empty($uservoucherrefundmodel->datemin)){
            $where .= " and a.refund_time > '" . $uservoucherrefundmodel->datemin . "'";
        }
        if(!empty($uservoucherrefundmodel->datemax)){
            $datemax=strtotime($uservoucherrefundmodel->datemax)+86400;
            $datemax=date('Y-m-d',$datemax);
            $where .= " and a.refund_time < '" . $datemax . "'";
        }
        if(!empty($uservoucherrefundmodel->datemin)&&!empty($uservoucherrefundmodel->datemax)&&$uservoucherrefundmodel->datemin>$uservoucherrefundmodel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($uservoucherrefundmodel->orderBy)){
            $orderBy = $uservoucherrefundmodel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($uservoucherrefundmodel->orderByUpOrDown)){
            $orderByUpOrDown = $uservoucherrefundmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        //退款状态
        $refundstatearr=array(
            'D01'=>'退款申请中',
            'D02'=>'退款中',
            'D03'=>'已打款',
            'D04'=>'退款成功',
            'D06'=>'退款关闭'
        );
        $usersql=$usermodel->buildSql();
        $storesql=$storemodel->buildSql();
        $refundlist=$uservoucherrefundmodel
            ->alias('a')
            ->field('a.*,u.mobile,w.store_name')
            ->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')
            ->join([$storesql=> 'w'],'a.store_id = w.store_id','LEFT')
            ->where($where)->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$refundlist->appends($parmas)->render();
        foreach($refundlist as $key => $value){
            $refundstate=$value->refund_state;
            $refundlist[$key]->refundstate=$refundstatearr[$refundstate];
            $reasonid=$value->reason_id;
            if (empty($reasonid)){
                $reasonid=0;
            }
            $refundreasonrow=$refundreasonmodel->where('reason_id ='.$reasonid)->find();
            if (empty($refundreasonrow)){
                $refundreasoncon='';
            }else{
                $refundreasoncon=$refundreasonrow->reason_desc;
            }
            $refundlist[$key]->refundreasoncon=$refundreasoncon;
        }
        //统计退款笔数,金额
        $allrefundmoney=$uservoucherrefundmodel->sum('refund_price');
        $todaysql='to_days(refund_time) = to_days(now())';
        $todayrefundcount=$uservoucherrefundmodel->where($todaysql)->count();
        $todayrefundmoney=$uservoucherrefundmodel->where($todaysql)->sum('refund_price');
        $this->assign('refundlist',$refundlist);
        $this->assign('page',$page);
        $this->assign('where', $uservoucherrefundmodel->toArray());
        $this->assign('pronum',$refundlist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('refundstatearr', $refundstatearr);
        $this->assign('allrefundmoney', $allrefundmoney);
        $this->assign('todayrefundcount', $todayrefundcount);
        $this->assign('todayrefundmoney', $todayrefundmoney);
        // 模板输出
        return view("Report/proorderrefundlist");
    }
    //商品订单退款详情
    public function proorderrefundinfo(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UserModel $usermodel,StoreModel $storemodel,RefundReasonModel $refundreasonmodel){
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
        return view("Report/proorderrefundinfo");
    }
    //商品订单退款操作(后台退款只能退至用户余额,需要与用户沟通好)
    public function proorderrefund(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UserModel $usermodel,UserVoucherModel $uservouchermodel,UserVoucherRefundModel $userVoucherRefundModel, StoreOrderModel $storeOrderModel,UserMoneyLogModel $usermoneylogmodel,UserScoreLogModel $userscorelogmodel,StoreModel $storeModel,StoreRebateLogModel $storeRebateLogModel){
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
        //用户信息
        $user_id=$refundinfo->user_id;
        $userinfo=$usermodel->where('user_id',$user_id)->find();
        if (empty($userinfo)){
            $this->error('找不到用户信息');
        }
        //店铺信息
        $store_id=$refundinfo->store_id;
        $storeInfo=$storeModel->where('store_id',$store_id)->find();
        if (empty($storeInfo)){
            $this->error('找不到店铺信息');
        }
        //订单信息
        $storeorderinfo=$storeOrderModel->where('order_id',$refundinfo->order_id)->find();

        $user_voucher_id=$refundinfo->user_voucher_id;
        $uservoucherinfo=$uservouchermodel->where('order_id',$refundinfo->order_id)->select();
        if (empty($uservoucherinfo)){
            $this->error('找不到用户抵用券信息');
        }
        $userVoucherRefund=$userVoucherRefundModel->where('order_id',$refundinfo->order_id)->select();
        if (empty($userVoucherRefund)){
            $this->error('找不到用户退款信息');
        }

        $olduserscore = $userinfo->user_score;//用户积分
        $oldusermoney = $userinfo->user_money;//用户余额
        $userGiveScore = $storeorderinfo->user_give_score;   //返还用户积分
        $storeGiveScore = $storeorderinfo->store_give_score;   //返还店铺积分
        $backmoney = $refundinfo->refund_price;  //退款金额
        //需要更改的用户金额和积分
        $userRefundNumber = count($uservoucherinfo) - count($userVoucherRefund);
        if ($userRefundNumber == 0) {
            $deductscore = $userGiveScore;
            $storeScore = $storeGiveScore;
        } else {
            $deductscore = ceil($userGiveScore/$userRefundNumber);
            $storeScore = ceil($storeGiveScore/$userRefundNumber);
        }
        //用户积分
        $nowuserscore=$olduserscore-$deductscore;
        //用户余额
        $nowusermoney=$oldusermoney+$backmoney;
        //订单退款金额
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
        $storeRebateLog=array(
            'store_id'=>$store_id,
            'desc'=>'商品退款,订单:'.$refundinfo->order_sn . '，优惠券名:'. $refundinfo->voucher_name . ',优惠券ID:' . $refundinfo->voucher_id,
            'score'=>'-'.$storeScore
        );
        $usermoneylogmodel->save($moneylogarr);
        $storeRebateLogModel->save($storeRebateLog);
        $userscorelogmodel->save($scorelogarr);
        $this->setAdminUserLog("编辑","商品退款:id为$id","new_user_voucher_refund",$id);
        //判断订单状态是否需要修改
        $order_id=$refundinfo->order_id;
        $orderinfo=$storeOrderModel->where('order_id',$order_id)->find();
        $voucher_num=$orderinfo->voucher_num;
        $refundcount=$uservoucherrefundmodel->where("order_id = $order_id and refund_state != 'D01'")->count();
        if ($voucher_num==$refundcount){
            if(!$storeOrderModel->allowField(true)->save(['order_state'=>'T04'],['order_id'=>$order_id])){
                $this->error('退款成功,订单状态更改失败');
            }
        }
        $this->success('退款成功');
    }

    //店铺账单
    public function storeClear(StoreClearBillModel $storeClearBillModel, Request $request)
    {
        $storeClearBillModel->data($request->param());
        if (!empty($storeClearBillModel->show_count)){
            $show_count = $storeClearBillModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if (!empty($storeClearBillModel->clear_state)){
            $clear_state = $storeClearBillModel->clear_state-1;
            $where .= " and b.clear_state = $clear_state ";
        }
        if(!empty($storeClearBillModel->keywords)){
            $keywords = $storeClearBillModel->keywords;
            $where .= " and (s.store_name like '%" . $keywords . "%' or u.user_name like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($storeClearBillModel->orderBy)){
            $orderBy = $storeClearBillModel->orderBy;
        }else{
            $orderBy = 'b.id';
        }
        if(!empty($storeClearBillModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeClearBillModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storeClearList = $storeClearBillModel
            ->field('b.id,b.pay_price,b.store_id,b.pay_state,s.store_name,a.audit_bank,a.audit_bank_card,u.mobile,b.clear_start_time,b.clear_end_time')
            ->alias('b')
            ->join('new_store s','b.store_id = s.store_id','left')
            ->join('new_store_audit a','b.store_id = a.store_id','left')
            ->join('store_user u','a.admin_id = u.admin_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->group('b.store_id')
            ->paginate($show_count);
        if ($storeClearList) {
            foreach ($storeClearList as &$clearList) {
                $clearList['audit_bank_card'] = substr_replace($clearList['audit_bank_card'], '****', 4, 12);
            }
        }
        // 获取分页显示
        $page = $storeClearList->render();
        // 模板变量赋值
        $this->assign('storeClearList', $storeClearList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $storeClearBillModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('protectList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Report/store_clear_number_list");
    }
    //账单详情
    public function storeClearDetail(StoreClearBillModel $storeClearBillModel, StoreClearModel $storeClearModel, Request $request)
    {
        $storeClearBill = $request->param();

        $storeClear = $storeClearBillModel
            ->field('b.pay_price,b.store_id,b.pay_state,s.store_name,a.audit_bank,a.audit_bank_card,u.mobile,b.clear_start_time,b.clear_end_time')
            ->alias('b')
            ->join('new_store s','b.store_id = s.store_id','left')
            ->join('new_store_audit a','b.store_id = a.store_id','left')
            ->join('store_user u','a.admin_id = u.admin_id','left')
            ->where('b.id = ' . $storeClearBill['id'])
            ->find();
        if ($storeClear) {
            $storeClear['audit_bank_card'] = substr_replace($storeClear['audit_bank_card'], '****', 4, 12);
        }

        $where = 'b.store_id = ' . $storeClear->store_id;
        $where .= " and date_format(b.create_time,'%Y-%m-%d') >= date_format('" . $storeClear->clear_start_time. "','%Y-%m-%d')";
        $where .= " and date_format(b.create_time,'%Y-%m-%d') <= date_format('" . $storeClear->clear_end_time. "','%Y-%m-%d')";

        $storeClearList = $storeClearModel
            ->field('b.*,u.user_name,s.store_name')
            ->alias('b')
            ->join('new_users u','b.user_id = u.user_id','left')
            ->join('new_store s','b.store_id = s.store_id','left')
            ->where($where)
            ->select();

        if (!empty($storeClearList)) {
            foreach ($storeClearList as &$order) {
                //实付金额
                $order['buy_price'] = $order['order_price'] - $order['user_voucher_price'] - $order['discount_price'];
            }
        }
        // 模板变量赋值
        $this->assign('storeClear', $storeClear);
        $this->assign('storeClearList', $storeClearList);
        // 模板输出
        return view("Report/store_clear_detail");
    }
    //店铺提现
    public function storeClearAct(StoreClearBillModel $storeClearModel, Request $request)
    {
        $storeClearInfo = $request->param();
        //如果是提交
        $protect = $storeClearModel->where(["store_id"=>$storeClearInfo['store_id']])->find();
        if(!empty($protect)){
            $storeClearInfo['pay_time'] = date('Y-m-d H:i:s',time());
            $upWhere['store_id'] = $storeClearInfo['store_id'];
            $result = $storeClearModel->update($storeClearInfo,$upWhere);
            if($result){
                $this->setAdminUserLog("编辑","编辑店铺提现：id为" . $storeClearInfo['store_id'] );
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $this->error("店铺提现不存在，修改失败");
        }
    }
    //店铺报表
    public function storeReportDay(StoreReportDayModel $storeReportDayModel,  Request $request, NavModel $navModel){
        $storeReportDayModel->data($request->param());
        //获取行业列表
        $navList = $navModel->where('disabled=1')->select();
        //获取分页数
        if (!empty($storeReportDayModel->show_count)){
            $show_count = $storeReportDayModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($storeReportDayModel->nav_id)){
            $where .= " and b.nav_id = '" . $storeReportDayModel->nav_id . "'";
        }
        if(!empty($storeReportDayModel->datemin) && !empty($storeReportDayModel->datemax) && ($storeReportDayModel->datemin > $storeReportDayModel->datemax)){
            $this->error("请正确选择时间");
        }
        if(!empty($storeReportDayModel->datemin)){
            $dateMin = explode('-',$storeReportDayModel->datemin);
            $minYear = $dateMin[0];
            $minMonth = $dateMin[1];
            $minDay = $dateMin[2];
            $where .= " and year = '$minYear' and month = '$minMonth' and day >= '$minDay'";
        }
        if(!empty($storeReportDayModel->datemax)){
            $datemax = explode('-',$storeReportDayModel->datemax);
            $maxYear = $datemax[0];
            $maxMonth = $datemax[1];
            $maxDay = $datemax[2];
            $where .= " and year = '$maxYear' and month = '$maxMonth' and day <= '$maxDay'";
        }

        if(!empty($storeReportDayModel->keywords)){
            $keywords = $storeReportDayModel->keywords;
            $where .= " and s.store_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($storeReportDayModel->orderBy)){
            $orderBy = $storeReportDayModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeReportDayModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeReportDayModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storeReportDay = $storeReportDayModel
            ->field('b.*,n.nav_name,s.store_name')
            ->alias('b')
            ->join('new_nav n','b.nav_id = n.nav_id','left')
            ->join('new_store s','b.store_id = s.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        if (!empty($storeReportDay)) {
            foreach($storeReportDay as &$storeReport){
                $storeReport['day_time'] = $storeReport['year'] . '-' . $storeReport['month'] . '-' . $storeReport['day'];
            }
        }
        // 获取分页显示
        //$page = $storeReportDay->render();
        $parmas = request()->param();
        $page = $storeReportDay->appends($parmas)->render();
        // 模板变量赋值
        $this->assign('storeReportDay', $storeReportDay);
        $this->assign('navList', $navList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $storeReportDayModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('storeReportDay');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Report/store_report_day");
    }

}