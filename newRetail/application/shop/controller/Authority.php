<?php
namespace app\shop\controller;
use app\shop\model\ActionModel;
use app\shop\model\StoreLogModel;
use app\shop\model\StoreUserModel;
use app\shop\model\StoreUserActionModel;
use app\shop\model\UserActionModel;
use think\Request;
use think\Session;
use tp5er\Backup;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/5/14
 * Time: 17:51
 */
class Authority extends Common
{
    /**
     * 管理员列表
     * @param StoreUserModel $storeUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserList(StoreUserModel $storeUserModel, Request $request) {
        $storeUserModel->data($request->param());
        if (!empty($storeUserModel->show_count)){
            $show_count = $storeUserModel->show_count;
        }else{
            $show_count = 10;
        }

        $store_id = Session::get("shop_id");
        $shop_user_id = Session::get("shop_user_id");
        $where = " 1=1 and store_id = '$store_id' and disabled = 1";
        if(!empty($storeUserModel->datemin)){
            $where .= " and create_time >= '" . $storeUserModel->datemin . "'";
        }
        if(!empty($storeUserModel->datemax)){
            $where .= " and create_time <= '" . $storeUserModel->datemax . "'";
        }
        if(!empty($storeUserModel->keywords)){
            $keywords = $storeUserModel->keywords;
            $where .= " and (admin_id like '%" . $keywords . "%' or user_name like '%" . $keywords . "%')";
        }

        //排序条件
        if(!empty($storeUserModel->orderBy)){
            $orderBy = $storeUserModel->orderBy;
        }else{
            $orderBy = 'admin_id';
        }
        if(!empty($storeUserModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeUserModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $admin_user_list = $storeUserModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $admin_user_list->render();
        //权限按钮
        $action_code_list = $this->getChileAction('adminUserList');
        // 模板变量赋值
        $this->assign('admin_user_list', $admin_user_list);
        $this->assign('where', $storeUserModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Authority/admin_user_list");
    }

    /**
     * 添加管理员
     * @param StoreUserModel $storeUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserAdd(StoreUserModel $storeUserModel, Request $request)
    {
        $storeUserModel->data($request->param());
        //如果是提交
        if(!empty($storeUserModel->is_ajax)){
            $admin_user_info = $storeUserModel->where(["mobile"=>$storeUserModel->mobile])->find();
            if(!empty($admin_user_info)){
                $this->error("用户手机号已存在");
            }
            $storeUserModel->password = md5(md5($storeUserModel->password));
            $storeUserModel->store_id = Session::get("shop_id");
            $storeUserModel->is_boss = 2;
            $result = $storeUserModel->allowField(true)->save($storeUserModel);
            if($result){
                $admin_id = $storeUserModel->getLastInsID();
                $this->setAdminUserLog("新增","添加管理员用户：" . $admin_id . "-" . $storeUserModel->user_name,$storeUserModel->table,$admin_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Authority/admin_user_info");
        }
    }

    /**
     * 编辑管理员
     * @param StoreUserModel $storeUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserEdit(StoreUserModel $storeUserModel, Request $request){
        $storeUserModel->data($request->param());
        if(empty($storeUserModel->admin_id)){
            $this->error("用户id不能为空");
        }
        //如果是提交
        if(!empty($storeUserModel->is_ajax)){
            if(!empty($storeUserModel->mobile)){
                $admin_user_info = $storeUserModel->where(["mobile"=>$storeUserModel->mobile])->where("admin_id","neq",$storeUserModel->admin_id)->find();
                if(!empty($admin_user_info)){
                    $this->error("用户名已存在");
                }
            }
            $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
            if(!empty($admin_user_info)){
                $upWhere['user_id'] = $storeUserModel->admin_id;
                if(!empty($storeUserModel->repassword)){
                    $storeUserModel->password = md5(md5(123123123));
                }
                $result = $storeUserModel->allowField(true)->save($storeUserModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑管理员用户：" . $storeUserModel->admin_id,$storeUserModel->table,$storeUserModel->admin_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("用户不存在，修改失败");
            }
        }else{
            //获取管理员用户信息
            $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
            if(!empty($admin_user_info)){
                $admin_user_info = $admin_user_info->toArray();
            }
            $this->assign('admin_user_info', $admin_user_info);
            // 模板输出
            return view("Authority/admin_user_info");
        }
    }

    /**
     * 删除管理员
     * @param StoreUserModel $storeUserModel
     * @param Request $request
     */
    public function adminUserDel(StoreUserModel $storeUserModel, Request $request){
        $storeUserModel->data($request->param());
        $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
        if(!empty($admin_user_info)){
            $result = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除管理员用户：" . $storeUserModel->admin_id,$storeUserModel->table,$storeUserModel->admin_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("用户不存在，删除失败");
        }
    }

    /**
     * 编辑管理员权限
     * @param StoreUserModel $storeUserModel
     * @param Request $request
     * @param StoreUserActionModel $userActionModel
     * @param ActionModel $actionModel
     * @return \think\response\View
     */
    public function adminUserRuleEdit(StoreUserModel $storeUserModel, Request $request, StoreUserActionModel $userActionModel, ActionModel $actionModel){
        $storeUserModel->data($request->param());
        if(empty($storeUserModel->admin_id)){
            $this->error("用户id不能为空");
        }
        $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
        if(empty($admin_user_info)){
            $this->error("用户不存在，修改失败");
        }
        if($admin_user_info->user_name == 'admin'){
            $this->error("超级用户权限不能修改");
        }
        //如果是提交
        if(!empty($storeUserModel->is_ajax)){
            $upWhere['admin_user_id'] = $storeUserModel->admin_id;
            $userActionModel->admin_user_id = $storeUserModel->admin_id;
            $user_action = $userActionModel->where($upWhere)->find();
            $admin_action_list = explode(",",$storeUserModel->admin_action_list);
            $userActionModel->admin_action_list = serialize($admin_action_list);
            if(!empty($user_action)){
                $result = $userActionModel->allowField(true)->save($userActionModel,$upWhere);
            }else{
                $result = $userActionModel->allowField(true)->save($userActionModel);
            }
            if($result){
                $this->setAdminUserLog("编辑","删除管理员用户权限：" . $storeUserModel->admin_id . "-" . $userActionModel->admin_action_list,$storeUserModel->table,$storeUserModel->admin_id);
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }

        }else{
            //获取用户的权限栏目
            $user_action_list = $userActionModel->where(['admin_user_id'=>$storeUserModel->admin_id])->find();
            if(empty($user_action_list->admin_action_list)){
                $user_action_list = array();
            }else{
                if($user_action_list->admin_action_list == 'all'){
                    $user_action_list = "all";
                }else{
                    $user_action_list = unserialize($user_action_list->admin_action_list);
                }
            }

            //获取所有功能方法列表
            $action_list = $actionModel->where(['disabled'=>1])->select();
            $leftMenu = empty($action_list)?array():$action_list->toArray();
            //获取侧边栏
            $allMenuNew = array();
            foreach ($leftMenu as $item){
                if($item['parent_id'] == 0){
                    array_push($allMenuNew, $item);
                }
            }

            //一级权限列表排序
            foreach ($allMenuNew as $key=>$value){
                $id[$key] = $value['action_id'];
                $sort[$key] = $value['sort'];
            }
            array_multisort($sort,SORT_NUMERIC,SORT_ASC,$id,SORT_STRING,SORT_ASC,$allMenuNew);
            foreach ($allMenuNew as $key=>$first){
                $allMenuNew[$key]['children'] = array();
                foreach ($leftMenu as $i){
                    if($i['parent_id'] == $first['action_id']){
                        array_push($allMenuNew[$key]['children'], $i);
                    }
                }
            }

            //对子级权限进行排序
            foreach ($allMenuNew as $k=>$item){
                $item['isAll'] = 0;
                $child = 0;
                $child_have = 0;
                foreach ($item['children'] as $key=>$value){
                    $child++;
                    $id1[$key] = $value['action_id'];
                    $sort1[$key] = $value['sort'];
                    if($user_action_list == 'all'){
                        $item['children'][$key]['isHave'] = 1;
                    }else{
                        if(in_array($value['action_id'],$user_action_list)){
                            $item['children'][$key]['isHave'] = 1;
                            $child_have ++ ;
                        }
                    }
                }
                if($child >= $child_have && $child_have != 0){
                    $item['isAll'] = 1;
                }
                array_multisort($sort1,SORT_NUMERIC,SORT_ASC,$id1,SORT_STRING,SORT_ASC,$item['children']);
                $allMenuNew[$k] = $item;

            }
            $this->assign('allMenunew',$allMenuNew);
            $this->assign("admin_user_id",$storeUserModel->admin_id);
            // 模板输出
            return view("Authority/admin_user_rule_info");
        }
    }

    public function adminLog(StoreLogModel $storeLogModel,Request $request){
        $storeLogModel->data($request->param());
        if (!empty($storeLogModel->show_count)){
            $show_count = $storeLogModel->show_count;
        }else{
            $show_count = 10;
        }

        $storeLogModel->data($request->param());
        $store_id = Session::get("shop_id");
        $where = " 1=1 and store_id = '$store_id'";
        if(!empty($storeLogModel->datemin)){
            $where .= " and create_time >= '" . $storeLogModel->datemin . "'";
        }
        if(!empty($storeLogModel->datemax)){
            $where .= " and create_time <= '" . $storeLogModel->datemax . "'";
        }
        if(!empty($storeLogModel->keywords)){
            $keywords = $storeLogModel->keywords;
            $where .= " and (admin_nickname like '%" . $keywords . "%')";
        }
        if(!empty($storeLogModel->logtype)){
            $logtype = $storeLogModel->logtype;
            if($logtype == '登录'){
                $where .= " and log_type = '" . $logtype . "'";
            }else{
                $where .= " and log_type != '" . $logtype . "'";
            }
        }
        //排序条件
        if(!empty($storeLogModel->orderBy)){
            $orderBy = $storeLogModel->orderBy;
        }else{
            $orderBy = 'log_id';
        }
        if(!empty($storeLogModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeLogModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $admin_log_list = $storeLogModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);

        // 获取分页显示
        $page = $admin_log_list->render();


        // 模板变量赋值
        $this->assign('admin_user_list', $admin_log_list);
        $this->assign('where', $storeLogModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Authority/admin_user_log_list");

    }

    public function dbm(){
        $config=array(
            'path'     => './Data/',//数据库备份路径
            'part'     => 20971520,//数据库备份卷大小
            'compress' => 0,//数据库备份文件是否启用压缩 0不压缩 1 压缩
            'level'    => 9 //数据库备份文件压缩级别 1普通 4 一般  9最高
        );
        $db = new Backup($config);

        $db_list = $this->fetch('index',['list'=>$db->dataList()]);
        print_r($db_list);die;
    }

}