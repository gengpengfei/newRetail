<?php
namespace app\api\controller;

use app\api\model\AgreementRuleModel;
use app\api\model\LoginModel;
use app\api\model\OrderModel;
use app\api\model\OrderProModel;
use app\api\model\ProCommentModel;
use app\api\model\RankModel;
use app\api\model\RegionModel;
use app\api\model\StoreCollectModel;
use app\api\model\StoreCommentCollectModel;
use app\api\model\StoreCommentModel;
use app\api\model\StoreDiscountRuleModel;
use app\api\model\StoreModel;
use app\api\model\StoreOrderModel;
use app\api\model\StoreProLikeModel;
use app\api\model\StoreProModel;
use app\api\model\StoreVoucherCollectModel;
use app\api\model\UserCouponsModel;
use app\api\model\UserAddressModel;
use app\api\model\UserMoneyLogModel;
use app\api\model\UserRewardModel;
use app\api\model\UserRechargeModel;
use app\api\model\UserRechargeRuleModel;
use app\api\model\UsersModel;
use app\api\model\UserScoreLogModel;
use app\api\model\UserTraceModel;
use app\api\model\UserVoucherModel;
use app\api\model\SystemConfigModel;
use app\api\service\LoginService;
use app\api\service\StoreService;
use think\Request;

/**
    +----------------------------------------------------------
     * @explain 用户类
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
class User extends Common {
    use \app\api\traits\BuildParam;
    /*
     * explain:用户个人信息
     * params :@user_id
     * authors:Mr.Geng
     * addTime:2018/3/23 14:18
     */
    public function information(Request $request,UsersModel $usersModel) {
        $user_id = $request->param('user_id');
        $userInfo = $usersModel
            ->alias('u')
            ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
            ->where(['user_id'=>$user_id])
            ->join('new_rank r','r.rank_id=u.rank_id','left')
            ->find();
        $save_money_ranking = $usersModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
        $all = $usersModel->group('user_save_money')->count();
        $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
        $this->jkReturn(1,'用户个人信息',$userInfo);
    }

    /*
     * explain:修改个人信息
     * params :@user_id ...
     * authors:Mr.Geng
     * addTime:2018/3/23 14:34
     */
    public function saveInformation(Request $request,UsersModel $usersModel)
    {
        $param = $request->param();
        if(!empty($param['head_img'])){
            $param['head_img'] = urldecode($param['head_img']);
        }
        if(!empty($param['invitation_code'])){
            //-- 查询用户是否已经更新过被邀请码
            $info = $usersModel->where(['user_id'=>$param['user_id']])->find();
            if(!empty($info->invitation_code)) $this->jkReturn('-1','您已经填写过邀请码，不能重复提交',[]);
        }
        $res = $usersModel->allowField(true)->save($param,['user_id'=>$param['user_id']]);
        if(!$res)
            $this->jkReturn(-1,'修改失败','1');
        $userInfo = $usersModel->where('user_id',$param['user_id'])->find();
        $this->jkReturn(1,'修改成功',$userInfo);
    }

    /*
     * explain:添加用户地址
     * params :@user_id @user_name @address @mobile @province @city @district
     * authors:Mr.Geng
     * addTime:2018/3/26 10:04
     */
    public function addAddress(Request $request,UserAddressModel $userAddressModel,UsersModel $usersModel)
    {
        $param = $request->param();
        if(empty($param['user_id']) || empty($param['user_name'])||empty($param['address'])||empty($param['mobile'])||empty($param['province'])||empty($param['city'])||empty($param['district'])){
            $this->jkReturn('-1','请填写完整的地址信息',$param);
        }
        $userAddressModel->startTrans();
        if(!$userAddressModel->allowField(true)->save($param)){
            $userAddressModel->rollback();
            $this->jkReturn('-1','添加地址失败',[]);
        }
        if(!empty($param['is_default'])){
            $addressId = $userAddressModel->getLastInsID();
            if(!$usersModel->update(['address_id'=>$addressId],['user_id'=>$param['user_id']])){
                $userAddressModel->rollback();
                $this->jkReturn('-1','添加地址失败',[]);
            }
        }
        $userAddressModel->commit();
        $this->jkReturn('1','添加地址成功','');
    }
    
    /*
     * explain:更新用户地址
     * params :@address_id @user_id @user_name @address @mobile @province @city @district
     * authors:Mr.Geng
     * addTime:2018/3/23 17:49
     */
    public function updateAddress(Request $request,UserAddressModel $userAddressModel)
    {
        $param = $request->param();
        if(empty($param['address_id'])){
            $this->jkReturn('-1','缺少参数',$param);
        }
        //-- 如果省市区有变动 , 则手动调用自动完成函数
        if(!empty($param['district'])){
            $userAddressModel->address_name = $userAddressModel->setAddressNameAttr();
        }
        $userAddressModel->allowField(true)->save($param,['id' => $param['address_id']]);
        $this->jkReturn('1','更新地址成功','');
    }

    /*
     * explain:删除用户地址
     * params :@address_id
     * authors:Mr.Geng
     * addTime:2018/3/26 10:53
     */
    public function delAddress(Request $request,UserAddressModel $userAddressModel)
    {
        $addressId = $request->param('address_id')??0;
        $userAddressModel->where('address_id',$addressId)->delete();
        $this->jkReturn('1','地址删除成功','');
    }

    /*
     * explain:用户地址列表
     * params :@user_id
     * authors:Mr.Geng
     * addTime:2018/3/23 14:36
     */
    public function addressList(Request $request,UserAddressModel $userAddressModel) {
        $user_id = $request->param('user_id');
        $addressList = $userAddressModel->where('user_id',$user_id)->select();
        $this->jkReturn(1,'地址列表',$addressList);
    }

    /*
     * explain:用户地址详情
     * params :@user_id @address_id
     * authors:Mr.Geng
     * addTime:2018/3/23 17:45
     */
    public function addressInfo(Request $request,UserAddressModel $userAddressModel) {
        $addressId = $request->param('address_id');
        $addressInfo = $userAddressModel->where(['address_id'=>$addressId])->find();
        $this->jkReturn(1,"用户地址详情",$addressInfo);
    }

    /*
     * explain:用户设置默认地址
     * params :@user_id @address_id
     * authors:Mr.Geng
     * addTime:2018/3/26 11:01
     */
    public function setDefaultAddress(Request $request,UsersModel $usersModel)
    {
        $param = $request->param();
        $usersModel->update(['address_id'=>$param['address_id']],['user_id' => $param['user_id']]);
        $this->jkReturn('1','设置默认地址成功','');
    }

    /*
     * explain:用户收藏店铺
     * params :@store_id @user_id
     * authors:Mr.Geng
     * addTime:2018/3/22 13:56
     */
    public function storeCollect(Request $request,StoreCollectModel $storeCollectModel)
    {
        $store_id = $request->param('store_id');
        $res = $storeCollectModel->where(['user_id'=>$request->param('user_id'),'store_id'=>$store_id])->find();
        if(empty($res)){
            $storeCollectModel->save(['user_id'=>$request->param('user_id'),'store_id'=>$store_id]);
            $this->jkReturn(1,'您已收藏该店铺','');
        }else{
            $storeCollectModel->where('id',$res->id)->delete();
            $this->jkReturn(1,'您已取消收藏该店铺!','');
        }
    }

    /*
     * explain:用户收藏的店铺列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 11:08
     */
    public function storeCollectList(Request $request,StoreCollectModel $storeCollectModel,StoreService $storeService)
    {
        $storeList = $storeCollectModel
            ->field(['s.lat,s.lng,s.store_hot,s.store_id','store_name','store_desc','store_phone','store_img','store_address','n.nav_name'])
            ->where('new_store_collect.user_id',$request->param('user_id'))
            ->join('new_store s','s.store_id=new_store_collect.store_id','left')
            ->join('new_nav n','n.nav_id=s.nav_id','left')
            ->select();
        foreach ($storeList as &$v){
            $storeService->getStoreDistance($v);
        }
        $this->jkReturn(1,'店铺收藏列表',$storeList);
    }

    /*
     * explain:用户收藏店铺抵用券
     * params :@store_id @user_id
     * authors:Mr.Geng
     * addTime:2018/3/22 13:56
     */
    public function storeVoucherCollect(Request $request,StoreVoucherCollectModel $storeVoucherCollectModel)
    {
        $voucher_id = $request->param('voucher_id');
        $res = $storeVoucherCollectModel->where(['user_id'=>$request->param('user_id'),'voucher_id'=>$voucher_id])->find();
        if(empty($res)){
            $storeVoucherCollectModel->save(['user_id'=>$request->param('user_id'),'voucher_id'=>$voucher_id]);
            $this->jkReturn(1,'您已收藏该优惠券','');
        }else{
            $storeVoucherCollectModel->where('id',$res->id)->delete();
            $this->jkReturn(1,'您已取消收藏该优惠券!','');
        }
    }

    /*
     * explain:用户收藏店铺抵用券列表
     * params :@user_id @voucher_id
     * authors:Mr.Geng
     * addTime:2018/3/28 11:28
     */
    public function voucherCollectList(Request $request,StoreVoucherCollectModel $storeVoucherCollectModel)
    {
        $storeList = $storeVoucherCollectModel
            ->field('s.*')
            ->where(['new_store_voucher_collect.user_id'=>$request->param('user_id'),'voucher_type'=>1])
            ->join('new_store_voucher s','s.voucher_id=new_store_voucher_collect.voucher_id','left')
            ->select();
        $this->jkReturn(1,'店铺抵用券收藏列表',$storeList);
    }
    /*
     * explain:店铺商品点赞
     * params :@store_pro_id @user_id
     * authors:Mr.Geng
     * addTime:2018/3/22 13:56
     */
    public function storeProLike(Request $request ,StoreProModel $storeProModel,StoreProLikeModel $storeProLikeModel)
    {
        $store_pro_id = $request->param('store_pro_id');
        $res = $storeProLikeModel->where(['user_id'=>$request->param('user_id'),'store_pro_id'=>$store_pro_id])->find();
        if(empty($res)){
            $storeProLikeModel->save(['user_id'=>$request->param('user_id'),'store_pro_id'=>$store_pro_id]);
            $storeProModel->where(['store_pro_id'=>$store_pro_id])->setInc('store_pro_like',1);
            $this->jkReturn(1,'成功点赞','');
        }else{
            $storeProLikeModel->where('id',$res->id)->delete();
            $storeProModel->where(['store_pro_id'=>$store_pro_id])->setDec('store_pro_like',1);
            $this->jkReturn(1,'取消点赞','');
        }
    }

    /*
     * explain:用户优惠券列表
     * params :@user_id
     * authors:Mr.Geng
     * addTime:2018/3/26 11:40
     */
    public function userCoupons(Request $request,UserCouponsModel $userCouponsModel)
    {
        $user_id = $request->param('user_id');
        $used_state = $request->param('used_state');
        $where = "user_id=$user_id ";
        $time = $this->getTime();
        if($used_state == 1){
            $where .= " and used_state <> 'C04' and  used_state <> 'C03' and use_end_date>'".$time."' and '".$time."'>use_start_date";
        }else{
            $where .= " and (used_state = 'C04' or used_state='C03' or use_end_date<'".$time."')";
        }
        $userCouponsList = $userCouponsModel->where($where)->order('create_time','desc')->select();
        $this->jkReturn(1,'优惠券列表',$userCouponsList);
    }
    
    /*
     * explain:用户抵用券列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 11:51
     */
    public function userVoucher(Request $request,UserVoucherModel $userVoucherModel,StoreService $storeService)
    {
        $user_id = $request->param('user_id');
        $time = $this->getTime();
        $used_state = $request->param('used_state');
        $where = "user_id=$user_id";
        if($used_state == 1){
            $where .= " and used_state <> 'C04' and  used_state <> 'C03' and use_end_date>'".$time."' and '".$time."'>use_start_date";
        }else{
            $where .= " and (used_state = 'C04' or used_state='C03' or use_end_date<'".$time."')";
        }
        $userVoucherList = $userVoucherModel
            ->alias('u')
            ->field('u.*,s.store_name,s.lat,s.lng')
            ->where($where)
            ->join('new_store s','s.store_id=u.store_id','left')
            ->order('create_time','desc')
            ->select();
        //算出实际距离
        foreach ($userVoucherList as &$v){
            $storeService->getStoreDistance($v);
        }
        $this->jkReturn(1,'抵用券列表',$userVoucherList);
    }

    /*
     * explain:判断订单是否下单完成
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/30 16:35
     */
    public function checkOrderSn(Request $request,StoreOrderModel $storeOrderModel)
    {
        $param = $request->param();
        $orderSn = $param['order_sn']??0;
        $orderInfo = $storeOrderModel->where("order_sn=$orderSn")->find();
        if(empty($orderInfo))
            $this->jkReturn(-1,'正在下单中');
        $this->jkReturn(1,'订单详情',$orderInfo);
    }

    /*
      * explain:用户积分日志
      * params :@user_id
      * authors:Mr.Geng
      * addTime:2018/3/26 11:22
      */
    public function userScoreLog(Request $request,UserScoreLogModel $userScoreLogModel)
    {
        $user_id = $request->param('user_id');
        $scoreLogAll = $userScoreLogModel->where('user_id',$user_id)->order('create_time','desc')->select();
        $scoreLogAdd = $userScoreLogModel->where("user_id=$user_id and score>0")->order('create_time','desc')->select();
        $scoreLogDel = $userScoreLogModel->where("user_id=$user_id and score<0")->order('create_time','desc')->select();
        $result = [
            'score_log_all'=>$scoreLogAll,
            'score_log_add'=>$scoreLogAdd,
            'score_log_del'=>$scoreLogDel,
            'user_score'=>$request->user->user_score
        ];
        $this->jkReturn(1,"用户积分日志",$result);
    }

    /*
      * explain:用户金额日志
      * params :@user_id
      * authors:Mr.Geng
      * addTime:2018/3/26 11:22
      */
    public function userMoneyLog(Request $request,UserMoneyLogModel $userMoneyLogModel)
    {
        $param = $request->param();
        $user_id = $param['user_id'];
        if(isset($param['type'])){
            $addWhere = ' and type='.$param['type'];
        }
        $moneyLogAll = $userMoneyLogModel->where("user_id=$user_id $addWhere")->order('create_time','desc')->select();
        $moneyLogAdd = $userMoneyLogModel->where("user_id=$user_id and money>0 $addWhere")->order('create_time','desc')->select();
        $moneyLogDel = $userMoneyLogModel->where("user_id=$user_id and money<0 $addWhere")->order('create_time','desc')->select();
        $result = [
            'money_log_all'=>$moneyLogAll,
            'money_log_add'=>$moneyLogAdd,
            'money_log_del'=>$moneyLogDel,
            'user_money'=>$request->user->user_money
        ];
        $this->jkReturn(1,"用户金额日志",$result);
    }

    /*
     * explain:用户店铺评价列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 18:14
     */
    public function userStoreCommitList(Request $request,StoreCommentModel $storeCommentModel)
    {
        $userId = $request->param('user_id');
        $commentList = $storeCommentModel
            ->append('is_collect')
            ->alias('c')
            ->field('c.*,u.nick_name,u.head_img,s.store_name,s.store_address,c.comment_img as comment_img_no,c.comment_img')
            ->join('new_store s','s.store_id=c.store_id','left')
            ->join('new_users u','u.user_id=c.user_id','left')
            ->where('c.user_id',$userId)
            ->order('c.create_time','desc')
            ->select();
        $this->jkReturn('1','店铺评论列表',$commentList);
    }

    /*
     * explain:用户店铺评价点赞
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/11 14:51
     */
    public function userStoreCommentCollect(Request $request,StoreCommentModel $storeCommentModel,StoreCommentCollectModel $storeCommentCollectModel)
    {
        $param = $request->param();
        $isCollect = $storeCommentCollectModel
            ->where(['store_comment_id'=>$param['store_comment_id'],'user_id'=>$param['user_id']])
            ->find();
        if(empty($isCollect)){
            $storeCommentCollectModel->create(['store_comment_id'=>$param['store_comment_id'],'user_id'=>$param['user_id']]);
            $storeCommentModel->where(['store_comment_id'=>$param['store_comment_id']])->setInc('collect_num',1);
        }else{
            $storeCommentCollectModel
                ->where(['store_comment_id'=>$param['store_comment_id'],'user_id'=>$param['user_id']])
                ->delete();
            $storeCommentModel->where(['store_comment_id'=>$param['store_comment_id']])->setDec('collect_num',1);
        }
        $this->jkReturn('1','成功',[]);
    }
    
    /*
     * explain:删除评论
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/11 15:05
     */
    public function userStoreCommentDel(Request $request,StoreCommentModel $storeCommentModel,StoreCommentCollectModel $storeCommentCollectModel)
    {
        $param = $request->param();
        $storeCommentCollectModel->where(['store_comment_id'=>$param['store_comment_id']])->delete();
        $storeCommentModel->where(['store_comment_id'=>$param['store_comment_id'],'user_id'=>$param['user_id']])->delete();
        $this->jkReturn('1','评论删除成功');
    }

    /*
     * explain:用户积分商城评价列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 18:14
     */
    public function userShopCommitList(Request $request,OrderProModel $orderProModel,ProCommentModel $proCommentModel)
    {
        $userId = $request->param('user_id');
        $commentList = $proCommentModel
            ->alias('c')
            ->field('c.*,u.nick_name,u.head_img,p.*')
            ->join('new_order_pro p','p.order_pro_id=c.order_pro_id','left')
            ->join('new_users u','u.user_id=c.user_id','left')
            ->where('c.user_id',$userId)
            ->order('c.create_time','desc')
            ->select();
        $this->jkReturn('1','店铺评论列表',$commentList);
    }

    /*
     * explain:我的足迹
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/17 14:54
     */
    public function userTrace(Request $request,UserTraceModel $userTraceModel,StoreService $storeService)
    {
        $userId = $request->param('user_id');
        $storeList = $userTraceModel
            ->alias('t')
            ->field('t.id,s.store_id,s.comment_num,s.store_name,s.store_desc,s.store_phone,s.store_img,s.store_address,s.lng,s.lat')
            ->where('user_id',$userId)
            ->join('new_store s','s.store_id=t.store_id','left')
            ->group('store_id')
            ->select();
        foreach ($storeList as &$v){
            //-- 计算距离
            $storeService->getStoreDistance($v);
            switch ($v->comment_num){
                case 1:
                    $v->comment_name = '不满';
                    break;
                case 2:
                    $v->comment_name = '一般';
                    break;
                case 3:
                    $v->comment_name = '满意';
                    break;
                case 4:
                    $v->comment_name = '超赞';
                    break;
            }
        }
        $this->jkReturn(1,"用户足迹",$storeList);
    }

    /*
     * explain:删除我的足迹
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/17 14:54
     */
    public function userTraceDel(Request $request,UserTraceModel $userTraceModel)
    {
        $id = $request->param('id');
        $userTraceModel->where(['id'=>$id])->delete();
        $this->jkReturn(1,"用户足迹删除成功",[]);
    }

    /***
     * 验证解绑手机验证码
     * @param Request $request
     * @param LoginModel $loginModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkUnbindCode(Request $request,LoginModel $loginModel,LoginService $loginService)
    {
        $mobile = $request->param('mobile');
        $msg_code = $request->param('msg_code');

        //匹配符合条件验证码
        $where['mobile'] = $mobile;
        $where['code'] = $msg_code;
        $where['code_type'] = '4';
        $codeList = $loginModel->where($where)->find();


        if(!empty($codeList->id)){

            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证通过删除验证码
            $loginModel->where($where)->delete();
            $this->jkReturn(1,'更换手机验证码正确',null);

        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码已过期，请重新获取',array());
        }
    }

    /***
     * 绑定新手机号码
     * @param Request $request
     * @param LoginModel $loginModel
     * @param LoginService $loginService
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bindNewMobile(Request $request,LoginModel $loginModel,LoginService $loginService,UsersModel $usersModel)
    {
        $mobile = $request->param('mobile');
        $msg_code = $request->param('msg_code');
        $user_id = $request->param('user_id');

        /**验证手机号是否存在**/
        $isHaveMobile = $usersModel->where("mobile = {$mobile}")->find();
        if($isHaveMobile){
            $this->jkReturn(-1,'该手机号已存在',null);
        }

        //匹配符合条件验证码
        $where['mobile'] = $mobile;
        $where['code'] = $msg_code;
        $where['code_type'] = '5';
        $codeList = $loginModel->where($where)->find();

        if(!empty($codeList->id)){

            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证通过删除验证码
            $loginModel->where($where)->delete();

            /**修改手机号**/
            $res = $usersModel->save([
                'mobile'  => $mobile,
            ],["user_id"=>$user_id]);

            if($res){
                $this->jkReturn(1,'绑定新手机成功',$res);
            }
            else{
                $this->jkReturn(-1,'绑定新手机失败',null);
            }


        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码已过期，请重新获取',array());
        }
    }

    /*
     * explain:获取协议
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/3 11:35
     */
    public function agreementInfo(Request $request,AgreementRuleModel $agreementRuleModel)
    {
        $code = $request->param('agreement_code');
        $info = $agreementRuleModel->where(['agreement_code'=>$code])->find();
        $content = htmlspecialchars_decode(htmlspecialchars_decode($info->agreement_info));
        $this->assign('content',$content);
        return view("User/agreement_info");
    }

    /*
     * explain:用户积分up指数详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/7 18:09
     */
    public function userScoreUp(Request $request,UserScoreLogModel $userScoreLogModel)
    {
        $userId = $request->param('user_id');
        //-- 现有积分
        $userScore = $request->user->user_score;
        //-- 当月变动
        $newScore = $userScoreLogModel->where("user_id=$userId and date_format(create_time,'%Y-%m')=date_format(now(),'%Y-%m') 
")->sum('score');
        //-- 上月积分
        $oldScore = $userScore-$newScore;
        //-- up指数
        $scoreUp = $oldScore>0 ? sprintf('%.2f',$newScore/$oldScore*100).'%':"0.00%" ;
        $data = [
            'user_score'=>$userScore,
            'new_score'=>$newScore,
            'old_score'=>$oldScore,
            'score_up'=>$scoreUp
        ];
        $this->jkReturn('1','积分up指数',$data);
    }

    /*
     * explain:用户最大可用店铺优惠券
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/8 15:14
     */
    public function userVoucherMax(Request $request,UserVoucherModel $userVoucherModel)
    {
        $param = $request->param();
        $time = $this->getTime();
        $userVoucher = $userVoucherModel->append('refund_state')->where("store_id=".$param['store_id']." and voucher_type=0 and is_pay_used=1 and min_amount<=".$param['order_price']." and use_start_date<'".$time."' and use_end_date>'".$time."' and used_state='C02' and user_id=".$param['user_id'])->select();
        $couponsPrice = 0;
        foreach ($userVoucher as $v){
            if($v->refund_state === '0'){
                if($v->use_method === 0){
                    $price = $v->use_method_info;
                }
                if($v->use_method === 1){
                    $price = sprintf('%.2f',(100-$v->use_method_info)*$param['order_price']/100);
                }
                if($price>$couponsPrice){
                    $couponsPrice = $price;
                    $voucher = $v;
                }
            }
        }
        $data = [
            'user_voucher'=>$voucher??null,
            'price'=>$couponsPrice
        ];
        $this->jkReturn('1','用户可使用的优惠券',$data);
    }

    /*
     * explain:用户最大可用平台优惠券(包括新零售优惠,两种优惠取最大优惠)
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/8 15:14
     */
    public function userCouponsMax(Request $request,UserCouponsModel $userCouponsModel,UsersModel $usersModel,RankModel $rankModel,StoreModel $storeModel,StoreDiscountRuleModel $discountRuleModel)
    {
        $param = $request->param();
        $time = $this->getTime();
        //-- 判断用户可享受的最大优惠券优惠
        $userCouponsList = $userCouponsModel
            ->where("user_id=".$param['user_id']." and used_state='C02' and use_start_date<'".$time."' and use_end_date>'".$time."' and min_amount<=".$param['order_price']." and use_type<>1")
            ->order('create_time','desc')
            ->select();
        $userInfo = $usersModel->where('user_id',$param['user_id'])->find();
        $rankInfo = $rankModel->where('rank_id',$userInfo->rank_id)->find();
        $couponsPrice = 0;
        foreach ($userCouponsList as $v){
            //-- 判断等级
            $rank = $rankModel->where('rank_id',$v->use_rank)->find();
            if($rankInfo->rank_num<$rank->rank_num){
                continue;
            }
            //-- 判断店铺或者分类
            if($v->use_scope === 1){
                //-- 店铺
                if(!in_array($param['store_id'],explode(',',$v->use_scope_info))){
                    continue;
                }
            }else{
                //--分类
                $storeInfo = $storeModel->where('store_id',$param['store_id'])->find();
                $arr = array_intersect(explode(',',$storeInfo->category_id),explode(',',$v->use_scope_info));
                if(count($arr)==0){
                    continue;
                }
            }
            if($v->use_method === 0){
                $price = $v->use_method_info;
            }
            if($v->use_method === 1){
                $price = sprintf('%.2f',(100-$v->use_method_info)*$param['order_price']/100);
            }
            if($price>$couponsPrice){
                $couponsPrice = $price;
                $voucher = $v;
            }
        }
        //-- 判断用户可享受的新零售优惠
        $newTime = $this->getTime();
        $info = $discountRuleModel->where("disabled=1 and '$newTime'>=start_time and '$newTime'<=end_time ")->select();
        $discountPrice = 0;
        foreach ($info as $v){
            if($v->discount_range==0){
                //-- 行业
                $storeInfo = $storeModel->where('store_id',$param['store_id'])->find();
                if(!in_array($storeInfo->nav_id,explode(',',$v->discount_range_info))){
                    continue;
                }
            }
            if($v->discount_range==1){
                //-- 店铺
                if(!in_array($param['store_id'],explode(',',$v->discount_range_info))){
                    continue;
                }
            }
            //-- 计算优惠金额
            switch ($v->discount_type){
                case 0:
                    break;
                case 1:
                    if($v->discount_term>0){
                        $num = floor ($param['order_price']/$v->discount_term);
                        if($num>0){
                           $discount = $num*$v->discount_info;
                        }
                    }
                    break;
                case 2:
                    $discount = sprintf('%.2f',($param['order_price']*(100-$v->discount_info))/100);
            }
            if($discount>0){
                $discount = $discount>$v->discount_max? $v->discount_max:$discount;
            }
            if($discount>$discountPrice){
                $discountPrice = $discount;
                $discountRule = $v;
            }
        }
        if($discountPrice>$couponsPrice){
            $data = [
                'name'=>$discountRule->discount_name??null,
                'user_voucher'=>null,
                'price'=>$discountPrice
            ];
        }else{
            $data = [
                'name'=>$voucher->voucher_name??null,
                'user_voucher'=>$voucher??null,
                'price'=>$couponsPrice
            ];
        }
        $this->jkReturn('1','用户可使用的优惠券',$data);
    }

    /*
     * explain:充值列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/16 10:26
     */
    public function rechargeRule(UserRechargeRuleModel $userRechargeRuleModel)
    {
        $time = $this->getTime();
        $list = $userRechargeRuleModel
            ->where("start_time<'".$time."' and end_time>'".$time."'")
            ->order('sort_order','asc')
            ->select();
        $this->jkReturn('1','充值列表',$list);
    }

    /*
     * explain:充值预下单
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/16 11:10
     */
    public function rechargeCreateOrder(Request $request,UserRechargeModel $userRechargeModel)
    {
        $param = $request->param();
        if(!$userRechargeModel->create($param)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $id = $userRechargeModel->getLastInsID();
        $this->jkReturn('1','下单成功',$id);
    }

    /*
     * explain:查看系统配置
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/16 11:10
     */
    public function getSystemConfig(Request $request,SystemConfigModel $systemConfigModel){
        $code = $request->param('code');
        $where = '1=1 and parent_id<>0 ';
        if(!empty($code)){
            $where .= "and code = '$code'";
        }
        $configList = $systemConfigModel->where($where)->select();
        $this->jkReturn('1','系统配置列表',$configList);
    }


    /*
     * explain:获取分享邀请码页面
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/3 11:35
     */
    public function invitationInfo(Request $request)
    {
        $code = $request->param('invitation_code');
        $this->assign('code',$code);
        return view("User/invitation_info");
    }


    /*
     * explain:我的奖励金详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/3 11:35
     */
    public function userRewardInfo(Request $request,UserRewardModel $userRewardModel)
    {
        $userId = $request->param('user_id');
        //-- 获取用户奖励金额信息
        $moneyList = $userRewardModel->where('user_id='.$userId.' and user_reward_type=2')->select();
        $moneyTotal = $userRewardModel->where('user_id='.$userId.' and user_reward_type=2')->sum('user_reward_info');
        //-- 获取用户奖励积分信息
        $scoreList = $userRewardModel->where('user_id='.$userId.' and user_reward_type=0')->select();
        $scoreTotal = $userRewardModel->where('user_id='.$userId.' and user_reward_type=0')->sum('user_reward_info');

        //获取用户奖励优惠券信息
        $couponsTotal = $userRewardModel->where('user_id='.$userId.' and user_reward_type=1')->count();
        $data = array(
            'moneyList'=>$moneyList,
            'moneyTotal'=>$moneyTotal,
            'scoreList'=>$scoreList,
            'scoreTotal'=>$scoreTotal,
            'couponsTotal'=>$couponsTotal
        );
        $this->jkReturn('1','用户邀请奖励金详情',$data);
    }
}