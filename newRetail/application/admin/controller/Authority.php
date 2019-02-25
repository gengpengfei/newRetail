<?php
namespace app\admin\controller;
use app\admin\model\ActionModel;
use app\admin\model\AdminLogModel;
use app\admin\model\AdminUserModel;
use app\admin\model\UserActionModel;
use think\Request;
use tp5er\Backup;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/2
 * Time: 17:51
 */
class Authority extends Common
{
    /**
     * 管理员列表
     * @param AdminUserModel $adminUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserList(AdminUserModel $adminUserModel, Request $request) {
        $adminUserModel->data($request->param());
        if (!empty($adminUserModel->show_count)){
            $show_count = $adminUserModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($adminUserModel->datemin)){
            $where .= " and create_time >= '" . $adminUserModel->datemin . "'";
        }
        if(!empty($adminUserModel->datemax)){
            $where .= " and create_time <= '" . $adminUserModel->datemax . "'";
        }
        if(!empty($adminUserModel->keywords)){
            $keywords = $adminUserModel->keywords;
            $where .= " and (admin_id like '%" . $keywords . "%' or user_name like '%" . $keywords . "%' or nickname like '%". $keywords . "%')";
        }

        //排序条件
        if(!empty($adminUserModel->orderBy)){
            $orderBy = $adminUserModel->orderBy;
        }else{
            $orderBy = 'admin_id';
        }
        if(!empty($adminUserModel->orderByUpOrDown)){
            $orderByUpOrDown = $adminUserModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $admin_user_list = $adminUserModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);

        // 获取分页显示
        $page = $admin_user_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('adminUserList');
        // 模板变量赋值
        $this->assign('admin_user_list', $admin_user_list);
        $this->assign('where', $adminUserModel->toArray());
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
     * @param AdminUserModel $adminUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserAdd(AdminUserModel $adminUserModel, Request $request)
    {
        $adminUserModel->data($request->param());
        //如果是提交
        if(!empty($adminUserModel->is_ajax)){
            $admin_user_info = $adminUserModel->where(["user_name"=>$adminUserModel->user_name])->find();
            if(!empty($admin_user_info)){
                $this->error("用户名已存在");
            }
            $adminUserModel->password = md5(md5($adminUserModel->password));
            $result = $adminUserModel->allowField(true)->save($adminUserModel);
            if($result){
                $admin_id = $adminUserModel->getLastInsID();
                $this->setAdminUserLog("新增","添加管理员用户：" . $admin_id . "-" . $adminUserModel->user_name,$adminUserModel->table,$admin_id);
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
     * @param AdminUserModel $adminUserModel
     * @param Request $request
     * @return \think\response\View
     */
    public function adminUserEdit(AdminUserModel $adminUserModel, Request $request){
        $adminUserModel->data($request->param());
        if(empty($adminUserModel->admin_id)){
            $this->error("用户id不能为空");
        }
        //如果是提交
        if(!empty($adminUserModel->is_ajax)){
            if(!empty($adminUserModel->user_name)){
                $admin_user_info = $adminUserModel->where(["user_name"=>$adminUserModel->user_name])->where("admin_id","neq",$adminUserModel->admin_id)->find();
                if(!empty($admin_user_info)){
                    $this->error("用户名已存在");
                }
            }
            $admin_user_info = $adminUserModel->where(["admin_id"=>$adminUserModel->admin_id])->find();
            if(!empty($admin_user_info)){
                $upWhere['user_id'] = $adminUserModel->admin_id;
                if(!empty($adminUserModel->repassword)){
                    $adminUserModel->password = md5(md5(888888));
                }
                $result = $adminUserModel->allowField(true)->save($adminUserModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑管理员用户：" . $adminUserModel->admin_id,$adminUserModel->table,$adminUserModel->admin_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("用户不存在，修改失败");
            }
        }else{
            //获取管理员用户信息
            $admin_user_info = $adminUserModel->where(["admin_id"=>$adminUserModel->admin_id])->find();
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
     * @param AdminUserModel $adminUserModel
     * @param Request $request
     */
    public function adminUserDel(AdminUserModel $adminUserModel, Request $request){
        $adminUserModel->data($request->param());
        $admin_user_info = $adminUserModel->where(["admin_id"=>$adminUserModel->admin_id])->find();
        if(!empty($admin_user_info)){
            $result = $adminUserModel->where(["admin_id"=>$adminUserModel->admin_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除管理员用户：" . $adminUserModel->admin_id,$adminUserModel->table,$adminUserModel->admin_id );
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
     * @param AdminUserModel $adminUserModel
     * @param Request $request
     * @param UserActionModel $userActionModel
     * @param ActionModel $actionModel
     * @return \think\response\View
     */
    public function adminUserRuleEdit(AdminUserModel $adminUserModel, Request $request, UserActionModel $userActionModel, ActionModel $actionModel){
        $adminUserModel->data($request->param());
        if(empty($adminUserModel->admin_id)){
            $this->error("用户id不能为空");
        }
        $admin_user_info = $adminUserModel->where(["admin_id"=>$adminUserModel->admin_id])->find();
        if(empty($admin_user_info)){
            $this->error("用户不存在，修改失败");
        }
        if($admin_user_info->user_name == 'admin'){
            $this->error("超级用户权限不能修改");
        }
        //如果是提交
        if(!empty($adminUserModel->is_ajax)){
            $upWhere['admin_user_id'] = $adminUserModel->admin_id;
            $userActionModel->admin_user_id = $adminUserModel->admin_id;
            $user_action = $userActionModel->where($upWhere)->find();
            $admin_action_list = explode(",",$adminUserModel->admin_action_list);
            $userActionModel->admin_action_list = serialize($admin_action_list);
            if(!empty($user_action)){
                $result = $userActionModel->allowField(true)->save($userActionModel,$upWhere);
            }else{
                $result = $userActionModel->allowField(true)->save($userActionModel);
            }
            if($result){
                $this->setAdminUserLog("编辑","删除管理员用户权限：" . $adminUserModel->admin_id . "-" . $userActionModel->admin_action_list,$adminUserModel->table,$adminUserModel->admin_id);
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }

        }else{
            //获取用户的权限栏目
            $user_action_list = $userActionModel->where(['admin_user_id'=>$adminUserModel->admin_id])->find();
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

                if($child == $child_have && $child_have != 0){
                    $item['isAll'] = 1;
                }
                array_multisort($sort1,SORT_NUMERIC,SORT_ASC,$id1,SORT_STRING,SORT_ASC,$item['children']);
                $allMenuNew[$k] = $item;

            }
            $this->assign('allMenunew',$allMenuNew);
            $this->assign("admin_user_id",$adminUserModel->admin_id);
            // 模板输出
            return view("Authority/admin_user_rule_info");
        }
    }

    public function adminLog(AdminLogModel $adminLogModel,Request $request){
        $adminLogModel->data($request->param());
        if (!empty($adminLogModel->show_count)){
            $show_count = $adminLogModel->show_count;
        }else{
            $show_count = 10;
        }

        $adminLogModel->data($request->param());
        $where = " 1=1 ";
        if(!empty($adminLogModel->datemin)){
            $where .= " and create_time >= '" . $adminLogModel->datemin . "'";
        }
        if(!empty($adminLogModel->datemax)){
            $where .= " and create_time <= '" . $adminLogModel->datemax . "'";
        }
        if(!empty($adminLogModel->keywords)){
            $keywords = $adminLogModel->keywords;
            $where .= " and (admin_nickname like '%" . $keywords . "%')";
        }
        if(!empty($adminLogModel->logtype)){
            $logtype = $adminLogModel->logtype;
            if($logtype == '登录'){
                $where .= " and log_type = '" . $logtype . "'";
            }else{
                $where .= " and log_type != '" . $logtype . "'";
            }
        }
        //排序条件
        if(!empty($adminLogModel->orderBy)){
            $orderBy = $adminLogModel->orderBy;
        }else{
            $orderBy = 'log_id';
        }
        if(!empty($adminLogModel->orderByUpOrDown)){
            $orderByUpOrDown = $adminLogModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $admin_log_list = $adminLogModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);

        // 获取分页显示
        $page = $admin_log_list->render();


        // 模板变量赋值
        $this->assign('admin_user_list', $admin_log_list);
        $this->assign('where', $adminLogModel->toArray());
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