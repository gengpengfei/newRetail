<?php
namespace app\shopapi\controller;
/**
    +----------------------------------------------------------
     * @explain 店铺类
    +----------------------------------------------------------
     * @access class
    +----------------------------------------------------------
     * @return Store
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/

use app\api\model\ActivityInfoModel;
use app\api\service\ClientService;
use app\api\service\RewardService;
use app\shopapi\model\ActivityApplyModel;
use app\shopapi\model\LoginModel;
use app\shopapi\model\NavModel;
use app\shopapi\model\StoreAuditModel;
use app\shopapi\model\StoreClearBillModel;
use app\shopapi\model\StoreClearModel;
use app\shopapi\model\StoreCloseLogModel;
use app\shopapi\model\StoreConfigDefaultModel;
use app\shopapi\model\StoreConfigModel;
use app\shopapi\model\StoreOpinionsModel;
use app\shopapi\model\StoreOrderModel;
use app\shopapi\model\StoreCommentModel;
use app\shopapi\model\StoreModel;
use app\shopapi\model\StorePushMessageModel;
use app\shopapi\model\StoreUserModel;
use app\shopapi\model\StoreVoucherAttrModel;
use app\shopapi\model\StoreVoucherModel;
use app\shopapi\model\UsersModel;
use app\shopapi\model\UserVoucherModel;
use app\shopapi\service\LoginService;
use app\shopapi\service\QRcodeService;
use app\shopapi\service\StoreService;
use geohash\Geohash;
use think\Request;

class Store extends Common {
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\GetConfig;

    /*
     * params :
     * explain:认领店铺列表
     * authors:Mr.Geng
     * addTime:2018/3/13 19:11
     */
    public function storeList(Request $request,StoreModel $storeModel,StoreService $storeService)
    {
        $req = $request->param();
        //-- 当前选择的城市id
        $locationData = $request->locationData;
        $city_id = $locationData->city_id;
        if(empty($city_id)){
            $this->jkReturn(-1,'请选择当前城市',' ');
        }
        //-- 当前位置geohash值
        $like_geohash = $storeService->getGeohashLike();
        $where = "city=".$city_id." and disabled=1 and geohash like '".$like_geohash."%'";
        //-- 关键字检索
        if($req['keywords'] ?? 0){
            $where .= " and (LOCATE('".$req['keywords']."', `store_name`)>0 or LOCATE('".$req['keywords']."', `store_desc`)>0) ";
        }
        $store_list = $storeModel
            ->field('store_id,store_name,store_desc,store_img,store_type,store_address,lng,lat,geohash,audit_state,city')
            ->where($where)
            ->select();
        //算出实际距离
        $nearDistance = $this->getConfig('store_near_distance');
        foreach ($store_list as &$v){
            $storeService->getStoreDistance($v);
            //排序列
            if($v->distance>$nearDistance*1000){
                unset($v);
            }
        }
        //距离排序
        $store_list = $store_list->toArray();
        array_multisort(array_column($store_list,'distance'),SORT_ASC,$store_list);
        $this->jkReturn(1,'店铺列表',$store_list);
    }

    /*
     * explain:获取店铺品类列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 15:12
     */
    public function storeCategoryAll(NavModel $navModel)
    {
        $list = $navModel->append("category_list")->select();
        $this->jkReturn('1','品类列表',$list);
    }

    /*
     * explain:创建门店
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 14:51
     */
    public function createStore(Request $request,StoreModel $storeModel,StoreAuditModel $storeAuditModel)
    {
        $param = $request->param();
        $geohash = new Geohash();
        $n_geohash = $geohash->encode($param['lat'],$param['lng']);
        $param['geohash'] = $n_geohash;
        //-- 查询唯一性
        $info = $storeModel
            ->where(['store_name'=>$param['store_name'],'store_address'=>$param['store_address'],'city'=>$param['city']])
            ->find();
        if(!empty($info)){
            $this->jkReturn('-1','该店铺名称和地址已经被使用,请重新确认',[]);
        }
        if($storeModel->allowField(true)->create($param)){
            $id = $storeModel->getLastInsID();
            if(!$storeAuditModel->allowField(true)->create(['store_id'=>$id,'admin_id'=>$param['admin_id']])){
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            $id = $storeAuditModel->getLastInsID();
            $this->jkReturn('1','店铺认领成功',$id);
        }
        $this->jkReturn('-1','创建店铺失败',[]);
    }
    
    /*
     * explain:认领店铺
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 9:52
     */
    public function claimStore(Request $request,StoreAuditModel $storeAuditModel)
    {
        $param = $request->param();
        //-- 判断是否有认领店铺
        $info = $storeAuditModel
            ->where("admin_id={$param['admin_id']}")
            ->find();
        if(!empty($info)){
            if($info->audit_state === 'null' ){
                $storeAuditModel->where("admin_id={$param['admin_id']} and audit_state is NULL")->delete();
            }else{
                $this->jkReturn('-1','您已经有认领店铺,不能重新认领',[]);
            }
        }
        if(!$storeAuditModel->allowField(true)->create($param)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $id = $storeAuditModel->getLastInsID();
        $this->jkReturn('1','店铺认领成功',$id);
    }
    
    /*
     * explain:店铺提交资质
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 16:27
     */
    public function setStoreAudit(Request $request,StoreAuditModel $storeAuditModel,LoginModel $loginModel,LoginService $loginService)
    {
        $param = $request->param();
        //获取该手机号该类验证码
        $where['mobile'] = $param['audit_mobile'];
        $where['code_type'] = $param['code_type'];
        $codeList = $loginModel->where($where)->find();
        if(!empty($codeList->id)){
            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证码是否匹配
            $loginService->checkMobileCodeMate($codeList->code,$param['code']);
            $param['audit_identity_face'] = urldecode($param['audit_identity_face']);
            $param['audit_identity_coin'] = urldecode($param['audit_identity_coin']);
            $param['audit_license'] = urldecode($param['audit_license']);
            $param['audit_state'] = 0;
            $param['temp_license']= urldecode($param['temp_license']);
            if($storeAuditModel->allowField(true)->update($param,['store_id'=>$param['store_id'],'admin_id'=>$param['admin_id']])){
                //验证通过删除验证码
                $loginModel->where($where)->delete();
                $this->jkReturn('1','提交资质成功,客服会尽快处理,请耐心等候',[]);
            }
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }else{
            //验证码不存在
            $this->jkReturn('-1','短信验证码已过期，请重新获取',[]);
        }
    }

    /*
     * explain:门店资质详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 17:56
     */
    public function storeAuditInfo(Request $request,StoreAuditModel $storeAuditModel)
    {
        $param = $request->param();
        $info = $storeAuditModel->where(['admin_id'=>$param['admin_id']])->find();
        $this->jkReturn('1','资质审核详情',$info??[]);
    }

    /*
     * explain:店铺资质编辑
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 18:01
     */
    public function editStoreAudit(Request $request,StoreAuditModel $storeAuditModel,StoreModel $storeModel)
    {
        $param = $request->param();
        //-- 资质详情
        $info = $storeAuditModel->where(['admin_id'=>$param['admin_id']])->find();
        $storeAuditModel->startTrans();
        if(!empty($param['audit_identity_face'])){
            $param['audit_identity_face'] = urldecode($param['audit_identity_face']);
        }
        if(!empty($param['audit_identity_coin'])){
            $param['audit_identity_coin'] = urldecode($param['audit_identity_face']);
        }
        if(!empty($param['audit_license'])){
            $param['audit_license'] = urldecode($param['audit_license']);
        }
        $param['audit_state'] = 0;
        if(!$storeAuditModel->allowField(true)->update($param,['id'=>$info->id])){
            $storeAuditModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 改变店铺状态
        if(!$storeModel->update(['audit_state'=>0],['store_id'=>$info->store_id])){
            $storeAuditModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $storeAuditModel->commit();
        $this->jkReturn('1','编辑资质成功,客服会尽快处理,请耐心等候',[]);
    }

    /*
     * params :@store_id 店铺id
     * explain:店铺详情
     * authors:Mr.Geng
     * addTime:2018/3/14 11:50
     */
    public function storeDetail(Request $request,StoreModel $storeModel,StoreCommentModel $storeCommentModel)
    {
        $param = $request->param();
        //-- 获取会员认证的店铺
        $storeInfo = $storeModel
            ->alias('s')
            ->field('s.*,c.nav_name')
            ->where(["store_id"=>$param['store_id']])
            ->join('new_nav c','c.nav_id=s.nav_id','left')
            ->find();
        !$storeInfo && $this->jkReturn(-1,"参数错误",[]);
        //-- 好评率
        $com1 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>1])->count();
        $com2 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>2])->count();
        $com3 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>3])->count();
        $com4 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>4])->count();
        $all = $com1+$com2+$com3+$com4;
        if($all === 0){
            $avg = '100%';
        }else{
            $avg = sprintf('%.1f',($com3+$com4)*100/$all)."%";
        }
        $storeInfo->comment_ok_rate = $avg;
        $this->jkReturn(1,"店铺详情",$storeInfo);
    }

    /*
     * explain:店铺关店申请
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 13:59
     */
    public function storeClose(Request $request,StoreModel $storeModel,StoreUserModel $storeUserModel,StoreCloseLogModel $closeLogModel)
    {
        $param = $request->param();
        $adminUser = $storeUserModel->where('is_boss=1 and admin_id='.$param['admin_id'])->find();
        if(empty($adminUser)){
            $this->jkReturn('-1','您的权限不足,只有店铺主可以关闭店铺',[]);
        }
        $info = $closeLogModel->where(['store_id'=>$adminUser->store_id])->find();
        if(empty($info)){
            //-- 店铺信息
            $storeInfo  = $storeModel->where(['store_id'=>$adminUser->store_id])->find();
            $storeInfo->close_img = urldecode($param['close_img']);
            $storeInfo->close_reason = $param['close_reason'];
            if(!$closeLogModel->allowField(true)->save($storeInfo->toArray())){
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }else{
            $param['close_state'] = 0;
            if(!empty($param['close_img'])){
                $param['close_img'] = urldecode($param['close_img']);
            }
            if(!$closeLogModel->allowField(true)->update($param,['id'=>$info->id])){
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        $this->jkReturn('1','提交成功,请耐心等候客服审核',[]);
    }

    /*
     * explain:查看关店申请
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 14:20
     */
    public function storeCloseInfo(Request $request,StoreUserModel $storeUserModel,StoreCloseLogModel $closeLogModel)
    {
        $param = $request->param();
        $adminUser = $storeUserModel->where('is_boss=1 and admin_id='.$param['admin_id'])->find();
        if(empty($adminUser)){
            $this->jkReturn('-1','您的权限不足,只有店铺主可以关闭店铺',[]);
        }
        //-- 审核进度信息
        $info = $closeLogModel->where(['store_id'=>$adminUser->store_id])->find();
        $this->jkReturn('1','审核进度',$info);
    }

    /*
     * explain:取消关店申请
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 14:20
     */
    public function storeCloseCancel(Request $request,StoreModel $storeModel,StoreUserModel $storeUserModel,StoreCloseLogModel $closeLogModel)
    {
        $param = $request->param();
        $adminUser = $storeUserModel->where('is_boss=1 and admin_id='.$param['admin_id'])->find();
        if(empty($adminUser)){
            $this->jkReturn('-1','您的权限不足,只有店铺主可以关闭店铺',[]);
        }
        //-- 审核进度信息
        if(!$closeLogModel->update(['close_state'=>3],['store_id'=>$adminUser->store_id])){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $this->jkReturn('1','取消成功',[]);
    }

    /*
     * explain:编辑店铺信息
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 15:14
     */
    public function editStore(Request $request,StoreModel $storeModel)
    {
        $param = $request->param();
        if(!$storeModel->allowField('store_name,store_address,store_info,store_hours,disabled')->save($param,['store_id'=>$param['store_id']])){
            $this->jkReturn('-1','编辑失败',[]);
        }
        $this->jkReturn('1','编辑成功',[]);
    }


    /*
     * params :@store_id 店铺id @limit @page @voucher_type
     * explain:店铺抵扣券
     * authors:Mr.Geng
     * addTime:2018/3/15 11:40
     */
    public function storeVoucherList(Request $request,StoreVoucherModel $storeVoucherModel)
    {
        $param = $request->param();
        $time = $this->getTime();
        $where = "store_id = {$param['store_id']} ";
        if($request->has('voucher_type')){
            $where .= " and voucher_type={$param['voucher_type']} ";
        }
        if($param['is_invalid']==1){
            $where .= " and disabled=1 and sell_start_date<'".$time."' and '".$time."'<sell_end_date and '".$time."'<use_end_date";
        }else{
            $where .= " and (disabled=1 and '".$time."'>sell_end_date )";
        }
        $voucherList = $storeVoucherModel->where( $where)->select();
        $this->jkReturn(1,"优惠券列表",$voucherList);
    }

    /*
     * explain:店铺抵扣券详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 14:36
     */
    public function storeVoucherInfo(Request $request,StoreVoucherModel $storeVoucherModel,StoreVoucherAttrModel $storeVoucherAttrModel)
    {
        $voucherId = $request->param('voucher_id');
        $storeId = $request->param('store_id');
        $voucherInfo = $storeVoucherModel
            ->where("voucher_id = $voucherId and store_id=$storeId")
            ->find();
        if(empty($voucherInfo)){
            $this->jkReturn(-1,'该商品已下架!');
        }
        $voucherAttr = $storeVoucherAttrModel
            ->alias('a')
            ->field('a.*,r.attr_name')
            ->join('new_store_attr_rule r','r.attr_rule_id=a.attr_rule_id','LEFT')
            ->where(['voucher_id'=>$voucherId,'store_id'=>$storeId])
            ->select();
        $data = [
            'voucher_info'=>$voucherInfo,
            'voucher_attr'=>$voucherAttr
        ];
        $this->jkReturn(1,"店铺抵扣券详情",$data);
    }

    /*
    * explain:店铺核销记录列表
    * params :
    * authors:Mr.Geng
    * addTime:2018/4/23 16:48
    */
    public function storeOrderList(Request $request,StoreClearModel $storeClearModel)
    {
        $param = $request->param();
        $time = $this->getTimeToday();
        $param['start_time'] = $param['start_time']??$time;
        $where = 'c.store_id='.$param['store_id'].' and c.order_type="'.$param['order_type'].'"';
        $where .= " and c.create_time> '".$param['start_time']."'";
        if($param['end_time']){
            $where .= " and c.create_time < '".$param['end_time']."'";
        }
       $orderList = $storeClearModel
            ->alias('c')
            ->field('c.*,u.user_name,u.head_img,v.voucher_name,v.voucher_sn')
            ->where($where)
            ->join('new_user_voucher v','v.user_voucher_id=c.user_voucher_id','left')
            ->join('new_users u','u.user_id=c.user_id','left')
            ->order('c.create_time','desc')
            ->select();
        $orderAmount = $storeClearModel->alias('c')->where($where)->sum('clear_price');
        $orderNum = $storeClearModel->alias('c')->where($where)->count();
        $this->jkReturn(1,'店铺订单列表',['order_list'=>$orderList,'order_amount'=>$orderAmount,'order_num'=>$orderNum]);
    }
    /*
     * explain:店铺核销记录详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/8 16:00
     */
    public function storeOrderInfo(Request $request,StoreClearModel $storeClearModel)
    {
        $param = $request->param();
        $orderList = $storeClearModel
            ->alias('c')
            ->field('c.*,v.voucher_img,v.voucher_price,v.voucher_name,v.voucher_amount,v.coupons_price,v.buy_price,v.used_time,u.user_name,u.head_img,s.store_name,s.store_img,c.create_time')
            ->where("id=".$param['id'])
            ->join('new_user_voucher v','v.voucher_sn=c.voucher_sn','left')
            ->join('new_store s','s.store_id=c.store_id','left')
            ->join('new_users u','u.user_id=c.user_id','left')
            ->order('c.create_time','desc')
            ->select();
        $this->jkReturn(1,'店铺订单详情',['order_list'=>$orderList]);
    }

    /*
     * explain:生成店铺二维码数据
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/6 10:13
     */
    public function getStoreSignCode(Request $request)
    {
        $param = $request->param();
        $codeInfo = $this->authCode($param['store_id'],'ENCODE','shike');
        $data = [
            'code'=>$codeInfo,
            'type'=>1,
            'sign'=>'9ef72e6748a99be8ae83'
        ];
        $this->jkReturn('1','店铺二维码',$data);
    }

    /*
     * explain:店铺二维码下载
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/12 13:59
     */
    public function storeQrcodeImg(Request $request,QRcodeService $QRcodeService)
    {
        $qrData = $request->param('data');
        $savePath = APP_PATH . '/../Public/images/storeQRcode/';
        if($filename = $QRcodeService->createQRcode($savePath,urldecode($qrData),'H','8')){
            $pic = $this->getConfig('base_url').'/images/storeQRcode/'.$filename;
            $this->jkReturn('1','下载成功',$pic);
        }else{
            $this->jkReturn('-1','网络延时,请稍后重试,!',[]);
        }
    }
    /*
     * explain:核销券码
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/5 18:22
     */
    public function checkUsedVoucher(Request $request,UserVoucherModel $userVoucherModel,StoreOrderModel $storeOrderModel,StoreClearModel $storeClearModel,StoreVoucherModel $storeVoucherModel,StorePushMessageModel $storePushMessageModel,UsersModel $usersModel)
    {
        $param = $request->param();
        if(!empty($param['code'])){
            $voucherSn = $this->authCode($param['code'],'DECODE','shike');
        }else{
            $voucherSn = trim($param['voucher_sn']);
        }
        if(empty($voucherSn)){
            $this->jkReturn('-1','券码已过期或不存在');
        }
        $time = $time = $this->getTime();
        //-- 获取待核销的抵用券
        $userVoucherInfo = $userVoucherModel
            ->append('refund_state')
            ->where("voucher_sn='$voucherSn' and store_id={$param['store_id']} and used_state='C02' and use_end_date>'".$time."' and '".$time."'>use_start_date")
            ->find();
        if(empty($userVoucherInfo)){
            $this->jkReturn('-1','该券不可使用,请重新核对',[]);
        }
        //-- 开启事物
        $userVoucherModel->startTrans();
        //-- 改变抵用券状态
        $userVoucherId = $userVoucherInfo->user_voucher_id;
        if(!$userVoucherModel->save(['used_state'=>'C03','used_time'=>$time],['user_voucher_id'=>$userVoucherId])){
            $userVoucherModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 增加优惠券使用数量
        if(!$storeVoucherModel->where(['voucher_id'=>$userVoucherInfo->voucher_id])->setInc('used_num',1)){
            $userVoucherModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $orderInfo = $storeOrderModel->where("order_sn='$userVoucherInfo->order_sn'")->find();
        //-- 添加店铺结算表
        $clearData = [
            'order_id'=>$orderInfo->order_id,
            'order_sn'=>$orderInfo->order_sn,
            'voucher_sn'=>$voucherSn,
            'user_id'=>$userVoucherInfo->user_id,
            'order_type'=>$orderInfo->order_type,
            'store_id'=>$orderInfo->store_id,
            'pay_type'=>$orderInfo->pay_type,
            'order_price'=>$userVoucherInfo->voucher_price,
            'clear_price'=>sprintf('%.2f',($userVoucherInfo->buy_price+$userVoucherInfo->coupons_price)),
            'discount_price'=>sprintf('%.2f',$userVoucherInfo->coupons_price),
            'clear_desc'=>'抵用券核销:'.$userVoucherInfo->voucher_sn,
            'clear_state'=>0
        ];
        if($orderInfo->voucher_type==1){
            if(!$storeClearModel->allowField(true)->create($clearData)){
                $userVoucherModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        //-- 判断是否改变订单状态(按状态优先级判断)
        $orderState = 'T05';
        if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C05'")->find()){
            $orderState = 'T04';
        }
        if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C04'")->find()){
            $orderState = 'T04';
        }
        if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C03'")->find()){
            $orderState = 'T03';
        }
        if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C02'")->find()){
            $orderState = 'T02';
        }
        if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C01'")->find()){
            $orderState = 'T01';
        }
        if(!$storeOrderModel->update(['order_state'=>$orderState],['order_id'=>$orderInfo->order_id])){
            $userVoucherModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 消息推送
        $userInfo = $usersModel->where(['user_id'=>$orderInfo->user_id])->find();
        $info = [
            'user_name'=>$userInfo->user_name ,
            'head_img'=>$userInfo->head_img,
            'clear_price'=>$clearData['clear_price'],
            'clear_time'=>$this->getTime(),
            'clear_desc'=>$clearData['clear_desc'],
            'order_sn'=>$clearData['order_sn']
        ];
        $data = [
            'store_id'=>$orderInfo->store_id,
            'message_type'=>2,
            'message_cont'=>"券号:".$voucherSn."已核销",
            'message_data'=>json_encode($info,JSON_UNESCAPED_UNICODE)
        ];
        if(!$storePushMessageModel->allowField(true)->create($data)){
            $userVoucherModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //极光推送
        $clientService = new ClientService();
        $receiver['alias'] = array('store'.$orderInfo->store_id);//接收者
        $data['id'] = $storePushMessageModel->getLastInsID();
        $data['create_time'] = $this->getTime();
        $clientService->push($data['message_cont'],$receiver,'核销消息',json_encode($data));

        $rewardService = new RewardService();
        $rewardService->giveUserReward($orderInfo->user_id,$orderInfo->voucher_price);

        $userVoucherModel->commit();
        $this->jkReturn('1','核销成功',[]);
    }

    /*
     * explain:店铺首页订单统计
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/6 16:12
     */
    public function storeOrderReport(Request $request,StoreClearModel $storeClearModel)
    {
        $param = $request->param();
        $time = $this->getTimeToday();
        $param['start_time'] = $param['start_time']??$time;
        $where = 'store_id='.$param['store_id'];
        $where .= " and create_time> '".$param['start_time']."'";
        if($param['end_time']){
            $where .= " and create_time < '".$param['end_time']."'";
        }
        //-- 线上订单数
        $validOrder = $storeClearModel->where($where." and order_type=0 and clear_price>0")->count();
        //--线上订单金额
        $validOrderAmount = $storeClearModel->where($where." and order_type=0 and clear_price>0")->sum('clear_price');
        //-- 线下订单数
        $offlineOrder = $storeClearModel->where($where." and order_type=1 and clear_price>0")->count();
        //--线下订单金额
        $offlineOrderAmount = $storeClearModel->where($where." and order_type=1 and clear_price>0")->sum('clear_price');
        //-- 补贴金额
        $discountPrice = $storeClearModel->where($where)->sum('discount_price');
        //-- 退款金额
        $refundPrice = $storeClearModel->where($where." and clear_price<0 ")->sum('discount_price');
        $data = [
            'valid_order'=>$validOrder,
            'valid_order_amount'=>$validOrderAmount,
            'offline_order'=>$offlineOrder,
            'offline_order_amount'=>$offlineOrderAmount,
            'discount_price'=>$discountPrice,
            'refund_price'=>$refundPrice,
        ];
        $this->jkReturn(1,'店铺订单统计',$data);
    }


    /*
     * explain:店铺提现列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 14:36
     */
    public function storeClearBillList(Request $request,StoreClearBillModel $storeClearBillModel)
    {
        $storeId = $request->param('store_id');
        $payState = $request->param('pay_state');
        $storeClearBill = $storeClearBillModel
            ->where("store_id = $storeId and pay_state=$payState")
            ->select();
        $this->jkReturn(1,"店铺账单列表",$storeClearBill);
    }

    /*
     * explain:店铺账单详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/7 18:16
     */
    public function storeClearBillInfo(Request $request,StoreClearBillModel $storeClearBillModel,StoreClearModel $storeClearModel)
    {
        $id = $request->param('id');
        $billInfo = $storeClearBillModel->where('id='.$id)->find();
        //-- 根据账单结算起始日期 , 计算每日详情
        $rang = 60*60*24;
        $timeArray = $this->splitTime($billInfo->clear_start_time,$billInfo->clear_end_time,$rang,'Y-m-d H:i:s');
        foreach ($timeArray as $key=>$v){
            $amount = $storeClearModel
                ->where("store_id=$billInfo->store_id and DATE_FORMAT(`create_time`,'%Y-%m-%d')=DATE_FORMAT('".$v."','%Y-%m-%d')
")
                ->sum('clear_price');

            $weekarray=array("日","一","二","三","四","五","六");
            $list[$key]['amount'] = $amount;
            $list[$key]['day_time'] = $v;
            $list[$key]['week'] = "星期".$weekarray[date("w",strtotime($v))];
        }
        $this->jkReturn('1','账单详情',['bill_info'=>$billInfo,'bill_day_list'=>$list]);
    }
    
    /*
     * explain:店铺意见提交
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/8 11:43
     */
    public function storeOpinionSub(Request $request,StoreOpinionsModel $storeOpinionsModel)
    {
        $param = $request->param();
        if(empty($param['opinion_img'])){
            $param['opinion_img'] = urldecode($param['opinion_img']);
        }
        if(!$storeOpinionsModel->allowField(true)->save($param)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $this->jkReturn('1','意见提交成功,感谢您的意见!',[]);
    }

    /*
     * explain:活动申请
     * addTime:2018/6/8 12:00
     */
    public function activityApply(Request $request,ActivityApplyModel $activityApplyModel, ActivityInfoModel $activityInfoModel)
    {
        $param = $request->param();
        //-- 查询唯一性
        $info = $activityApplyModel
            ->where(['activity_list_id'=>$param['activity_list_id'],'store_id'=>$param['store_id']])
            ->find();
        $activityInfo = $activityInfoModel
            ->where(['activity_list_id'=>$param['activity_list_id'],'store_id'=>$param['store_id']])
            ->find();
        if(!empty($info) || !empty($activityInfo)){
            $this->jkReturn('-1','该活动已被申请过,请重新确认',[]);
        }
        if($activityApplyModel->create($param)){
            $id = $activityApplyModel->getLastInsID();
            $this->jkReturn('1','活动申请成功',$id);
        }
        $this->jkReturn('-1','活动申请失败',[]);
    }

    /*
     * explain:店铺配置列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/8 16:39
     */
    public function storeDefaultConfigList(Request $request ,StoreConfigDefaultModel $storeConfigDefaultModel,StoreConfigModel $storeConfigModel)
    {
        $param = $request->param();
        $defaultList = $storeConfigDefaultModel->select()->toArray();
        $storeConfigList = $storeConfigModel->field('code,desc,value,name,'.$param['store_id'].' as store_id')->where("store_id={$param['store_id']}")->select()->toArray();
        //-- 取差集
        $diff = array_diff(array_column($defaultList,'code'),array_column($storeConfigList,'code'));
        $inStr = "'".str_replace(",","','",join(",",$diff))."'";
        $diffList = $storeConfigDefaultModel
            ->field('code,desc,value,name,'.$param['store_id'].' as store_id')
            ->where("code in ($inStr)")
            ->select()
            ->toArray();
        //-- 插入店铺配置表
        if(!$storeConfigModel->allowField(true)->saveAll($diffList)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 重新获取店铺配置
        $storeConfigList = $storeConfigModel->where("store_id={$param['store_id']}")->select()->toArray();
        $this->jkReturn('1','店铺配置',$storeConfigList);
    }

    /*
     * explain:店铺消息配置
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/8 14:16
     */
    public function storeSetConfig(Request $request,StoreConfigModel $storeConfigModel)
    {
        $param = $request->param();
        $configDate = $param['config_data'];
        if(!$storeConfigModel->allowField(true)->saveAll($configDate,true)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $this->jkReturn('1','设置成功',[]);
    }
}