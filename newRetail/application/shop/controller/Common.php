<?php
namespace app\shop\controller;
use app\shop\model\ActionModel;
use app\shop\model\StoreLogModel;
use app\shop\model\StoreUserActionModel;
use app\shop\service\ValidateService;
use think\Controller;
use think\Request;
use think\Session;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/27
 * Time: 17:51
 */
class Common extends Controller
{
    public function __construct(ValidateService $validateService,Request $request,StoreUserActionModel $userActionModel,ActionModel $actionModel)
    {
        //不验证是否登陆的控制器
        $not_login = ['Login','Common','Messagereport'];
        //不验证权限的控制器
        $not_rule = ['Login','Common','Index','Upload','Messagereport'];
        parent::__construct();
        if(empty(Session::get('shop_user_id')) && !in_array($request->controller(),$not_login)){
            $this->redirect('shop/Login/index');
        }
        $validateService->validate($_POST);
        //验证用户权限
        if(!empty(Session::get('shop_user_id'))){
            //获取用户权限，序列或者all
            if(empty(Session::get("shop_action_list"))){
                $admin_action_list = $userActionModel->where("admin_user_id=".Session::get('shop_user_id'))->find();
                $admin_action_list = $admin_action_list->admin_action_list;
                Session::set("shop_action_list",$admin_action_list);
            }else{
                $admin_action_list = Session::get("shop_action_list");
            }
            //如果需要验证权限
            if(!in_array($request->controller(),$not_rule)){
                if(!empty($admin_action_list)){
                    if($admin_action_list != 'all'){
                        $action_id_list = unserialize($admin_action_list);
                        $action_list = $actionModel->where('action_id','in',$action_id_list)->where('disabled=1')->select();
                    }else {
                        $action_list = $actionModel->where('disabled=1')->select();
                    }
                    //Session::set("shop_action_list",$admin_action_list);
                    $action_list = empty($action_list)?array():$action_list->toArray();
                    Session::set("admin_action_id_list",array_column($action_list,'action_id'));
                    $controller = strtolower($request->controller());
                    $action = strtolower($request->action());
                    //根据code查找控制器id
                    $where['action_code'] = $controller;
                    $where['parent_id'] = 0;
                    $where['disabled'] = 1;
                    $controller_id = $actionModel
                        ->field('action_id')
                        ->where($where)
                        ->select()->toArray();
                    //查询方法id
                    $action_id = $actionModel
                        ->field('action_id')
                        ->where("parent_id in (" . implode(',', array_column($action_list, 'action_id')) . ") and disabled=1 and action_code='$action'")
                        ->find()->action_id;
                    if (!in_array($action_id,session('admin_action_id_list'))){
                        $this->error('您暂时没有此权限，请联系超级管理员');
                    }
                }else{
                    $this->error('系统异常，请重新登录','Shop/Login/index');
                }
            }
        }

    }

    public function showMessage(){
        return $this->showMessageHtml($_REQUEST['msgtype'],$_REQUEST['msg'],$_REQUEST['button'],$_REQUEST['url'],$_REQUEST['gohistory']);
    }
    function showMessageHtml($msgtype,$msg,$button='',$url='',$gohistory=0){
        $this->assign("msgtype",$msgtype);
        $this->assign("msg",$msg);
        $this->assign("button",$button);
        $this->assign("url",$url);
        $this->assign("gohistory",$gohistory);
        return view('Public/message');
    }

    public function setAdminUserLog($type='',$cont='',$table_name='',$table_id='')
    {
        $userLogModel = new StoreLogModel();
        $userLogModel->log_type = $type;
        $userLogModel->content = $cont;
        $userLogModel->table_name = $table_name;
        $userLogModel->table_id = $table_id;
        $userLogModel->store_id = Session::get('shop_id');
        $userLogModel->admin_user_id = Session::get('shop_user_id');
        $userLogModel->admin_nickname = Session::get('shop_user_name');
        $browser = $this->getBrowser();
        $userLogModel->browser = $browser['browser'];
        $userLogModel->version = $browser['version'];
        $userLogModel->ip = request()->ip();
        $userLogModel->allowField(true)->save();
    }

    /*public function setOrderLog($type='',$cont='',$order_id='',$table_name='',$table_id='')
    {
        $orderLogModel = new OrderLogModel();
        $orderLogModel->log_type = $type;
        $orderLogModel->content = $cont;
        $orderLogModel->table_name = $table_name;
        $orderLogModel->table_id = $table_id;
        $orderLogModel->admin_user_id = Session::get('shop_user_id');
        $orderLogModel->admin_nickname = Session::get('admin_nickname');
        $browser = $this->getBrowser();
        $orderLogModel->browser = $browser['browser'];
        $orderLogModel->version = $browser['version'];
        $orderLogModel->ip = request()->ip();
        $orderLogModel->order_id = $order_id;
        $orderLogModel->allowField(true)->save();
    }*/

    /*public function setOrderMessage($cont='',$order_id='')
    {
        $orderMessageModel = new OrderMessageModel();
        $orderMessageModel->order_message = $cont;
        $orderMessageModel->admin_user_id = Session::get('shop_user_id');
        $orderMessageModel->admin_nickname = Session::get('admin_nickname');
        $orderMessageModel->order_id = $order_id;
        $orderMessageModel->allowField(true)->save();
    }*/

    public function getChileAction($parentCode=''){
        $actionModel = new ActionModel();
        //权限按钮
        $action_id = $actionModel->where(["action_code"=>$parentCode,"disabled"=>1])->field('parent_id')->find()->parent_id;
        $where['parent_id'] = $action_id;
        $where['disabled'] = 1;
        $child_action = $actionModel->where($where)->field('action_code')->select()->toArray();
        $action_code_list = array_column($child_action,'action_code');
        return $action_code_list;
    }

    //获取浏览器以及版本号
    protected function getBrowser() {
        global $_SERVER;
        $agent  = $_SERVER['HTTP_USER_AGENT'];
        $browser  = '';
        $browser_ver  = '';

        if (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
            $browser  = 'OmniWeb';
            $browser_ver   = $regs[2];
        }

        if (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
            $browser  = 'Netscape';
            $browser_ver   = $regs[2];
        }

        if (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
            $browser  = 'Safari';
            $browser_ver   = $regs[1];
        }

        if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
            $browser  = 'Internet Explorer';
            $browser_ver   = $regs[1];
        }

        if (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
            $browser  = 'Opera';
            $browser_ver   = $regs[1];
        }

        if (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
            $browser  = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
            $browser_ver   = $regs[1];
        }

        if (preg_match('/Maxthon/i', $agent, $regs)) {
            $browser  = '(Internet Explorer ' .$browser_ver. ') Maxthon';
            $browser_ver   = '';
        }
        if (preg_match('/360SE/i', $agent, $regs)) {
            $browser       = '(Internet Explorer ' .$browser_ver. ') 360SE';
            $browser_ver   = '';
        }
        if (preg_match('/SE 2.x/i', $agent, $regs)) {
            $browser       = '(Internet Explorer ' .$browser_ver. ') 搜狗';
            $browser_ver   = '';
        }

        if (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
            $browser  = 'FireFox';
            $browser_ver   = $regs[1];
        }

        if (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
            $browser  = 'Lynx';
            $browser_ver   = $regs[1];
        }

        if(preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)){
            $browser  = 'Chrome';
            $browser_ver   = $regs[1];

        }

        if ($browser != '') {
            return ['browser'=>$browser,'version'=>$browser_ver];
        } else {
            return ['browser'=>'unknow browser','version'=>'unknow browser version'];
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

    /**
     * 验证输入的手机号码是否合法
     * @access public
     * @param string $mobile_phone
     * @return bool
     */
    function is_mobile( $text ) {
        $search = '/^0?1[3|4|5|6|7|8][0-9]\d{8}$/';
        if ( preg_match( $search, $text ) ) {
            return ( true );
        } else {
            return ( false );
        }
    }
}