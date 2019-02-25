<?php
namespace app\admin\controller;
use app\admin\model\CouponsModel;
use app\admin\model\RewardLimitModel;
use app\admin\model\RewardRuleModel;
use app\admin\model\StoreCommentModel;
use app\admin\model\StoreModel;
use app\admin\model\UserModel;
use app\admin\model\UserRankModel;
use app\admin\model\UserRankRuleModel;
use app\admin\model\UsersAddressModel;
use app\admin\model\UserVoucherModel;
use app\admin\model\StoreOrderModel;
use app\admin\model\UserVoucherRefundModel;
use app\admin\service\UploadService;
use think\Request;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/9
 * Time: 14:51
 */
class User extends Common
{
    use \app\api\traits\BuildParam;
    /**
     * 会员列表
     * @param UserModel $userModel
     * @param Request $request
     * @return \think\response\View
     */
    public function userList(UserModel $userModel, Request $request) {
        $userModel->data($request->param());
        if (!empty($userModel->show_count)){
            $show_count = $userModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($userModel->datemin)){
            $where .= " and create_time >= '" . $userModel->datemin . "'";
        }
        if(!empty($userModel->datemax)){
            $where .= " and create_time <= '" . $userModel->datemax . "'";
        }
        if(!empty($userModel->keywords)){
            $keywords = $userModel->keywords;
            $where .= " and (user_id like '%" . $keywords . "%' or user_name like '%" . $keywords . "%' or nick_name like '%". $keywords . "%')";
        }

        //排序条件
        if(!empty($userModel->orderBy)){
            $orderBy = $userModel->orderBy;
        }else{
            $orderBy = 'user_id';
        }
        if(!empty($userModel->orderByUpOrDown)){
            $orderByUpOrDown = $userModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $user_list = $userModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $user_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('userlist');

        // 模板变量赋值
        $this->assign('admin_user_list', $user_list);
        $this->assign('where', $userModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("User/user_list");
    }

    /**
     * 编辑会员
     * @param UserModel $userModel
     * @param Request $request
     * @return \think\response\View
     */
    public function userEdit(UserModel $userModel, Request $request){
        $userModel->data($request->param());
        if(empty($userModel->user_id)){
            $this->error("用户id不能为空");
        }
        //如果是提交
        if(!empty($userModel->is_ajax)){
            if(!empty($userModel->user_name)){
                $user_info = $userModel->where(["user_name"=>$userModel->user_name])->where("user_id","neq",$userModel->user_id)->find();
                if(!empty($user_info)){
                    $this->error("用户名已存在");
                }
            }

            $admin_user_info = $userModel->where(["user_id"=>$userModel->user_id])->find();
            if(!empty($admin_user_info)){
                $upWhere['user_id'] = $userModel->user_id;
                if(!empty($userModel->repassword)){
                    $userModel->password = md5(md5(888888));
                }
                if(!empty($userModel->rePayPassword)){
                    $userModel->pay_password = null;
                }
                $result = $userModel->allowField(true)->save($userModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑会员用户：" . $userModel->user_id ,$userModel->table,$userModel->user_id);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("用户不存在，修改失败");
            }
        }else{
            //获取用户信息
            $user_info = $userModel->where(["user_id"=>$userModel->user_id])->find();
            if(!empty($user_info)){
                $user_info = $user_info->toArray();
            }
            $this->assign('user_info', $user_info);
            // 模板输出
            return view("User/user_info");
        }
    }

    /**
     * 删除会员
     * @param UserModel $userModel
     * @param Request $request
     */
    public function userDel(UserModel $userModel, Request $request){
        $userModel->data($request->param());
        if(!empty($userModel->user_id)){
            $user_id_list = explode(",",$userModel->user_id);
        }

        $result = $userModel->destroy($user_id_list);
        if($result){
            $this->setAdminUserLog("删除","删除会员用户：" . $userModel->user_id ,$userModel->table,$userModel->user_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    /**
     * 批量删除会员
     * @param UserModel $userModel
     * @param Request $request
     */
    public function userQueryDel(UserModel $userModel, Request $request){
        $userModel->data($request->param());
        if(!empty($userModel->user_id)){
            $user_id_list = explode(",",$userModel->user_id);
        }

        $result = $userModel->destroy($user_id_list);
        if($result){
            $this->setAdminUserLog("删除","批量删除会员用户：" . $userModel->user_id ,$userModel->table,$userModel->user_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    /**
     * 批量编辑会员
     * @param UserModel $userModel
     * @param Request $request
     */
    public function userQueryEdit(UserModel $userModel, Request $request){
        $userModel->data($request->param());
        if(empty($userModel->user_id)){
            $this->error("用户id不能为空");
        }else{
            $user_id_list = explode(",",$userModel->user_id);
        }

        foreach ($user_id_list as $key=>$item){
            $data = array();
            $data['user_id'] = $item;
            $data['disabled'] = $userModel->disabled;
            $data['update_time'] = date("Y-m-d H:i:s",time());
            $list[$key] = $data;
        }
        $result = $userModel->allowField(true)->saveAll($list);
        if($result){
            if($userModel->disabled == 0){
                $this->setAdminUserLog("编辑","批量停用会员：" . $userModel->user_id ,$userModel->table,$userModel->user_id);
            }else{
                $this->setAdminUserLog("编辑","批量启用会员：" . $userModel->user_id ,$userModel->table,$userModel->user_id );
            }
            $this->success("编辑成功");
        }else{
            $this->error("编辑失败");
        }

    }

    /**
     * 显示用户信息
     * @param UserModel $userModel
     * @param StoreOrderModel $storeOrderModel
     * @param StoreCommentModel $storeCommentModel
     * @param UserVoucherModel $userVoucherModel
     * @param UsersAddressModel $usersAddressModel
     * @param Request $request
     * @return \think\response\View
     */
    public function userShow(UserModel $userModel, StoreOrderModel $storeOrderModel, StoreCommentModel $storeCommentModel, UserVoucherRefundModel $userVoucherRefundModel, UsersAddressModel $usersAddressModel, Request $request){
        $userModel->data($request->param());
        if(empty($userModel->user_id)){
            $this->error("用户id不能为空");
        }
        //获取用户信息
        $user_info = $userModel->where(["user_id"=>$userModel->user_id])->find();
        if(!empty($user_info)){
            $user_info = $user_info->toArray();
        }
        //消费金额
        $user_info['order_price'] = $storeOrderModel->where(["user_id"=>$userModel->user_id])->sum('order_price');
        //订单数量
        $user_info['order_count'] = $storeOrderModel->where(["user_id"=>$userModel->user_id])->count('order_price');
        //店铺评价
        $user_info['store_comment'] = $storeCommentModel->where(["user_id"=>$userModel->user_id])->count('store_id');
        //退款记录
        $user_info['refund_count'] = $userVoucherRefundModel->where(["user_id"=>$userModel->user_id])->where("refund_state","neq","0")->count('user_id');
        //收货地址列表
        $user_info['address_list'] = $usersAddressModel->where(["user_id"=>$userModel->user_id])->select()->toArray();

        $this->assign('user_info', $user_info);

        // 模板输出
        return view("User/user_show");

    }

    /**
     * 会员等级列表
     *
     * @param UserRankModel $userRankModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/24
     */
    public function userRank(UserRankModel $userRankModel, Request $request) {
        $userRankModel->data($request->param());
        if (!empty($userRankModel->show_count)){
            $show_count = $userRankModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($userRankModel->orderBy)){
            $orderBy = $userRankModel->orderBy;
        }else{
            $orderBy = 'rank_id';
        }
        if(!empty($userRankModel->orderByUpOrDown)){
            $orderByUpOrDown = $userRankModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $user_rank = $userRankModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $user_rank->render();
        //权限按钮
        $action_code_list = $this->getChileAction('userrank');
        // 模板变量赋值
        $this->assign('user_rank', $user_rank);
        $this->assign('where', $userRankModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("User/user_rank_list");
    }

    /**
     * 添加会员等级
     *
     * @param UserRankModel $userRankModel
     * @param UploadService $uploadService
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/24
     */
    public function userRankAdd(UserRankModel $userRankModel,UploadService $uploadService, Request $request)
    {
        $userRankInfo = $request->param();
        //如果是提交
        if(!empty($userRankInfo['is_ajax'])){
            $file = $request->file('rank_img');
            if ($file) {
                $imgUrl  = '/images/User/userRank/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $userRankInfo['rank_img'] = $result;
            }
            $result = $userRankModel->create($userRankInfo);
            if($result){
                $rank_id = $userRankModel->getLastInsID();
                $this->setAdminUserLog("新增","添加会员等级：id为" . $rank_id ,'User');
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("User/user_rank_info");
        }
    }

    /**
     * 编辑会员等级
     *
     * @param UserRankModel $userRankModel
     * @param UploadService $uploadService
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/24
     */
    public function userRankEdit(UserRankModel $userRankModel,UploadService $uploadService, Request $request)
    {
        $userRankInfo = $request->param();
        //如果是提交
        if(!empty($userRankInfo['is_ajax'])){
            $userRank = $userRankModel->where(["rank_id"=>$userRankInfo['rank_id']])->find();
            if(!empty($userRank)){
                $file = $request->file('rank_img');
                if ($file) {
                    $imgUrl  = '/images/User/userRank/';
                    $imgName = $this->imgName();
                    $result = $uploadService->upload($file,$imgUrl,$imgName);
                    $userRankInfo['rank_img'] = $result;
                }else{
                    unset($userRankInfo['rank_img']);
                }
                $upWhere['rank_id'] = $userRankInfo['rank_id'];
                $result = $userRankModel->update($userRankInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑会员等级：id为" . $userRankInfo['rank_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("会员等级不存在，修改失败");
            }
        }else{
            $userRank = $userRankModel->where(["rank_id"=>$userRankInfo['rank_id']])->find();
            if(!empty($userRank)){
                $userRank = $userRank->toArray();
            }
            $this->assign('userRank', $userRank);
            // 模板输出
            return view("User/user_rank_info");
        }
    }

    /**
     * 删除会员等级
     *
     * @param UserRankModel $userRankModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/24
     */
    public function userRankDel(UserRankModel $userRankModel,Request $request){
        $userRankModel->data($request->param());
        if(empty($userRankModel->rank_id)){
            $this->error("会员等级id不能为空");
        }
        $rank_id=$userRankModel->rank_id;
        //单个删除
        $rank = $userRankModel->where(["rank_id"=>$rank_id])->find();
        if(!empty($rank)){
            unlink('.' . $rank->rank_img);
            $result = $userRankModel->where(["rank_id"=>$rank_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除会员等级：id为" . $rank_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("会员等级不存在，删除失败");
        }
    }

    /**
     * 等级规则列表
     *
     * @param UserRankRuleModel $userRankRuleModel
     * @param StoreModel $storeModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/28
     */
    public function userRankRule(UserRankRuleModel $userRankRuleModel, Request $request,StoreModel $storeModel) {
        $userRankRuleModel->data($request->param());
        if (!empty($userRankRuleModel->show_count)){
            $show_count = $userRankRuleModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($userRankRuleModel->orderBy)){
            $orderBy = $userRankRuleModel->orderBy;
        }else{
            $orderBy = 'rank_rule_id';
        }
        if(!empty($userRankRuleModel->orderByUpOrDown)){
            $orderByUpOrDown = $userRankRuleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $user_rank_rule = $userRankRuleModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        if (!empty($user_rank_rule)){
            foreach ($user_rank_rule as &$rank_rule) {
                $rank_rule['storeName'] = '';
                if (!empty($rank_rule['store_id'])) {
                    $storeIds = explode(',',$rank_rule['store_id']);
                    if (count($storeIds) == 1) {
                        $storeInfo = $storeModel->where(["store_id"=>$storeIds[0]])->find();
                        if(!empty($storeInfo)){
                            $storeInfo = $storeInfo->toArray();
                        }
                        $rank_rule['storeName'] = $storeInfo['store_name'];
                    }else{
                        $storeName = array();
                        foreach ($storeIds as $storeId) {
                            $storeInfo = $storeModel->where(["store_id"=>$storeId])->find();
                            if(!empty($storeInfo)){
                                $storeInfo = $storeInfo->toArray();
                                $storeName[] = $storeInfo['store_name'];
                            }
                        }
                        $rank_rule['storeName'] = implode(',',$storeName);
                    }
                }
            }
        }
        // 获取分页显示
        $page = $user_rank_rule->render();
        //权限按钮
        $action_code_list = $this->getChileAction('userrankrule');
        // 模板变量赋值
        $this->assign('user_rank_rule', $user_rank_rule);
        $this->assign('where', $userRankRuleModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("User/user_rank_rule_list");
    }

    /**
     * 添加等级规则
     *
     * @param UserRankRuleModel $userRankRuleModel
     * @param Request $request
     * @param UserRankModel $userRankModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/28
     */
    public function userRankRuleAdd(UserRankRuleModel $userRankRuleModel, Request $request, UserRankModel $userRankModel)
    {
        $userRankRuleInfo = $request->param();
        //如果是提交
        if(!empty($userRankRuleInfo['is_ajax'])){
            $rule_info = array();
            if (!empty($userRankRuleInfo['rank_id']) && !empty($userRankRuleInfo['info'])) {
                foreach ($userRankRuleInfo['rank_id'] as $key => $rank_id) {
                    foreach ($userRankRuleInfo['info'] as $value =>$number) {
                        if ($key == $value) {
                            //$rule_info[$rank_id] = $number;
                            $rule_info[] = array('rank_id' => $rank_id, 'info' => $number);
                        }
                    }
                }
            }
            $userRankRuleInfo['rule_info'] = serialize($rule_info);
            $result = $userRankRuleModel->create($userRankRuleInfo);
            if($result){
                $rank_id = $userRankRuleModel->getLastInsID();
                $this->setAdminUserLog("新增","添加等级规则：id为" . $rank_id ,'User');
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $rule_info  = array(array('rank' => '', 'info' => 0));
            $userRank = $userRankModel->field('rank_id,rank_name')->select();

            $this->assign('userRank', $userRank);
            $this->assign('rule_info', $rule_info);

            // 模板输出
            return view("User/user_rank_rule_info");
        }
    }

    /**
     * 编辑等级规则
     *
     * @param UserRankRuleModel $userRankRuleModel
     * @param Request $request
     * @param UserRankModel $userRankModel
     * @param StoreModel $storeModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/28
     */
    public function userRankRuleEdit(UserRankRuleModel $userRankRuleModel, Request $request, UserRankModel $userRankModel,StoreModel $storeModel)
    {
        $userRankRuleInfo = $request->param();
        //如果是提交
        if(!empty($userRankRuleInfo['is_ajax'])){
            $userRankRule = $userRankRuleModel->where(["rank_rule_id"=>$userRankRuleInfo['rank_rule_id']])->find();
            if(!empty($userRankRule)){
                $rule_info = array();
                if (!empty($userRankRuleInfo['rank_id']) && !empty($userRankRuleInfo['info'])) {
                    foreach ($userRankRuleInfo['rank_id'] as $key => $rank_id) {
                        foreach ($userRankRuleInfo['info'] as $value =>$number) {
                            if ($key == $value) {
                                //$rule_info[$rank_id] = $number;
                                $rule_info[] = array('rank_id' => $rank_id, 'info' => $number);
                            }
                        }
                    }
                }
                $userRankRuleInfo['rule_info'] = serialize($rule_info);
                $upWhere['rank_rule_id'] = $userRankRuleInfo['rank_rule_id'];
                $result = $userRankRuleModel->update($userRankRuleInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑等级规则：id为" . $userRankRuleInfo['rank_rule_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("等级规则不存在，修改失败");
            }
        }else{
            //获取信息
            $userRankRule = $userRankRuleModel->where(["rank_rule_id"=>$userRankRuleInfo['rank_rule_id']])->find();
            if(!empty($userRankRule)){
                $userRankRule = $userRankRule->toArray();
            }
            $rankInfo = unserialize($userRankRule['rule_info']);
            $userRank = $userRankModel->field('rank_id,rank_name')->select()->toArray();
            foreach ($userRank as &$rank) {
                if (empty($rankInfo)) {
                    $rank['info'] = $rank['rank_id'] - 1 ;
                }else{
                    foreach ($rankInfo as $info) {
                        if ($rank['rank_id'] == $info['rank_id']) {
                            $rank['info'] = $info['info'];
                        }
                    }
                }
            }
            //获取店铺列表
            $list = array();
            if (!empty($userRankRule['store_id'])) {
                $store_id = $userRankRule['store_id'];
                $where = ' disabled =1 and store_id in ('.$store_id .') and audit_state = 1 ';
                $list = $storeModel->field(['store_id as id','store_name as name'])->where($where)->select();
            }

            $this->assign('userRank', $userRank);
            $this->assign('userRankRule', $userRankRule);
            $this->assign('storeList',$list);
            //$this->assign('rule_info', $rule_info);
            // 模板输出
            return view("User/user_rank_rule_info");
        }
    }

    /**
     * 删除等级规则
     *
     * @param UserRankRuleModel $userRankRuleModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/28
     */
    public function userRankRuleDel(UserRankRuleModel $userRankRuleModel, Request $request)
    {
        $userRankRuleModel->data($request->param());
        if(empty($userRankRuleModel->rank_rule_id)){
            $this->error("会员等级id不能为空");
        }
        $rank_id=$userRankRuleModel->rank_rule_id;
        //单个删除
        $rank = $userRankRuleModel->where(["rank_rule_id"=>$rank_id])->find();
        if(!empty($rank)){
            $result = $userRankRuleModel->where(["rank_rule_id"=>$rank_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除等级规则：id为" . $rank_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("等级规则不存在，删除失败");
        }
    }
    //奖励限制
    public function rewardLimit(Request $request,RewardLimitModel $rewardLimitModel){
        $rewardLimitModel->data($request->param());
        if (!empty($rewardLimitModel->show_count)){
            $show_count = $rewardLimitModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($rewardLimitModel->orderBy)){
            $orderBy = $rewardLimitModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($rewardLimitModel->orderByUpOrDown)){
            $orderByUpOrDown = $rewardLimitModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $rewardLimit = $rewardLimitModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $rewardLimit->render();
        //权限按钮
        $action_code_list = $this->getChileAction('rewardlimit');
        // 模板变量赋值
        $this->assign('rewardLimit', $rewardLimit);
        $this->assign('where', $rewardLimitModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("User/reward_limit");
    }
    public function rewardLimitAdd(Request $request,RewardLimitModel $rewardLimitModel){
        $rewardLimit = $request->param();
        //如果是提交
        if(!empty($rewardLimit['is_ajax'])){
            $result = $rewardLimitModel->create($rewardLimit);
            if($result){
                $limit_id = $rewardLimitModel->getLastInsID();
                $this->setAdminUserLog("新增","添加奖励限制：id为" . $limit_id ,'User');
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("User/reward_limit_info");
        }
    }
    public function rewardLimitEdit(Request $request,RewardLimitModel $rewardLimitModel){
        $rewardLimit = $request->param();
        //如果是提交
        if(!empty($rewardLimit['is_ajax'])){
            $userRankRule = $rewardLimitModel->where(["id"=>$rewardLimit['id']])->find();
            if(!empty($userRankRule)){
                $upWhere['id'] = $rewardLimit['id'];
                $result = $rewardLimitModel->update($rewardLimit,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑奖励限制：id为" . $rewardLimit['id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("奖励限制不存在，修改失败");
            }
        }else{
            //获取信息
            $rewardLimitInfo = $rewardLimitModel->where(["id"=>$rewardLimit['id']])->find();
            if(!empty($rewardLimitInfo)){
                $rewardLimitInfo = $rewardLimitInfo->toArray();
            }
            $this->assign('rewardLimitInfo', $rewardLimitInfo);
            // 模板输出
            return view("User/reward_limit_info");
        }
    }
    public function rewardLimitDel(RewardLimitModel $rewardLimitModel, Request $request)
    {
        $rewardLimitModel->data($request->param());
        if(empty($rewardLimitModel->id)){
            $this->error("限制id不能为空");
        }
        $rank_id = $rewardLimitModel->id;
        //单个删除
        $rank = $rewardLimitModel->where(["id"=>$rank_id])->find();
        if(!empty($rank)){
            $result = $rewardLimitModel->where(["id"=>$rank_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除奖励限制：id为" . $rank_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("奖励限制不存在，删除失败");
        }
    }

    //用户奖励规则
    public function rewardRule(RewardRuleModel $rewardRuleModel, Request $request, CouponsModel $couponsModel){
        $rewardRuleModel->data($request->param());
        if (!empty($rewardRuleModel->show_count)){
            $show_count = $rewardRuleModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($rewardRuleModel->orderBy)){
            $orderBy = $rewardRuleModel->orderBy;
        }else{
            $orderBy = 'b.id';
        }
        if(!empty($rewardRuleModel->orderByUpOrDown)){
            $orderByUpOrDown = $rewardRuleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $rewardRule = $rewardRuleModel
            ->field('b.*,l.min_amount,l.limit_order')
            ->alias('b')
            ->join('new_reward_limit l','l.id = b.limit_id','left')
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        if (!empty($rewardRule)) {
            foreach ($rewardRule as &$rule) {
                if ($rule['reward_range'] == 1) {
                    $couponName = $couponsModel->field('coupons_name')->where(['coupons_id'=>$rule['reward_info']])->find();
                    $rule['voucher_name'] = $couponName['coupons_name'];
                }
            }
        }
        // 获取分页显示
        $page = $rewardRule->render();
        //权限按钮
        $action_code_list = $this->getChileAction('rewardrule');
        // 模板变量赋值
        $this->assign('rewardRule', $rewardRule);
        $this->assign('where', $rewardRuleModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("User/reward_rule_list");
    }
    public function rewardRuleAdd(RewardRuleModel $rewardRuleModel, Request $request, CouponsModel $couponsModel, RewardLimitModel $rewardLimitModel)
    {
        $rewardRuleInfo = $request->param();
        //如果是提交
        if(!empty($rewardRuleInfo['is_ajax'])){
            if ($rewardRuleInfo['reward_range'] == 1) {
                $rewardRuleInfo['reward_info'] = $rewardRuleInfo['coupons_id'];
                $rewardRuleInfo['reward_type'] = 1;
            }
            if (strtotime($rewardRuleInfo['start_time']) > strtotime($rewardRuleInfo['end_time'])) {
                $this->error("结束时间不能大于开始时间");
            }
            $result = $rewardRuleModel->create($rewardRuleInfo);
            if($result){
                $rank_id = $rewardRuleModel->getLastInsID();
                $this->setAdminUserLog("新增","添加奖励规则：id为" . $rank_id ,'User');
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $coupons = $couponsModel->field('coupons_id,coupons_name')->select();
            $rewardLimit = $rewardLimitModel->select();
            $this->assign('coupons', $coupons);
            $this->assign('rewardLimit', $rewardLimit);
            // 模板输出
            return view("User/reward_rule_info");
        }
    }
    public function rewardRuleEdit(RewardRuleModel $rewardRuleModel, Request $request, CouponsModel $couponsModel, RewardLimitModel $rewardLimitModel)
    {
        $rewardRuleInfo = $request->param();
        //如果是提交
        if(!empty($rewardRuleInfo['is_ajax'])){
            $rewardRule = $rewardRuleModel->where(["id"=>$rewardRuleInfo['id']])->find();
            if(!empty($rewardRule)){
                if ($rewardRuleInfo['reward_range'] == 1) {
                    $rewardRuleInfo['reward_info'] = $rewardRuleInfo['coupons_id'];
                    $rewardRuleInfo['reward_type'] = 1;
                }
                if (strtotime($rewardRuleInfo['start_time']) > strtotime($rewardRuleInfo['end_time'])) {
                    $this->error("结束时间不能大于开始时间");
                }
                $upWhere['id'] = $rewardRuleInfo['id'];
                $result = $rewardRuleModel->update($rewardRuleInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑奖励规则：id为" . $rewardRuleInfo['id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("奖励规则不存在，修改失败");
            }
        }else{
            //获取信息
            $rewardRule = $rewardRuleModel->where(["id"=>$rewardRuleInfo['id']])->find();
            if(!empty($rewardRule)){
                $rewardRule = $rewardRule->toArray();
            }
            $coupons = $couponsModel->field('coupons_id,coupons_name')->select();
            $rewardLimit = $rewardLimitModel->select();
            $this->assign('coupons', $coupons);
            $this->assign('rewardLimit', $rewardLimit);
            $this->assign('rewardRule', $rewardRule);


            // 模板输出
            return view("User/reward_rule_info");
        }
    }
    public function rewardRuleDel(RewardRuleModel $rewardRuleModel, Request $request)
    {
        $rewardRuleModel->data($request->param());
        if(empty($rewardRuleModel->id)){
            $this->error("奖励规则id不能为空");
        }
        $rank_id=$rewardRuleModel->id;
        //单个删除
        $rank = $rewardRuleModel->where(["id"=>$rank_id])->find();
        if(!empty($rank)){
            $result = $rewardRuleModel->where(["id"=>$rank_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除奖励规则：id为" . $rank_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("奖励规则不存在，删除失败");
        }
    }


}