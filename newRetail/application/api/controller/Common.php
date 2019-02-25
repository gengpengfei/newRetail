<?php
namespace app\api\controller;


use app\api\model\UsersModel;
use app\api\service\ValidateService;

use think\Controller;

class Common extends Controller {

    public function __construct(ValidateService $validateService){
        parent::__construct();
//        $get_json = file_get_contents("php://input");
        //转化为数组
//        $_POST = !empty($get_json)?json_decode($get_json,true):$_POST;
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods: PUT,POST,GET,DELETE,OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        //验证签名
        //$this->checkSign($_POST);
        //验证参数
        $validateService->validate($_POST);
        $this->bindUser();
    }
    /*
     * explain:验证签名
     * params :@sign
     * authors:Mr.Geng
     * addTime:2018/4/8 9:43
     */
    function checkSign($data=array()){
        if(!empty($data) && is_array($data)){
            $sign = $data['sign'];
            if(empty($sign)){
                $this->jkReturn(-1,'缺少参数sign',array(),'0');
            }else{
                unset($data['sign']);
                //排序
                ksort($data);
                $str = '';
                foreach ($data as $item){
                    $str .= $item;
                }
                $str .= '87749CECEA24B1C314CC27CF7952EBC3';
                $firstSign = strtoupper(md5($str));
                //截取第3-18位，共16位
                $str2 = substr($firstSign,2,16);
                $sign2 = strtoupper(md5($str2));
                if($sign == $sign2){
                    return true;
                }else{
                    $this->jkReturn(-1,'签名错误',array(),'0');
                }
            }
        }else{
            $this->jkReturn(-1,'数据格式不正确',array(),'0');
        }
    }
    
    /*
     * explain:绑定用户属性
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/8 9:43
     */
    public function bindUser()
    {
        $param = Request()->param();
        if(request()->has('locationData')){
            $locationData = json_decode(json_encode($param["locationData"]));
            //-- 绑定地址信息
            request()->bind('locationData',$locationData);
        }
        if(request()->has('user_id') && $param['user_id'] != ''){
            $userInfo = UsersModel::where(['user_id'=>$param['user_id'],'disabled'=>1])->find();
            if(empty($userInfo))  $this->jkReturn(-1,'该账户不存在或被冻结,请核实',[]);
            //-- 绑定用户信息
            request()->bind('user',$userInfo);
        }

    }

    /**
     * 统一接口返回模板
     *
     * @param array  $data        返回数据
     * @param string $msg         返回信息
     * @param int    $code        编码
     * @param string $type        类型
     * @param int    $json_option
     */
    public function jkReturn($code,$msg,$data='',$type='',$json_option=0)
    {
        if (empty($type)) {
            $type = "JSON";
        }
        switch (strtoupper($type)) {
            case 'JSON' :
                //---------- 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], $json_option));
            case 'JSONP':
                //----------- 返回JSON数据格式到客户端
                header('Content-Type:application/json; charset=utf-8');
                exit('(' . json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], $json_option) . ');');
            default     :
                //----------- 用于扩展其他返回格式数据
        }
    }
}
