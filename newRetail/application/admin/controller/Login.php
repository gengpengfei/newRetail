<?php

namespace app\admin\controller;

use app\admin\model\AdminUserModel;
use think\captcha\Captcha;
use think\Request;
use think\Session;
use app\admin\controller\Common;
class Login extends Common
{
    public function index()
    {
        return view();
    }
    public function login(AdminUserModel $adminUserModel)
    {
        $userName = $_POST['user_name'];
        $passWord = $_POST['password'];
        //用户是否存在
        if (!empty($userName) && !empty($passWord)) {
            $userInfo = $adminUserModel->where(['user_name'=>$userName])->find();
            if(empty($userInfo)){
                $this->error("用户不存在");
            }
            if($userInfo->disabled == 0){
                $this->error("用户已禁用");
            }
            if(md5(md5($passWord)) !== $userInfo->password)
                $this->error("账号或者密码填写错误");

            if ($userInfo->admin_id) {
                Session::set("admin_user_id",$userInfo->admin_id);
                Session::set("admin_user_name",$userInfo->user_name);
                Session::set("admin_nickname",$userInfo->nickname);
                Session::set("login_count",$userInfo->login_count);
                Session::set("last_ip",$userInfo->last_ip);
                Session::set("last_login_time",$userInfo->last_login_time);

                $this->setAdminUserLog("登录","用户登录",$adminUserModel->table,$userInfo->admin_id);

                //登录次数累计
                $userInfo->login_count ++;
                $userInfo->last_ip = request()->ip();
                $userInfo->last_login_time = date("Y-m-d H:i:s",time());
                $userInfo->save();
                $this->success();
            }
        } else {
            $this->error("请填写账号或者密码");
        }
    }
    //退出登录
    public function logout()
    {
        //清空session
        Session::clear();
        $this->redirect('Login/index');
    }

    public function repassword(AdminUserModel $adminUserModel,Request $request)
    {
        $request_data = $request->param();
        $admin_user_id = session::get("admin_user_id");
        //用户是否存在
        if (!empty($admin_user_id) ) {
            $userInfo = $adminUserModel->where(['admin_id'=>$admin_user_id])->find();
            if(empty($userInfo)){
                $this->error("用户不存在");
            }
            if(empty($request_data['is_ajax'])){
                return view("login/repassword");
            }else{
                if($userInfo->password != md5(md5($request_data['old_password']))){
                    $this->error("原始密码错误");
                }else{
                    $where['admin_id'] = $admin_user_id;
                    $request_data['password'] = md5(md5($request_data['password']));
                    $result = $adminUserModel->save($request_data,$where);
                    if($result){
                        $this->success("修改成功");
                    }else{
                        $this->error("修改失败");
                    }
                }
            }

        } else {
            $this->error("参数异常");
        }
    }

    //--获取验证码
    public function getCode()
    {
        ob_clean();
        $config = config('captcha');
        $captcha = new Captcha($config);
        return $captcha->entry();
    }


}