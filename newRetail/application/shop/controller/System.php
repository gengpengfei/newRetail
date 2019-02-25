<?php
namespace app\shop\controller;
use app\shop\model\SystemConfigModel;
use think\Request;


/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/11
 * Time: 18:51
 */
class System extends Common
{
    public function systemEdit(Request $request,SystemConfigModel $systemConfigModel){
        $systemConfigModel->data($request->param());
        //如果是提交
        if(!empty($systemConfigModel->is_ajax)){
            foreach ($request->param() as $key=>$value){
                $where['code'] = $key;
                $data['value'] = $value;
                $systemConfigModel->allowField(true)->save($data,$where);
            }
            $this->setAdminUserLog("编辑","编辑系统设置" ,'','');
            $this->success("编辑成功");

        }else{
            //获取配置信息
            $system_config = $systemConfigModel->select()->toArray();
            $system_config_top = array();
            $system_config_list = array();
            foreach ($system_config as $item){
                if($item['parent_id'] == 0){
                    array_push($system_config_list, $item);
                }
            }

            //一级权限列表排序
            foreach ($system_config_list as $key=>$value){
                $id[$key] = $value['id'];
                $sort[$key] = $value['sort_order'];
            }
            array_multisort($sort,SORT_NUMERIC,SORT_ASC,$id,SORT_STRING,SORT_ASC,$system_config_list);
            $system_config_top = $system_config_list;
            foreach ($system_config_list as $key=>$first){
                $system_config_list[$key]['children'] = array();
                foreach ($system_config as $i){
                    if($i['parent_id'] == $first['id']){
                        array_push($system_config_list[$key]['children'], $i);
                    }
                }
            }

            //对子级权限进行排序
            foreach ($system_config_list as $k=>$item){
                foreach ($item['children'] as $key=>$value){
                    $id1[$key] = $value['id'];
                    $sort1[$key] = $value['sort_order'];
                }
                array_multisort($sort1,SORT_NUMERIC,SORT_ASC,$id1,SORT_STRING,SORT_ASC,$item['children']);
                $system_config_list[$k] = $item;
            }

            $this->assign('system_config_list', $system_config_list);
            $this->assign('system_config_top', $system_config_top);
            // 模板输出
            return view("System/system_config_info");
        }
    }





}