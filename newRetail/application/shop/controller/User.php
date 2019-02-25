<?php
namespace app\shop\controller;
use app\shop\model\StoreCommentModel;
use app\shop\model\UserModel;
use app\shop\model\UsersAddressModel;
use app\shop\model\UserVoucherModel;
use app\shop\model\StoreOrderModel;
use think\Request;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/9
 * Time: 14:51
 */
class User extends Common
{
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
    public function userShow(UserModel $userModel, StoreOrderModel $storeOrderModel, StoreCommentModel $storeCommentModel, UserVoucherModel $userVoucherModel, UsersAddressModel $usersAddressModel, Request $request){
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
        $user_info['refund_count'] = $userVoucherModel->where(["user_id"=>$userModel->user_id])->where("refund_state","neq","0")->count('user_id');
        //收货地址列表
        $user_info['address_list'] = $usersAddressModel->where(["user_id"=>$userModel->user_id])->select()->toArray();

        $this->assign('user_info', $user_info);

        // 模板输出
        return view("User/user_show");

    }


}