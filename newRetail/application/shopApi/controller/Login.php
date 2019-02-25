<?php

namespace app\shopapi\controller;

use app\shopapi\model\LoginModel;
use app\shopapi\model\StoreUserModel;
use app\shopapi\model\UsersModel;
use app\shopapi\service\LoginService;
use think\Request;

class Login extends Common
{
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\SendSMS;
    use \app\shopapi\traits\GetConfig;
    /*
     * explain:发送验证码
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 10:33
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

            $sms_mobile_conf_code = ["sms_mobile_register_content","sms_mobile_login_content","sms_mobile_pay_content","sms_mobile_password_content","sms_mobile_modifier_old_tel","sms_mobile_modifier_new_tel","sms_mobile_update_password","sms_mobile_get_password","sms_mobile_admin_register_cont","sms_mobile_admin_audit_cont","sms_mobile_admin_claim","sms_mobile_admin_member",'sms_mobile_del_member'];

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
     * 注册
     * @param Request $request
     * @param LoginService $loginService
     * @param LoginModel $loginModel
     * @param UsersModel $userModel
     */
    public function register(Request $request, LoginService $loginService, LoginModel $loginModel,StoreUserModel $storeUserModel)
    {
        $param = $request->param();
        //获取该手机号该类验证码
        $where['mobile'] = $param['mobile'];
        $where['code_type'] = $param['code_type'];
        $codeList = $loginModel->where($where)->find();
        if(!empty($codeList->id)){
            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证码是否匹配
            $loginService->checkMobileCodeMate($codeList->code,$param['code']);
            //查看该手机号是否有对应用户
            $userInfo = $storeUserModel->where("mobile={$param['mobile']} and disabled=1")->find();
            if(!empty($userInfo)){
                $this->jkReturn(-1,'该手机号码已经注册',$userInfo);
            }else{
                if($param['password'] == $param['password_confirm']){
                    //进行注册
                    $storeUserModel->data($request->param());
                    $storeUserModel->user_name = $param['mobile'];
                    $storeUserModel->password = md5(md5($param['password']));
                    $storeUserModel->disabled = 1;
                    $storeUserModel->is_boss = 1;
                    $storeUserModel->allowField(true)->save();
                    $userInfo = $storeUserModel
                        ->where(['mobile' => $param['mobile']])
                        ->find();
                    //验证通过删除验证码
                    $loginModel->where($where)->delete();
                    $this->jkReturn(1,'注册成功',$userInfo);
                }else{
                    $this->jkReturn(-1,'网络延时,请稍后重试',array());
                }
            }
        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码已过期，请重新获取',array());
        }
    }

    /**
     * 快捷登陆
     * @param Request $request
     * @param LoginService $loginService
     * @param LoginModel $loginModel
     * @param UsersModel $userModel
     */
    public function quickLogin(Request $request, LoginService $loginService, LoginModel $loginModel, StoreUserModel $storeUserModel)
    {
        $loginModel->data($request->param());
        $storeUserModel->data($request->param());
        //获取该手机号该类验证码
        $where['mobile'] = $loginModel->mobile;
        $where['code_type'] = $loginModel->code_type;
        $codeList = $loginModel->where($where)->find();
        if(!empty($codeList->id)) {
            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证码是否匹配
            $loginService->checkMobileCodeMate($codeList->code, $loginModel->code);
            //查看该手机号是否有对应用户
            $userInfo = $storeUserModel->where("mobile=$loginModel->mobile and disabled=1")->find();
            if(empty($userInfo->admin_id)){
                //不存在进行注册
                //生成6位随机密码
                $password = $this->randNumber(8);
                $storeUserModel->user_name = $loginModel->mobile;
                $storeUserModel->password = md5(md5($password ));
                $storeUserModel->disabled = 1;
                $storeUserModel->is_boss = 0;
                $storeUserModel->token =  md5(md5($userInfo->mobile.time() ));
                $whereData['admin_id'] = $storeUserModel->allowField(true)->save();
                if($whereData['admin_id'] ){
                    $userInfo = $storeUserModel
                        ->where(['mobile' => $loginModel->mobile,"disabled"=>1])
                        ->find();
                    //验证通过删除验证码
                    $loginModel->where($where)->delete();
                    $this->jkReturn(1,'登录成功',$userInfo);
                }else{
                    $this->jkReturn(-1,'登录失败',$userInfo);
                }
            }else{
                $storeUserModel->token = md5(md5($userInfo->mobile.time() ));
                $storeUserModel->password = $userInfo->password;
                $upWhere['admin_id'] = $userInfo->admin_id;
                $storeUserModel->allowField(true)->save($storeUserModel,$upWhere);
                //存在的返回用户信息
                $userInfo = $storeUserModel
                    ->where(['mobile' => $loginModel->mobile,'disabled'=>1])
                    ->find();
                //验证通过删除验证码
                $loginModel->where($where)->delete();
                $this->jkReturn(1,'登录成功',$userInfo);
            }
        }else{
            //存在的返回用户信息
            $this->jkReturn(-1,'请输入正确手机验证码',array());
        }
    }

    /**
     * 登录
     * @param Request $request
     * @param UsersModel $userModel
     */
    public function login(Request $request, StoreUserModel $storeUserModel){
        $param = $request->param();
        $userInfo = $storeUserModel
            ->where(['mobile' => $param['mobile'],'disabled'=>1])
            ->find();
        if(empty($userInfo)){
            $this->jkReturn(-1,"该账号不存在",array());
        }
        if($userInfo->password == md5(md5($param['password']))){
            $upWhere['admin_id'] = $userInfo->admin_id;
            $data = $userInfo->toArray();
            $data['token'] = $token = md5(md5($userInfo->mobile.time() ));
            $data['last_login_time'] = $this->getTime();
            $data['login_count'] = $userInfo->login_count+1;
            $data['last_ip'] = $this->getIp();
            $storeUserModel->allowField(true)->save($data,$upWhere);
            $this->jkReturn(1,"登陆成功",$data);
        }else{
            $this->jkReturn(-1,"登陆失败",array());
        }
    }

    /**
     * token登录
     * @param Request $request
     * @param UsersModel $userModel
     */
    public function token_login(Request $request,StoreUserModel $storeUserModel){
        $param = $request->param();
        $userInfo = $storeUserModel
            ->where(['mobile' => $param['mobile'],'disabled'=>1])
            ->find();
        if($userInfo->token == $param['token']){
            $this->jkReturn(1,"登陆成功",$userInfo);
        }else{
            $this->jkReturn(-1,"登陆失败",array());
        }
    }

    /*
     * explain:修改密码
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/4 11:49
     */
    public function updatePassword(Request $request, LoginService $loginService, LoginModel $loginModel,StoreUserModel $storeUserModel){
        $param = $request->param();
        $ignore_code = $param['ignore_code'];
        if($ignore_code){
            // 如果不需要验证码，直接修改密码
           $this->modifyPassword($param['mobile'],$param['password'],$param['password_confirm'],$storeUserModel);
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
            //验证通过删除验证码
            $loginModel->where($where)->delete();
            //修改密码
            $this->modifyPassword($param['mobile'],$param['password'],$param['password_confirm'],$storeUserModel);

        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码错误，请重新获取',array());
        }
    }

    // 修改手机密码
    private function modifyPassword($mobile,$password,$password_confirm,$storeUserModel){
        //查看该手机号是否有对应用户
        $userInfo = $storeUserModel
            ->where(['mobile' => $mobile])
            ->find();
        if(!empty($userInfo->admin_id)){
            if($password == $password_confirm){
                //进行修改密码
                $password = md5(md5($password));
                if($storeUserModel->save(['password'=>$password],['admin_id'=>$userInfo->admin_id])){
                    $this->jkReturn(1,'修改密码成功',$userInfo);
                }else{
                    $this->jkReturn(1,'网络延时,请稍后重试',[]);
                }
            }else{
                $this->jkReturn(-1,'两次输入的密码不同',array());
            }
        }else{
            $this->jkReturn(-1,'该用户不存在,请确认手机号是否正确!',$userInfo);
        }
    }
}
