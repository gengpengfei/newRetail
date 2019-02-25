<?php
namespace app\shopapi\controller;

use app\shopapi\model\AgreementRuleModel;
use app\shopapi\model\LoginModel;
use app\shopapi\model\StoreAuditModel;
use app\shopapi\model\StoreModel;
use app\shopapi\model\StoreUserModel;
use app\shopapi\service\LoginService;
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
class Admin extends Common {
    use \app\shopapi\traits\BuildParam;

    /*
     * explain:用户个人信息
     * params :@user_id
     * authors:Mr.Geng
     * addTime:2018/3/23 14:18
     */
    public function information(Request $request,StoreUserModel $storeUserModel,StoreModel $storeModel,StoreAuditModel $storeAuditModel) {
        $admin_id = $request->param('admin_id');
        $userInfo = $storeUserModel
            ->where(['admin_id'=>$admin_id])
            ->find();
        $progress = 0;
        if(!empty($userInfo)){
            $progress = 1;
        }
        if(!empty($userInfo->store_id)){
            $progress = 4;
            $storeInfo = $storeModel->where(['store_id'=>$userInfo->store_id])->find();
        }else{
            $storeAuditInfo = $storeAuditModel->where(['admin_id'=>$admin_id])->find();
            if(!empty($storeAuditInfo)){
                $storeInfo = $storeModel->where(['store_id'=>$storeAuditInfo->store_id])->find();
                $progress =2;
                if(!is_null($storeAuditInfo->audit_state)){
                    $progress = 3;
                    if($storeAuditInfo->audit_state == 1){
                        $progress = 4;
                    }
                }
            }
        }
        $data = [
            'user_info'=>$userInfo??[],
            'store_info'=>$storeInfo??[],
            'progress'=>$progress
        ];
        $this->jkReturn(1,'用户个人信息',$data);
    }

    /*
     * explain:修改个人信息
     * params :@user_id ...
     * authors:Mr.Geng
     * addTime:2018/3/23 14:34
     */
    public function saveInformation(Request $request,StoreUserModel $storeUserModel)
    {
        $param = $request->param();
        if(!empty($param['head_img'])){
            $param['head_img'] = urldecode($param['head_img']);
        }
        $res = $storeUserModel->allowField(true)->save($param,['admin_id'=>$param['admin_id']]);
        if(!$res)
            $this->jkReturn(-1,'修改失败','1');
        $userInfo = $storeUserModel->where('admin_id',$param['admin_id'])->find();
        $this->jkReturn(1,'修改成功',$userInfo);
    }

    /*
     * explain:业务员列表
     * addTime:2018/6/6 15:10
     */
    public function memberList(Request $request,StoreUserModel $storeUserModel) {
        $store_id = $request->param('store_id');
        $userList = $storeUserModel
            ->where(['disabled'=>1,'store_id'=>$store_id,'is_boss'=>2])
            ->select();
        $this->jkReturn(1,'业务员列表',$userList);
    }

    /*
     * explain:添加业务员
     * params :@mobile
     * params :@password
     * addTime:2018/6/6 15:10
     */
    public function addMember(Request $request,StoreUserModel $storeUserModel,LoginModel $loginModel,LoginService $loginService) {
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
            //验证通过删除验证码
            $loginModel->where($where)->delete();
            //-- 查看该手机号是否是店铺主
            $checkBoss = $storeUserModel->where("mobile={$param['mobile']} and is_boss=1 and disabled=1")->find();
            if($checkBoss){
                $this->jkReturn(-1,'该手机号下拥有店铺,请更换手机号!',[]);
            }
            //查看该手机号是否在非本店铺外有对应业务员
            $checkNoStore = $storeUserModel->where("mobile={$param['mobile']} and is_boss=2 and store_id<>{$param['store_id']}")->find();
            if($checkNoStore){
                if(!$storeUserModel->save(['disabled'=>0],['admin_id'=>$checkNoStore->admin_id])){
                    $this->jkReturn(-1,'网络延时,请稍后重试',[]);
                }
            }
            //-- 查看该手机号在本店是否注册过业务员
            $user = $storeUserModel->where("mobile={$param['mobile']} and is_boss=0 and store_id={$param['store_id']}")->find();
            if($user){
                $data = [
                    'user_name'=>$param['user_name'],
                    'password'=>md5(md5($param['password'])),
                    'is_boss'=>2,
                    'disabled'=>1
                ];
                if(!$storeUserModel->update($data,['admin_id'=>$user->admin_id])){
                    $this->jkReturn(-1,'添加失败');
                }
            }else{
                //-- 查询该手机号是否是游客用户
                $userNo = $storeUserModel->where("mobile={$param['mobile']} and is_boss=0")->find();
                if($userNo){
                    $data = [
                        'store_id'=>$param['store_id'],
                        'user_name'=>$param['user_name'],
                        'password'=>md5(md5($param['password'])),
                        'is_boss'=>2,
                        'disabled'=>1
                    ];
                    if(!$storeUserModel->update($data,['admin_id'=>$userNo->admin_id])){
                        $this->jkReturn(-1,'添加失败');
                    }
                }else{
                    ///进行注册
                    $storeUser['user_name'] = $param['user_name'];
                    $storeUser['mobile'] = $param['mobile'];
                    $storeUser['store_id'] = $param['store_id'];
                    $storeUser['password'] = md5(md5($param['password']));
                    $storeUser['is_boss'] = 2;
                    $storeUser['disabled'] = 1;
                    $result = $storeUserModel->create($storeUser);
                    if(!$result){
                        $this->jkReturn(-1,'添加失败');
                    }
                }
            }
            $userInfo = $storeUserModel
                ->where(['user_name' => $param['user_name']])
                ->find();
            $this->jkReturn(1,'添加成功',$userInfo);
        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码已过期，请重新获取',[]);
        }
    }

    /*
     * explain:移除业务员
     * params :@user_id
     *
     * addTime:2018/6/6 15:10
     */
    public function delMember(Request $request,StoreUserModel $storeUserModel,LoginModel $loginModel,LoginService $loginService) {
        $admin_id = $request->param('admin_id');
        $param = $request->param();
        //获取该手机号该类验证码
        $where['mobile'] = $param['mobile'];
        $where['code_type'] = $param['code_type'];
        $codeList = $loginModel->where($where)->find();
        if(!empty($codeList->id)) {
            //验证码是否在有效期
            $loginService->checkMobileCodeEffective($codeList->create_time);
            //验证码是否匹配
            $loginService->checkMobileCodeMate($codeList->code, $param['code']);
            $userInfo = $storeUserModel
                ->where(['admin_id' => $admin_id, 'disabled' => 1])
                ->find();
            if ($userInfo) {
                $upWhere['admin_id'] = $admin_id;
                $storeUser['disabled'] = 0;
                $result = $storeUserModel->update($storeUser, $upWhere);
                $userList = $storeUserModel
                    ->where(['disabled' => 1, 'store_id' => $userInfo['store_id'], 'is_boss' => 2])
                    ->select();
                if ($result) {
                    //验证通过删除验证码
                    $loginModel->where($where)->delete();
                    $this->jkReturn(1, '移除成功', $userList);
                } else {
                    $this->jkReturn(-1, '移除失败', $userList);
                }
            } else {
                $this->jkReturn(1, '业务员不存在');
            }
        }else{
            //验证码不存在
            $this->jkReturn(-1,'短信验证码已过期，请重新获取',[]);
        }
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
    public function bindNewMobile(Request $request,LoginModel $loginModel,LoginService $loginService,StoreUserModel $storeUserModel)
    {
        $mobile = $request->param('mobile');
        $msg_code = $request->param('msg_code');
        $admin_id = $request->param('admin_id');
        /**验证手机号是否存在**/
        $isHaveMobile = $storeUserModel->where("mobile = {$mobile}")->find();
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
            $res = $storeUserModel->update(['mobile'  => $mobile],["admin_id"=>$admin_id]);
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
        $this->jkReturn('1','协议',$info);
    }
}