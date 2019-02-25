<?php
namespace app\shop\controller;
use app\admin\model\AdminLogModel;
use app\shop\model\ActionModel;
use app\shop\model\StoreOrderModel;
use think\Session;
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/27
 * Time: 17:51
 */
class Index extends Common
{
    public function index(ActionModel $actionModel)
    {
        $leftMenu = session('shop_action_list');
        if ($leftMenu == 'all') {
            $where = "1=1 ";
        } else {
            $arr = unserialize($leftMenu);
            $menu = implode(',',$arr);
            if ($menu == '') {
                $where = " ISNULL(action_id)";
            }else{
                $where = "action_id in ($menu) ";
            }

        }
        //-- 获取一级列表
        $action_list = $actionModel
            ->where("disabled=1 and parent_id=0 and $where")
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        foreach ($action_list as $key=>$v) {
            //-- 获取二级列表
            $action_v = $actionModel
                ->where("disabled=1 and parent_id={$v['action_id']} and level=2 and $where")
                ->order('sort', 'asc')
                ->select()
                ->toArray();
            if(count($action_v)>0){
                $action_list[$key]['children'] = $action_v;
                $action_list[$key]['is_children'] = 1;
            }
        }
        $this->assign('leftMenu', $action_list);
        // 模板输出
        return view("Index/index");
    }
    public function welcome(StoreOrderModel $storeOrderModel, AdminLogModel $adminLogModel)
    {
        $store_id = Session::get('shop_id');
        //店铺订单总数(今日)
        $store_order_count = $storeOrderModel->where("store_id = '$store_id' and create_time >= '" . date("Y-m-d",time()) . "'")->count();
        //店铺销售总额(今日)
        $store_order_money = $storeOrderModel->where("store_id = '$store_id' and create_time >= '" . date("Y-m-d",time()) . "' and (order_state = 'T02' or order_state = 'T03' or order_state = 'T04')")->sum("order_price");
        //店铺销售总额(昨日)
        $store_order_money_yestoday = $storeOrderModel->where("store_id = '$store_id' and create_time >= '" . date("Y-m-d",strtotime("-1 day")) . "' and create_time < '" . date("Y-m-d",time()) . "' and (order_state = 'T02' or order_state = 'T03' or order_state = 'T04')")->sum("order_price");
        //店铺销售总额(近七天)
        $store_order_money_seven = $storeOrderModel->where("store_id = '$store_id' and create_time >= '" . date("Y-m-d",strtotime("-6 day")) . "' and (order_state = 'T02' or order_state = 'T03' or order_state = 'T04')")->sum("order_price");
        $info = array(
            '操作系统'=>PHP_OS,
            '运行环境'=>$_SERVER["SERVER_SOFTWARE"],
            'PHP运行方式'=>php_sapi_name(),
            'ThinkPHP版本'=>THINK_VERSION.' [ <a href="http://thinkphp.cn" target="_blank">查看最新版本</a> ]',
            'serverLimitUpload'=>ini_get('upload_max_filesize'),//上传附件限制
            'serverLimitTime'=>ini_get('max_execution_time').'秒',//执行时间限制
            'serverTime'=>date("Y年n月j日 H:i:s"),//服务器时间
            'serverHost'=>$_SERVER['SERVER_NAME'],//服务器域名
            'serverIp'=>gethostbyname($_SERVER['SERVER_NAME']),//服务器IP
            '剩余空间'=>round((disk_free_space(".")/(1024*1024)),2).'M',
            'register_globals'=>get_cfg_var("register_globals")=="1" ? "ON" : "OFF",
            'magic_quotes_gpc'=>(1===get_magic_quotes_gpc())?'YES':'NO',
            'magic_quotes_runtime'=>(1===get_magic_quotes_runtime())?'YES':'NO',
        );

        $where = " log_type like '%登录%' and admin_user_id = " . Session::get('admin_user_id');
        $adminLog = $adminLogModel
            ->field('content,admin_nickname,create_time,ip')
            ->where($where)
            ->order('log_id DESC')
            ->paginate(10);

        $this->assign('adminLog',$adminLog);
        $this->assign('store_order_count',$store_order_count);
        $this->assign('store_order_money',$store_order_money);
        $this->assign('store_order_money_yestoday',$store_order_money_yestoday);
        $this->assign('store_order_money_seven',$store_order_money_seven);
        $this->assign('serverInfo',$info);
        return view("Index/welcome");
    }
}