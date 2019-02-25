<?php

namespace app\api\controller;

use app\api\model\LoginModel;
use app\api\model\RankModel;
use app\api\model\UsersModel;
use app\api\service\LoginService;
use think\Request;

class Login extends Common
{
    use \app\api\traits\BuildParam;
    use \app\api\traits\SendSMS;
    use \app\api\traits\GetConfig;

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
     * @param UsersModel $userModel
     */
    public function quickLogin(Request $request, LoginService $loginService, LoginModel $loginModel, UsersModel $userModel,RankModel $rankModel)
    {
        $loginModel->data($request->param());
        $userModel->data($request->param());
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
            $userInfo = $userModel->where(['mobile' => $loginModel->mobile])->find();
            if(empty($userInfo->user_id)){
                //不存在进行注册
                //-- 获取最低等级
                $rank = $rankModel->order('rank_num','asc')->find();
                //生成6位随机密码
                $password = $this->randNumber(6);
                $userModel->user_name = $loginModel->mobile;
                $userModel->password = md5(md5($password ));
                $userModel->nick_name = $loginModel->mobile;
                $userModel->create_by = 0;
                $userModel->disabled = 1;
                $userModel->rank_id = $rank->rank_id;
                $userModel->token =  md5(md5($userInfo->mobile.time()));
                $whereData['user_id'] = $userModel->allowField(true)->save();
                if($whereData['user_id'] ){
                    $userInfo = $userModel
                        ->alias('u')
                        ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
                        ->where(['mobile' => $loginModel->mobile])
                        ->join('new_rank r','r.rank_id=u.rank_id','left')
                        ->find();
                    $save_money_ranking = $userModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
                    $all = $userModel->group('user_save_money')->count();
                    $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
                    //验证通过删除验证码
                    $loginModel->where($where)->delete();
                    $this->jkReturn(1,'登录成功',$userInfo);
                }else{
                    $this->jkReturn(-1,'登录失败',$userInfo);
                }
            }else{
                $userInfo['token'] = $userModel->token = md5(md5($userInfo->mobile.time() ));
                $upWhere['user_id'] = $userInfo->user_id;
                $userModel->password = $userInfo->password;
                $userModel->allowField(true)->save($userModel,$upWhere);
                //存在的返回用户信息
                $userInfo = $userModel
                    ->alias('u')
                    ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
                    ->where(['mobile' => $loginModel->mobile])
                    ->join('new_rank r','r.rank_id=u.rank_id','left')
                    ->find();
                $save_money_ranking = $userModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
                $all = $userModel->group('user_save_money')->count();
                $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
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
     * 注册
     * @param Request $request
     * @param LoginService $loginService
     * @param LoginModel $loginModel
     * @param UsersModel $userModel
     */
    public function register(Request $request, LoginService $loginService, LoginModel $loginModel, UsersModel $userModel,RankModel $rankModel)
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
            $userInfo = $userModel->where(['mobile'=>$param['mobile']])->find();

            if(!empty($userInfo->user_id)){
                $this->jkReturn(-1,'该手机号码已经注册',$userInfo);
            }else{
                if($param['password'] == $param['password_confirm']){
                    //-- 获取最低等级
                    $rank = $rankModel->order('rank_num','asc')->find();
                    //-- 生成我的邀请码
                    $sole = $this->get62(8);
                    //-- 查询邀请码重复
                    $info = $userModel->where(['registration_code'=>$sole])->count();
                    if($info>0){
                        $this->jkReturn(-1,'网络延时,请稍后重试',array());
                    }
                    //进行注册
                    $userModel->data($request->param());
                    $userModel->user_name = $param['mobile'];
                    $userModel->password = md5(md5($param['password']));
                    $userModel->nick_name = $param['mobile'];
                    $userModel->token =  md5(md5($param['mobile'].time() ));
                    $userModel->disabled = 1;
                    $userModel->rank_id = $rank->rank_id;
                    $userModel->registration_code = $info;
                    $userModel->invitation_code = !empty($param['invitation_code'])?$param['invitation_code']:null;
                    if(!$userModel->allowField(true)->save()){
                        $this->jkReturn(-1,'网络延时,请稍后重试',array());
                    }
                    $userInfo = $userModel
                        ->alias('u')
                        ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
                        ->where(['mobile' => $param['mobile']])
                        ->join('new_rank r','r.rank_id=u.rank_id','left')
                        ->find();
                    $save_money_ranking = $userModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
                    $all = $userModel->group('user_save_money')->count();
                    $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
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

    public function checkMobile(Request $request, UsersModel $userModel)
    {
        $param = $request->param();
        //获取该手机号该类验证码
        $where['mobile'] = $param['mobile'];
        //查看该手机号是否有对应用户
        $userInfo = $userModel->where(['mobile'=>$param['mobile']])->find();
        if(!empty($userInfo->user_id)){
            $this->jkReturn(1,'ok',[]);
        }else{
            $this->jkReturn(-1,'该手机号未注册',[]);
        }
    }

    /**
     * 登录
     * @param Request $request
     * @param UsersModel $usersModel
     */
    public function login(Request $request, UsersModel $usersModel){
        $param = $request->param();
        $where['mobile'] = $param['mobile'];
        $userInfo = $usersModel
            ->alias('u')
            ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
            ->where($where)
            ->join('new_rank r','r.rank_id=u.rank_id','left')
            ->find();
        if(empty($userInfo)){
            $this->jkReturn(-1,"该账号不存在",array());
        }
        if($userInfo->password == md5(md5($param['password']))){
            $upWhere['user_id'] = $userInfo->user_id;
            $userInfo->token = $token = md5(md5($userInfo->mobile.time() ));
            $usersModel->allowField(true)->save(['token'=>$token],$upWhere);
            $save_money_ranking = $usersModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
            $all = $usersModel->group('user_save_money')->count();
            $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
            $this->jkReturn(1,"登陆成功",$userInfo);
        }else{
            $this->jkReturn(-1,"请核对您的账号或密码!",array());
        }
    }

    /**
     * token登录
     * @param Request $request
     * @param UsersModel $usersModel
     */
    public function token_login(Request $request, UsersModel $usersModel){
        $param = $request->param();
        $where['mobile'] = $param['mobile'];
        $userInfo = $usersModel
            ->alias('u')
            ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
            ->where($where)
            ->join('new_rank r','r.rank_id=u.rank_id','left')
            ->find();
        if($userInfo->token == $param['token']){
            $save_money_ranking = $usersModel->query("SELECT * FROM	(SELECT	a.user_id,(SELECT	count(DISTINCT user_save_money) FROM new_users AS b	WHERE	a.user_save_money < b.user_save_money
) + 1 AS rank	FROM new_users AS a	ORDER BY rank) AS u WHERE u.user_id=$userInfo->user_id");
            $all = $usersModel->group('user_save_money')->count();
            $userInfo->save_money_ranking = sprintf('%.0f',(($all-$save_money_ranking[0]['rank'])*100)/$all).'%';
            $this->jkReturn(1,"登陆成功",$userInfo);
        }else{
            $this->jkReturn(-1,"登陆失败",array());
        }
    }


    public function updatePassword(Request $request, LoginService $loginService, LoginModel $loginModel,UsersModel $userModel){
        $param = $request->param();
        $ignore_code = $param['ignore_code'];

        if($ignore_code){
            // 如果不需要验证码，直接修改密码
           $this->modifyPassword($param['mobile'],$param['password'],$param['password_confirm'],$userModel);
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
            $this->modifyPassword($param['mobile'],$param['password'],$param['password_confirm'],$userModel);
        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码错误，请重新获取',array());
        }
    }

    // 修改手机密码
    private function modifyPassword($mobile,$password,$password_confirm,$userModel){
        //查看该手机号是否有对应用户
        $userInfo = $userModel
            ->alias('u')
            ->field('u.*,r.rank_name,r.rank_img,r.rank_num')
            ->where(['mobile' => $mobile])
            ->join('new_rank r','r.rank_id=u.rank_id','left')
            ->find();
        if(!empty($userInfo->user_id)){
            if($password == $password_confirm){
                //进行修改密码
                $password = md5(md5($password));
                if($userModel->save(['password'=>$password],['user_id'=>$userInfo->user_id])){
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
