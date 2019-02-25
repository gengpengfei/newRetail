<?php
namespace app\admin\controller;
use app\admin\model\AdminUserModel;
use app\admin\model\MobileMessageModel;
use app\admin\model\UsersModel;
use think\Request;

/**
 * 短信供应商发送状态回调接口
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/17
 * Time: 13:27
 */
class Messagereport extends Common
{
    public function getReport(MobileMessageModel $mobileMessageModel) {
        $report = $_REQUEST['report'];
        $reportArr = explode("||",$report);
        $success_count = 0;
        foreach ($reportArr as $item){
            $oneArr = explode(",",$item);
            $msgId = $oneArr[0];
            $status = $oneArr[3];
            if($status == 'DELIVRD'){
                $success_count ++;
            }
        }
        $where['msgid'] = $msgId;
        $mobileMessageModel->report_seccess_count = $success_count;
        $mobileMessageModel->save($mobileMessageModel,$where);
//        $file = fopen("aa.txt",a);
//        fwrite($file,json_encode($_REQUEST));
//        fclose($file);
    }
    public function getUserListByType(Request $request,AdminUserModel $adminUserModel,UsersModel $usersModel){
        $receiveData = $request->param();
        $where = '1=1';
        if($receiveData['to_user_type'] == 0){
            //管理员
            if(!empty($receiveData['search_user_name'])){
                $where .= " and nickname like '%" . $receiveData['search_user_name'] . "%' ";
            }
            $user_list = $adminUserModel->where($where)->field("admin_id as user_id,nickname")->select();
        }else{
            //会员用户
            if(!empty($receiveData['search_user_name'])){
                $where .= " and nick_name like '%" . $receiveData['search_user_name'] . "%' ";
            }
            if(!empty($receiveData['rank_id'])){
                $where .= " and rank_id = " . $receiveData['rank_id'] ;
            }
            $user_list = $usersModel->where($where)->field("nick_name as nickname,user_id,mobile")->select();
        }
        $this->success('获取成功','',$user_list);
    }

}