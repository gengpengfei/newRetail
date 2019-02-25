<?php

namespace app\shop\controller;

use app\shop\model\LoginModel;
use app\shop\model\StoreAuditModel;
use app\shop\model\StoreUserModel;
use app\shop\service\LoginService;
use think\captcha\Captcha;
use think\Request;
use think\Session;

class Login extends Common
{
    use \app\shop\traits\SendSMS;
    use \app\shop\traits\BuildParam;
    use \app\shop\traits\GetConfig;
    /**
     *  发送验证码
     * @param LoginService $ls
     * @param Request $request
     * @param LoginModel $loginModel
     */
    public function sendMobileCode(LoginService $ls, Request $request, LoginModel $loginModel)
    {
        $loginModel->data($request->param());

        //先查看该手机号是否有该类验证码
        $where['mobile'] = $loginModel->mobile;
        $codeType = $loginModel->code_type;
        $where['code_type'] = $codeType;
        $codeList = $loginModel->where($where)->find();

        if($ls->checkCanSendMobileCode($codeList->create_time??0)){
            //删除原来记录内容
            $loginModel->where($where)->delete();
            $code = $this->randNumber(6);
            $sms_mobile_conf_code = ["sms_mobile_register_content","sms_mobile_login_content","sms_mobile_pay_content","sms_mobile_password_content","sms_mobile_modifier_old_tel","sms_mobile_modifier_new_tel","sms_mobile_update_password","sms_mobile_get_password"];
            $content = sprintf ( $this->getConfig($sms_mobile_conf_code[$codeType]), $this->getConfig('sms_mobile_from'), $code, $this->getConfig('sms_mobile_code_active_time'));
            // 保存验证信息
            $loginModel->code = $code;
            $loginModel->content = $content;
            $loginModel->allowField(true)->save();
            $id = $loginModel->getLastInsID();
            if($id){
                //发送验证码
                $status = $this->sendSMS ($loginModel->mobile,$content);
                if ($status == 0) {
                    $this->jkReturn(1,'短信验证码发送成功',$loginModel);
                } else {
                    $this->jkReturn(-1,'短信验证码发送失败',array());
                }
            }else{
                $this->jkReturn(-1,'短信验证码发送失败',array());
            }
        }

    }

    /**
     * 快捷登陆
     * @param Request $request
     * @param LoginService $loginService
     * @param LoginModel $loginModel
     */
    public function quickLogin(Request $request, LoginService $loginService, LoginModel $loginModel, StoreUserModel $storeUserModel)
    {
        $param = $request->param();
        if(!isset($param['is_ajax'])){
            return view();
            exit;
        }
        //获取该手机号该类验证码
        $where['mobile'] = $param['mobile'];
        $where['code_type'] = $param['code_type'];
        $codeList = $loginModel->where($where)->find();
        if(!empty($codeList->id)) {
            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证码是否匹配
            $loginService->checkMobileCodeMate($codeList->code, $param['code']);
            //查看该手机号是否有对应用户
            $userInfo = $storeUserModel->where("mobile={$param['mobile']} and store_id>0")->find();
            if(empty($userInfo->admin_id)){
                //不存在提示进行注册
                $this->jkReturn(-1,'该手机号未注册或未绑定门店',[]);
            }else{
                if($userInfo->disabled == 0){
                    $this->error("用户已禁用");
                }
                $token = md5(md5($userInfo->mobile.time() ));
                $storeUserModel->allowField(true)->save(['token'=>$token],['admin_id'=>$userInfo->admin_id]);
                $userInfo = $storeUserModel->where(['mobile'=>$where['mobile']])->find();
                Session::set("shop_user_id",$userInfo->admin_id);
                Session::set("shop_user_name",$userInfo->user_name);
                Session::set("login_count",$userInfo->login_count);
                Session::set("last_ip",$userInfo->last_ip);
                Session::set("last_login_time",$userInfo->last_login_time);
                Session::set("shop_id",$userInfo['store_id']);
                Session::set("is_boss",$userInfo['is_boss']);
                $this->setAdminUserLog("登录","用户登录",$storeUserModel->table,$userInfo->admin_id);
                //登录次数累计
                $userInfo->login_count ++;
                $userInfo->last_ip = request()->ip();
                $userInfo->last_login_time = date("Y-m-d H:i:s",time());
                $userInfo->save();
                //验证通过删除验证码
                $loginModel->where($where)->delete();
                $this->jkReturn(1,'登录成功',$userInfo);
            }
        }else{
            //存在的返回用户信息
            $this->jkReturn(-1,'手机验证码错误,请确认后重新输入!',array());
        }
    }

    public function index()
    {
        return view();
    }
    public function login(StoreUserModel $adminUserModel,StoreAuditModel $storeAuditModel)
    {
        $mobile = $_POST['mobile'];
        $passWord = $_POST['password'];
        //用户是否存在
        if (!empty($mobile) && !empty($passWord)) {
            $userInfo = $adminUserModel->where("mobile=$mobile and store_id>0")->find();
            if(empty($userInfo)){
                $this->jkReturn(11,'该手机号未注册或未绑定门店',[]);
            }
            if($userInfo->disabled == 0){
                $this->jkReturn(1,'用户已禁用',[]);
            }
            if(md5(md5($passWord)) !== $userInfo->password)
                $this->jkReturn(-1,'账号或者密码填写错误',[]);

            if ($userInfo->admin_id) {
                Session::set("shop_user_id",$userInfo->admin_id);
                Session::set("shop_user_name",$userInfo->user_name);
                /*Session::set("admin_nickname",$userInfo->nickname);*/
                Session::set("login_count",$userInfo->login_count);
                Session::set("last_ip",$userInfo->last_ip);
                Session::set("last_login_time",$userInfo->last_login_time);
                Session::set("shop_id",$userInfo['store_id']);
                Session::set("is_boss",$userInfo['is_boss']);

                $this->setAdminUserLog("登录","用户登录",$adminUserModel->table,$userInfo->admin_id);

                //登录次数累计
                $userInfo->login_count ++;
                $userInfo->last_ip = request()->ip();
                $userInfo->last_login_time = date("Y-m-d H:i:s",time());
                $userInfo->save();
                $this->jkReturn(1,'登录成功',[]);
            }
        } else {
            $this->jkReturn(-1,'请填写账号或者密码',[]);
        }
    }
    //退出登录
    public function logout()
    {
        //清空session
        Session::clear();
        $this->redirect('login/index');
    }

    public function repassword(StoreUserModel $adminUserModel,Request $request)
    {
        $request_data = $request->param();
        $admin_user_id = session::get("shop_user_id");
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