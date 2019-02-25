<?php
namespace app\admin\controller;
use app\admin\model\AgreementRuleModel;
use app\admin\model\BehaviorActionModel;
use app\admin\model\BehaviorLogModel;
use app\admin\model\BehaviorModel;
use app\admin\model\CategoryModel;
use app\admin\model\CouponsModel;
use app\admin\model\NavModel;
use app\admin\model\QueueJobsFailModel;
use app\admin\model\RankModel;
use app\admin\model\RefundReasonModel;
use app\admin\model\StoreCategoryModel;
use app\admin\model\StoreModel;
use app\admin\model\StorePushMessageLogModel;
use app\admin\model\StorePushMessageModel;
use app\admin\model\SystemConfigModel;
use app\admin\model\UserCouponsModel;
use app\admin\model\UserModel;
use app\admin\service\UploadService;
use databackup\BackUp;
use think\Request;
use think\Queue;
use think\Session;


/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/11
 * Time: 18:51
 */
class System extends Common
{
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;
    /**
     * 系统设置
     *
     * @param Request $request
     * @param SystemConfigModel $systemConfigModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/11
     */
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

    /**
     * 用户行为日志
     *
     * @param Request $request
     * @param BehaviorLogModel $behaviorLogModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/9
     */
    public function behaviorLogList(Request $request,BehaviorLogModel $behaviorLogModel)
    {
        $behaviorLogModel->data($request->param());
        if (!empty($behaviorLogModel->show_count)){
            $show_count = $behaviorLogModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($behaviorLogModel->keywords)){
            $keywords = $behaviorLogModel->keywords;
            $where .= " and u.user_name like '%" . $keywords . "%' ";
        }

        //排序条件
        if(!empty($behaviorLogModel->orderBy)){
            $orderBy = $behaviorLogModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($behaviorLogModel->orderByUpOrDown)){
            $orderByUpOrDown = $behaviorLogModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $behaviorLog = $behaviorLogModel
            ->field('b.*,u.user_name')
            ->alias('b')
            ->join('new_users u','u.user_id = b.user_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $behaviorLog->render();
        // 模板变量赋值
        $this->assign('behaviorLog', $behaviorLog);
        $this->assign('where', $behaviorLogModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('behaviorLogList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/behavior_log_list");
    }

    /**
     * 行为定义
     *
     * @param Request $request
     * @param BehaviorModel $behaviorModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/9
     */
    public function behaviorList(Request $request,BehaviorModel $behaviorModel)
    {
        $behaviorModel->data($request->param());
        if (!empty($behaviorModel->show_count)){
            $show_count = $behaviorModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($behaviorModel->orderBy)){
            $orderBy = $behaviorModel->orderBy;
        }else{
            $orderBy = 'behavior_id';
        }
        if(!empty($behaviorModel->orderByUpOrDown)){
            $orderByUpOrDown = $behaviorModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $behavior = $behaviorModel
            ->where(['disabled'=>1])
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $behavior->render();
        // 模板变量赋值
        $this->assign('behavior', $behavior);
        $this->assign('where', $behaviorModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('behaviorList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/behavior_list");
    }

    /**
     * 添加行为定义
     *
     * @param Request $request
     * @param BehaviorModel $behaviorModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function behaviorAdd(Request $request,BehaviorModel $behaviorModel)
    {
        $behaviorInfo=$request->param();
        //如果是提交
        if(!empty($behaviorInfo['is_ajax'])){
            $result = $behaviorModel->create($behaviorInfo);
            if($result){
                $behavior_id = $behaviorModel->getLastInsID();
                $this->setAdminUserLog("新增","添加行为定义：id为" . $behavior_id ,'System',$behavior_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("System/behavior_info");
        }
    }

    /**
     * 编辑行为定义
     *
     * @param Request $request
     * @param BehaviorModel $behaviorModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function behaviorEdit(Request $request,BehaviorModel $behaviorModel)
    {
        $behaviorInfo = $request->param();
        //如果是提交
        if(!empty($behaviorInfo['is_ajax'])){
            $behavior = $behaviorModel->where(["behavior_id"=>$behaviorInfo['behavior_id']])->find();
            if(!empty($behavior)){
                $upWhere['behavior_id'] = $behaviorInfo['behavior_id'];
                $result = $behaviorModel->update($behaviorInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑定义行为：id为" . $behaviorInfo['behavior_id'] ,'System',$behaviorInfo['behavior_id']);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("定义行为不存在，修改失败");
            }
        }else{
            //获取商品信息
            $behavior = $behaviorModel->where(["behavior_id"=>$behaviorInfo['behavior_id']])->find();
            if(!empty($behavior)){
                $behavior = $behavior->toArray();
            }
            $this->assign('behavior', $behavior);
            // 模板输出
            return view("System/behavior_info");
        }
    }

    /**
     * 删除行为定义
     *
     * @param Request $request
     * @param BehaviorModel $behaviorModel
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function behaviorDel(Request $request,BehaviorModel $behaviorModel){
        $behaviorModel->data($request->param());
        if(empty($behaviorModel->behavior_id)){
            $this->error("id不能为空");
        }
        $behavior_id=$behaviorModel->behavior_id;
        //单个删除
        $behavior = $behaviorModel->where(["behavior_id"=>$behavior_id])->find();
        if(!empty($behavior)){
            $result = $behaviorModel->destroy($behavior_id);
            if($result){
                $this->setAdminUserLog("删除","删除行为定义：id为" . $behavior_id ,'System',$behavior_id);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("行为定义不存在，删除失败");
        }
    }

    /**
     * 绑定店铺列表
     *
     * @param Request $request
     * @param BehaviorActionModel $behaviorActionModel
     * @param StoreModel $storeModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function behaviorStoreList(Request $request,BehaviorActionModel $behaviorActionModel,StoreModel $storeModel)
    {
        $behaviorActionModel->data($request->param());
        if (!empty($behaviorActionModel->show_count)){
            $show_count = $behaviorActionModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        //排序条件
        if(!empty($behaviorActionModel->orderBy)){
            $orderBy = $behaviorActionModel->orderBy;
        }else{
            $orderBy = 'behavior_action_id';
        }
        if(!empty($behaviorActionModel->orderByUpOrDown)){
            $orderByUpOrDown = $behaviorActionModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $behaviorAction = $behaviorActionModel
            ->field('b.*,p.behavior_name')
            ->alias('b')
            ->join('new_behavior p','p.behavior_id = b.behavior_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        if (!empty($behaviorAction)){
            foreach ($behaviorAction as &$behavior) {
                $behavior['storeName'] = '';
                if (!empty($behavior['store_id'])) {
                    $storeIds = explode(',',$behavior['store_id']);
                    if (count($storeIds) == 1) {
                        $storeInfo = $storeModel->where(["store_id"=>$storeIds[0]])->find();
                        if(!empty($storeInfo)){
                            $storeInfo = $storeInfo->toArray();
                        }
                        $behavior['storeName'] = $storeInfo['store_name'];
                    }else{
                        $storeName = array();
                        foreach ($storeIds as $storeId) {
                            $storeInfo = $storeModel->where(["store_id"=>$storeId])->find();
                            if(!empty($storeInfo)){
                                $storeInfo = $storeInfo->toArray();
                                $storeName[] = $storeInfo['store_name'];
                            }
                        }
                        $behavior['storeName'] = implode(',',$storeName);
                    }
                }
            }
        }

        // 获取分页显示
        $page = $behaviorAction->render();
        // 模板变量赋值
        $this->assign('behaviorAction', $behaviorAction);
        $this->assign('where', $behaviorActionModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('behaviorStoreList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/behavior_action_list");
    }

    public function behaviorStoreAdd(Request $request,BehaviorActionModel $behaviorActionModel,BehaviorModel $behaviorModel)
    {
        $behaviorAction = $request->param();
        $behaviorInfo = $behaviorModel->paginate();
        $this->assign('behaviorInfo', $behaviorInfo);
        //如果是提交
        if(!empty($behaviorAction['is_ajax'])){
            if(!empty($behaviorAction['stintid'])){
                $behaviorAction['store_id'] = $behaviorAction['stintid'];
            }
            //unserialize
            if ($behaviorAction['score_rule_type'] == 2){
                $score_array = array();
                $score_info = array();
                if (!empty($behaviorAction['min_score']) && !empty($behaviorAction['max_score']) && !empty($behaviorAction['score_integral'])) {
                    foreach ($behaviorAction['min_score'] as $k => &$min_score) {
                        foreach ($behaviorAction['max_score'] as $v => &$max_score) {
                            foreach ($behaviorAction['score_integral'] as $n => &$score_integral) {
                                if ($k == $v && $v == $n) {
                                    $score_array = array(
                                        'min'=>$min_score,
                                        'max'=>$max_score,
                                        'score'=>$score_integral
                                    );
                                }
                            }
                        }
                        $score_info[] = $score_array;
                    }
                }
                $behaviorAction['score_rule_info'] = serialize($score_info);
            }
            if ($behaviorAction['active_rule_type'] == 2){
                $active_array = array();
                $active_info = array();
                if (!empty($behaviorAction['min_active']) && !empty($behaviorAction['max_active']) && !empty($behaviorAction['active_integral'])) {
                    foreach ($behaviorAction['min_active'] as $k => &$min_active) {
                        foreach ($behaviorAction['max_active'] as $v => &$max_active) {
                            foreach ($behaviorAction['active_integral'] as $n => &$active_integral) {
                                if ($k == $v && $v == $n) {
                                    $active_array = array(
                                        'min'=>$min_active,
                                        'max'=>$max_active,
                                        'active'=>$active_integral
                                    );
                                }
                            }
                        }
                        $active_info[] = $active_array;
                    }
                }
                $behaviorAction['active_rule_info'] = serialize($active_info);
            }
            $result = $behaviorActionModel->create($behaviorAction);
            if($result){
                $behavior_action_id = $behaviorActionModel->getLastInsID();
                $this->setAdminUserLog("新增","添加绑定店铺：id为" . $behavior_action_id ,'IntegralShop',$behavior_action_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $active_rule_info  = array(array('min' => 0, 'max' => 0, 'active' => 0));
            $score_rule_info  = array(array('min' => 0, 'max' => 0, 'score' => 0));
            $this->assign('active_rule_info', $active_rule_info);
            $this->assign('score_rule_info', $score_rule_info);
            // 模板输出
            return view("System/behavior_action_info");
        }
    }

    public function behaviorStoreEdit(Request $request,BehaviorModel $behaviorModel,BehaviorActionModel $behaviorActionModel,StoreModel $storeModel)
    {
        $behaviorStoreInfo = $request->param();
        $behaviorInfo = $behaviorModel->paginate();
        $this->assign('behaviorInfo', $behaviorInfo);
        //如果是提交
        if(!empty($behaviorStoreInfo['is_ajax'])){
            $behaviorStore = $behaviorActionModel->where(["behavior_action_id"=>$behaviorStoreInfo['behavior_action_id']])->find();
            if(!empty($behaviorStore)){
                //unserialize
                if ($behaviorStoreInfo['score_rule_type'] == 2){
                    $score_array = array();
                    $score_info = array();
                    if (!empty($behaviorStoreInfo['min_score']) && !empty($behaviorStoreInfo['max_score']) && !empty($behaviorStoreInfo['score_integral'])) {
                        foreach ($behaviorStoreInfo['min_score'] as $k => &$min_score) {
                            foreach ($behaviorStoreInfo['max_score'] as $v => &$max_score) {
                                foreach ($behaviorStoreInfo['score_integral'] as $n => &$score_integral) {
                                    if ($k == $v && $v == $n) {
                                        $score_array = array(
                                            'min'=>$min_score,
                                            'max'=>$max_score,
                                            'score'=>$score_integral
                                        );
                                    }
                                }
                            }
                            $score_info[] = $score_array;
                        }
                    }
                    $behaviorStoreInfo['score_rule_info'] = serialize($score_info);
                }
                if ($behaviorStoreInfo['active_rule_type'] == 2){
                    $active_array = array();
                    $active_info = array();
                    if (!empty($behaviorStoreInfo['min_active']) && !empty($behaviorStoreInfo['max_active']) && !empty($behaviorStoreInfo['active_integral'])) {
                        foreach ($behaviorStoreInfo['min_active'] as $k => &$min_active) {
                            foreach ($behaviorStoreInfo['max_active'] as $v => &$max_active) {
                                foreach ($behaviorStoreInfo['active_integral'] as $n => &$active_integral) {
                                    if ($k == $v && $v == $n) {
                                        $active_array = array(
                                            'min'=>$min_active,
                                            'max'=>$max_active,
                                            'active'=>$active_integral
                                        );
                                    }
                                }
                            }
                            $active_info[] = $active_array;
                        }
                    }
                    $behaviorStoreInfo['active_rule_info'] = serialize($active_info);
                }
                if(!empty($behaviorStoreInfo['stintid'])){
                    $behaviorStoreInfo['store_id'] = $behaviorStoreInfo['stintid'];
                }
                $upWhere['behavior_action_id'] = $behaviorStoreInfo['behavior_action_id'];
                $result = $behaviorActionModel->update($behaviorStoreInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑行为绑定店铺：id为" . $behaviorStoreInfo['behavior_action_id'] ,'System',$behaviorStoreInfo['behavior_id']);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("行为绑定店铺不存在，修改失败");
            }
        }else{
            //获取商品信息
            $behaviorStore = $behaviorActionModel->where(["behavior_action_id"=>$behaviorStoreInfo['behavior_action_id']])->find();
            $score_rule_info = array();
            $active_rule_info = array();
            $list = array();
            if(!empty($behaviorStore)){
                $active_rule_info  = array(array('min' => 0, 'max' => 0, 'active' => 0));
                $score_rule_info  = array(array('min' => 0, 'max' => 0, 'score' => 0));

                if ($behaviorStore->score_rule_type == 2 && !empty($behaviorStore->score_rule_info)){
                   $score_rule_info = unserialize($behaviorStore->score_rule_info);
                }
                if ($behaviorStore->active_rule_type == 2 && !empty($behaviorStore->active_rule_info)){
                    $active_rule_info = unserialize($behaviorStore->active_rule_info);
                }
                if (!empty($behaviorStore->store_id)) {
                    $where='disabled =1 and store_id in ('.$behaviorStore->store_id.') and audit_state = 1';
                    $list = $storeModel->field(['store_id as id','store_name as name'])->where($where)->select();
                }
                $behaviorStore = $behaviorStore->toArray();
            }
            $this->assign('storeList',$list);
            $this->assign('behaviorStore', $behaviorStore);
            $this->assign('active_rule_info', $active_rule_info);
            $this->assign('score_rule_info', $score_rule_info);
            // 模板输出
            return view("System/behavior_Action_info");
        }
    }

    //删除
    public function behaviorStoreDel(Request $request,BehaviorActionModel $behaviorActionModel){
        $behaviorActionModel->data($request->param());
        if(empty($behaviorActionModel->behavior_action_id)){
            $this->error("id无效");
        }
        $behavior_action_id=$behaviorActionModel->behavior_action_id;
        $nav_info = $behaviorActionModel->where(["behavior_action_id"=>$behavior_action_id])->find();
        if(empty($nav_info)){
            $this->error("该信息不存在");
        }
        if (!$nav_info->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除积分设置:id为$behavior_action_id","new_behavior_action",$behavior_action_id);
        $this->success('删除成功');
    }

    /**
     * 店铺设置
     *
     * @param BehaviorActionModel $behaviorActionModel
     * @param StoreModel $storeModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function storeList(BehaviorActionModel $behaviorActionModel,StoreModel $storeModel,Request $request){
        $behavior = $request->param();
        $behavior_action_id = $behavior['behavior_action_id'];
        if(!empty($behavior_action_id)){
            $where='behavior_action_id = '.$behavior_action_id;
        }else{
            $this->error('该信息有误');
        }
        $behaviorInfo = $behaviorActionModel->where($where)->find();

        //如果是提交
        if(!empty($behavior['is_ajax'])){
            $store_info = array();
            if(!empty($behavior['stintid'])){
                $store_id = $behavior['stintid'];
                $store_info['store_id'] = $store_id;
            }
            $upWhere['behavior_action_id'] = $behavior_action_id;
            $result = $behaviorActionModel->update($store_info,$upWhere);
            if ($result){
                $this->setAdminUserLog("编辑","编辑绑定店铺:id为$store_info","new_coupons",$store_info);
                $this->success('编辑成功','Admin/System/behaviorStoreList');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $where='disabled =1 and ';
            $where.='store_id in ('.$behaviorInfo->store_id.') and audit_state = 1';
            $list=$storeModel->field(['store_id as id','store_name as name'])->where($where)->select();
            $this->assign('behavior_action_id',$behavior_action_id);
            $this->assign('behaviorInfo',$behaviorInfo);
            $this->assign('storeList',$list);
            // 模板输出
            return view("System/store_list");
        }
    }

    /**
     * @param StoreModel $storeModel
     * @param Request $request
     * @return false|\PDOStatement|string|\think\Collection
     * @Author: guanyl
     * @Date: 2018/5/10
     */
    public function searchAdd(StoreModel $storeModel,Request $request){
        $storeModel->data($request->param());
        if (!empty($storeModel->keyWord)){
            $keyword=$storeModel->keyWord;
        }
        //店铺
        $where='disabled = 1 and audit_state =1 and ';
        $where.= "(store_name like '%" . $keyword . "%' or store_desc like '%" . $keyword . "%'or store_keywords like '%" . $keyword. "%')";
        $ajaxlist=$storeModel->field(['store_id as id','store_name as name'])->where($where)->select();
        return $ajaxlist;
    }

    /*
     * 行业模块
     * */
    //行业列表
    public function navList(NavModel $navModel,Request $request){
        $navModel->data($request->param());
        //获取分页数
        if (!empty($navModel->show_count)){
            $show_count = $navModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($navModel->keywords)){
            $keywords = $navModel->keywords;
            $where .= " and (nav_name like '%" . $keywords . "%' or nav_desc like '%".$keywords."%')";
        }
        //排序条件
        if(!empty($navModel->orderBy)){
            $orderBy = $navModel->orderBy;
        }else{
            $orderBy = 'nav_id';
        }
        if(!empty($navModel->orderByUpOrDown)){
            $orderByUpOrDown = $navModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $navList=$navModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$navList->appends($parmas)->render();
        //获取券总量
        $this->assign('navlist',$navList);
        $this->assign('page',$page);
        $this->assign('where', $navModel->toArray());
        $this->assign('proNum',$navList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("System/nav_list");
    }
    //添加行业
    public function navAdd(NavModel $navModel,UploadService $uploadService,Request $request){
        $navModel->data($request->param());
        //如果是提交
        if(!empty($navModel->is_ajax)){
            if(!$navModel->allowField(true)->save($navModel)){
                $this->error("添加失败",'/admin/System/navlist');
            }
            $add_nav_id=$navModel->getLastInsID();
            $this->setAdminUserLog("新增","添加行业:id为$add_nav_id","new_nav",$add_nav_id);
            //获取图片
            $file=request()->file('nav_img');
            if (!empty($file)){
                $imgUrl='/images/nav/detail/'.$add_nav_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$navModel->where('nav_id='.$add_nav_id)->find();
                $storeobj->nav_img=$result;
                if($storeobj->save()){
                    $this->success("添加成功",'/admin/System/navlist');
                }else{
                    $this->error("图片上传失败,行业添加成功",'/admin/System/navlist');
                }
            }else{
                $this->success("添加成功",'/admin/System/navlist');
            }
        }else{
            $this->assign('is_type',1);
            // 模板输出
            return view("System/nav_info");
        }
    }
    //修改行业
    public function navEdit(NavModel $navModel,UploadService $uploadService,Request $request){
        $navModel->data($request->param());
        if(empty($navModel->nav_id)){
            $this->error("行业id无效",'/admin/System/navlist');
        }
        $nav_id=$navModel->nav_id;
        $nav_info = $navModel->where(["nav_id"=>$nav_id])->find();
        if(empty($nav_info)){
            $this->error("该行业信息不存在",'/admin/System/navlist');
        }
        //如果是提交
        if(!empty($navModel->is_ajax)){
            $upWhere['nav_id'] = $nav_id;
            if(!$navModel->allowField(true)->save($navModel,$upWhere)){
                $this->error("添加失败",'/admin/System/navlist');
            }
            $this->setAdminUserLog("编辑","修改行业:id为$nav_id","new_nav",$nav_id);
            //获取图片
            $file=request()->file('nav_img');
            if (!empty($file)){
                $oldimg=$nav_info->nav_img;
                $imgUrl='/images/nav/detail/'.$nav_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$navModel->where('nav_id='.$nav_id)->find();
                $storeobj->nav_img=$result;
                if($storeobj->save()){
                    $uploadService->delimage($oldimg);
                }else{
                    $this->error("图片上传失败,行业信息成功",'/admin/System/navlist');
                }
            }else{
                $this->success("修改成功",'/admin/System/navlist');
            }
        }else{
            $this->assign('navid',$nav_id);
            $this->assign('navinfo',$nav_info);
            $this->assign('is_type',2);
            // 模板输出
            return view("System/nav_info");
        }
    }
    //行业删除
    public function navDel(NavModel $navModel,CategoryModel $categoryModel,Request $request){
        $navModel->data($request->param());
        if(empty($navModel->nav_id)){
            $this->error("行业id无效");
        }
        $nav_id=$navModel->nav_id;
        $nav_info = $navModel->where(["nav_id"=>$nav_id])->find();
        if(empty($nav_info)){
            $this->error("该行业信息不存在");
        }
        $categorylist=$categoryModel->where('nav_id = '.$nav_id)->select();
        if (!empty($categorylist->toArray())){
            $this->error("该行业下有分类 ,删除失败");
        }
        if (!$nav_info->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除行业:id为$nav_id","new_nav",$nav_id);
        $this->success('删除成功');
    }

    /*
     * 分类列表
     * */
    //分类列表
    public function categoryList(CategoryModel $categoryModel,NavModel $navModel,Request $request){
        $categoryModel->data($request->param());
        //定义字段
        $categoryfield=array('category_id','category_name','nav_name','parent_id','grade','is_show_nav','a.sort_order','category_desc','a.disabled');
        //获取分页数
        if (!empty($categoryModel->show_count)){
            $show_count = $categoryModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($categoryModel->keywords)){
            $keywords = $categoryModel->keywords;
            $where .= " and (category_name like '%" . $keywords . "%' or category_desc like '%".$keywords."%' or category_keywords like '%".$keywords."%')";
        }
        //排序条件
        if(!empty($categoryModel->orderBy)){
            $orderBy = $categoryModel->orderBy;
        }else{
            $orderBy = 'category_id';
        }
        if(!empty($categoryModel->orderByUpOrDown)){
            $orderByUpOrDown = $categoryModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $navsql=$navModel->buildSql();
        $categoryList = $categoryModel
            ->alias('a')
            ->join([$navsql=> 'u'],'a.nav_id = u.nav_id','LEFT')
            ->field($categoryfield)
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$categoryList->appends($parmas)->render();
        //获取券总量
        $this->assign('categoryList',$categoryList);
        $this->assign('page',$page);
        $this->assign('where', $categoryModel->toArray());
        $this->assign('pronum',$categoryList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("System/category_list");
    }
    //添加分类
    public function categoryAdd(CategoryModel $categoryModel,NavModel $navModel,UploadService $uploadService,Request $request){
        $categoryModel->data($request->param());
        //如果是提交
        if(!empty($categoryModel->is_ajax)){
            if(!$categoryModel->allowField(true)->save($categoryModel)){
                $this->error("添加失败",'/admin/System/categoryList');
            }
            $add_category_id=$categoryModel->getLastInsID();
            $this->setAdminUserLog("新增","添加分类:id为$add_category_id","new_category",$add_category_id);
            //获取图片
            $file=request()->file('category_img');
            if (!empty($file)){
                $imgUrl='/images/category/detail/'.$add_category_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$categoryModel->where('category_id='.$add_category_id)->find();
                $storeobj->category_img=$result;
                if($storeobj->save()){
                    $this->success("添加成功",'/admin/System/categoryList');
                }else{
                    $this->error("图片上传失败,分类添加成功",'/admin/System/categoryList');
                }
            }else{
                $this->success("添加成功",'/admin/System/categoryList');
            }
        }else{
            //行业列表
            $navList=$navModel->field(['nav_id','nav_name'])->where('disabled = 1')->select();
            $this->assign('navlist',$navList);
            //获取分类列表
            $categoryList=$categoryModel->field(['category_id','category_name','grade'])->where('disabled =1 and grade = 1')->select()->toArray();
            if (!empty($categoryList)){
                foreach ($categoryList as $key =>$value){
                    $childcategory=$categoryModel->getchildCategory($value);
                    $categoryList[$key]['child']=$childcategory;
                }
            }
            $this->assign('categoryList',$categoryList);
            $this->assign('is_type',1);
            // 模板输出
            return view("System/category_info");
        }
    }
    //修改分类
    public function categoryEdit(CategoryModel $categoryModel,NavModel $navModel,UploadService $uploadService,Request $request){
        $categoryModel->data($request->param());
        if(empty($categoryModel->category_id)){
            $this->error("该分类id不存在",'/admin/System/categoryList');
        }
        $category_id=$categoryModel->category_id;
        $category_info = $categoryModel->where(["category_id"=>$category_id])->find();
        if(empty($category_info)){
            $this->error("该分类信息不存在",'/admin/System/categoryList');
        }
        //如果是提交
        if(!empty($categoryModel->is_ajax)){
            $upWhere['category_id'] = $category_id;
            if(!$categoryModel->allowField(true)->save($categoryModel,$upWhere)){
                $this->error("添加失败",'/admin/System/categoryList');
            }
            $this->setAdminUserLog("编辑","修改分类:id为$category_id","new_category",$category_id);
            //获取图片
            $file=request()->file('category_img');
            if (!empty($file)){
                $oldimg=$category_info->category_img;
                $imgUrl='/images/category/detail/'.$category_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$categoryModel->where('category_id='.$category_id)->find();
                $storeobj->category_img=$result;
                if($storeobj->save()){
                    $uploadService->delimage($oldimg);
                    $this->success("修改成功",'/admin/System/categoryList');
                }else{
                    $this->error("图片上传失败,分类修改成功",'/admin/System/categoryList');
                }
            }else{
                $this->success("修改成功",'/admin/System/categoryList');
            }
        }else{
            //行业列表
            $navList=$navModel->field(['nav_id','nav_name'])->where('disabled = 1')->select();
            $this->assign('navlist',$navList);
            //获取分类列表
            $categoryList=$categoryModel->field(['category_id','category_name','grade'])->where('disabled =1 and grade = 1')->select()->toArray();
            if (!empty($categoryList)){
                foreach ($categoryList as $key =>$value){
                    $childcategory=$categoryModel->getchildCategory($value);
                    $categoryList[$key]['child']=$childcategory;
                }
            }
            $this->assign('categoryList',$categoryList);
            $this->assign('is_type',2);
            $this->assign('categoryinfo',$category_info);
            $this->assign('categoryid',$category_id);
            // 模板输出
            return view("System/category_info");
        }
    }
    //删除分类
    public function categoryDel(CategoryModel $categoryModel,Request $request){
        $categoryModel->data($request->param());
        if(empty($categoryModel->category_id)){
            $this->error("该分类id不存在");
        }
        $category_id=$categoryModel->category_id;
        $category_info = $categoryModel->where(["category_id"=>$category_id])->find();
        if(empty($category_info)){
            $this->error("该分类信息不存在");
        }
        $categoryList=$categoryModel->where('parent_id = '.$category_id)->select();
        if (!empty($categoryList->toArray())){
            $this->error("该行业下有子分类 ,删除失败");
        }
        if (!$category_info->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除分类:id为$category_id","new_category",$category_id);
        $this->success('删除成功');
    }

    /*
     * 优惠券模块(用户红包)
     * */
    //优惠券列表
    public function couponsList(CouponsModel $couponsModel,RankModel $rankModel,Request $request){
        // 获取推荐单品
        $couponsModel->data($request->param());
        //获取分页数
        if (!empty($couponsModel->show_count)){
            $show_count = $couponsModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($couponsModel->issale)){
            $couponstype=$couponsModel->issale-1;
            $where .= " and coupons_type = '" . $couponstype . "'";
        }
        if(!empty($couponsModel->datemin)){
            $where .= " and use_end_date > '" . $couponsModel->datemin . "'";
        }
        if(!empty($couponsModel->datemax)){
            $where .= " and use_start_date < '" . $couponsModel->datemax . "'";
        }
        if(!empty($couponsModel->datemin)&&!empty($couponsModel->datemax)&&$couponsModel->datemin>$couponsModel->datemax){
            $this->error("请正确选择时间");
        }
        if(!empty($couponsModel->keywords)){
            $keywords = $couponsModel->keywords;
            $where .= " and (coupons_name like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($couponsModel->orderBy)){
            $orderBy = $couponsModel->orderBy;
        }else{
            $orderBy = 'coupons_id';
        }
        if(!empty($couponsModel->orderByUpOrDown)){
            $orderByUpOrDown = $couponsModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $ranksql=$rankModel->buildSql();
        $couponsList=$couponsModel->alias('a')->join([$ranksql=> 'w'],'a.use_rank = w.rank_id','LEFT')->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$couponsList->appends($parmas)->render();
        //获取券总量
        $this->assign('couponsList',$couponsList);
        $this->assign('page',$page);
        $this->assign('where', $couponsModel->toArray());
        $this->assign('pronum',$couponsList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //模块输出
        return view("System/coupons_list");
    }

    //添加优惠券
    public function couponsAdd(CouponsModel $couponsModel,RankModel $rankModel,UploadService $uploadService,Request $request){
        $couponsModel->data($request->param());
        //如果是提交
        if(!empty($couponsModel->is_ajax)){
            $couponsModel->coupons_desc = $couponsModel->editorValue;
            if(!$couponsModel->allowField(true)->save($couponsModel)){
                $this->error("添加失败",'/admin/System/couponsList');
            }
            $add_coupons_id=$couponsModel->getLastInsID();
            $this->setAdminUserLog("新增","添加系统优惠券:id为$add_coupons_id","new_coupons",$add_coupons_id);
            $storeobj=$couponsModel->where('coupons_id='.$add_coupons_id)->find();
            //获取图片
            $file=request()->file('coupons_img');
            if (!empty($file)){
                $imgUrl='/images/coupons/detail/'.$couponsModel->coupons_type.'/'.$add_coupons_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj->coupons_img = $result;
            }
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/coupons/coupons_desc/';
                $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                if($newImgName){
                    $newImgName = str_replace("./","$baseUrl/",$newImgName);
                    //替换content
                    $couponsModel->coupons_desc = str_replace($item,$newImgName,$couponsModel->coupons_desc);
                    //删除原图
                    unlink($image);
                }
            }
            $storeobj->coupons_desc = $couponsModel->coupons_desc;
            if(!$storeobj->save()){
                $this->error("图片上传失败,优惠券添加成功",'/admin/System/couponsList');
            }
            $this->success("添加成功",'/admin/System/couponsList');
        }else{
            //清除上传图片session
            Session::delete('uploadimg');
            //获取等级
            $ranklist=$rankModel->field(['rank_name','rank_id'])->select();
            $this->assign('ranklist', $ranklist);
            // 模板输出
            return view("System/coupons_add");
        }
    }

    //修改优惠券
    public function couponsEdit(CouponsModel $couponsModel,RankModel $rankModel,Request $request,UploadService $uploadService){
        $couponsModel->data($request->param());
        if(empty($couponsModel->coupons_id)){
            $this->error("优惠券id无效",'/admin/System/couponsList');
        }
        $coupons_id=$couponsModel->coupons_id;
        $coupons_info = $couponsModel->where(["coupons_id"=>$coupons_id])->find();
        if(empty($coupons_info)){
            $this->error("该优惠券不存在",'/admin/System/couponsList');
        }
        $coupons_info['coupons_desc'] = htmlspecialchars_decode($coupons_info['coupons_desc']);
        //如果是提交
        if(!empty($couponsModel->is_ajax)){
            $couponsModel->coupons_desc = $couponsModel->editorValue;
            $upWhere['coupons_id'] = $coupons_id;
            if(!$couponsModel->allowField(true)->save($couponsModel,$upWhere)){
                $this->error("修改失败",'/admin/System/couponsList');
            }
            $this->setAdminUserLog("编辑","修改优惠券:id为$coupons_id","new_coupons",$coupons_id);
            $storeobj=$couponsModel->where('coupons_id='.$coupons_id)->find();
            //获取图片
            $file=request()->file('coupons_img');
            $oldimg = '';
            if (!empty($file)){
                $oldimg=$coupons_info->coupons_img;
                $imgUrl='/images/coupons/detail/'.$couponsModel->coupons_type.'/'.$coupons_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj->coupons_img=$result;

            }
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/coupons/coupons_desc/';
                $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                if($newImgName){
                    $newImgName = str_replace("./","$baseUrl/",$newImgName);
                    //替换content
                    $couponsModel->coupons_desc = str_replace($item,$newImgName,$couponsModel->coupons_desc);
                    //删除原图
                    unlink($image);
                }
            }
            $storeobj->coupons_desc = $couponsModel->coupons_desc;
            if($storeobj->save()){
                if (!empty($oldimg)) {
                    $uploadService->delimage($oldimg);
                }
            }else{
                $this->error("图片上传失败,优惠券修改成功",'/admin/System/couponsList');
            }
            $this->success("修改成功",'/admin/System/couponsList');
        }else{
            //清除上传图片session
            Session::delete('uploadimg');
            //获取等级
            $ranklist=$rankModel->field(['rank_name','rank_id'])->select();
            $this->assign('ranklist', $ranklist);
            $this->assign('couponsinfo', $coupons_info);
            // 模板输出
            return view("System/coupons_edit");
        }
    }

    //优惠券信息(用户领取信息)
    public function couponsInfo(CouponsModel $couponsModel,UserModel $userModel,UserCouponsModel $userCouponsModel,Request $request){
        $userCouponsModel->data($request->param());
        //获取分页数
        if (!empty($userCouponsModel->show_count)){
            $show_count = $userCouponsModel->show_count;
        }else{
            $show_count = 10;
        }
        $coupons_id=$userCouponsModel->coupons_id;
        $where='a.coupons_id = '.$coupons_id;
        $usersql=$userModel->buildSql();
        $userCouponsList = $userCouponsModel
            ->alias('a')
            ->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')
            ->where($where)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$userCouponsList->appends($parmas)->render();
        $this->assign('userCouponsList',$userCouponsList);
        $this->assign('page',$page);
        $this->assign('show_count', $show_count);
        $this->assign('coupons_id', $coupons_id);
        // 模板输出
        return view("System/coupons_info");
    }

    //优惠券限制设置
    public function couponsTint(CouponsModel $couponsModel,StoreCategoryModel $storeCategoryModel,StoreModel $storemodel,Request $request){
        $couponsModel->data($request->param());
        $coupons_id=$couponsModel->coupons_id;
        if(!empty($coupons_id)){
            $where='coupons_id = '.$coupons_id;
        }else{
            $this->error('该优惠券信息有误');
        }
        $couponsinfo=$couponsModel->where($where)->find();
        //如果是提交
        if(!empty($couponsModel->is_ajax)){
            $use_scope=$couponsModel->attr_rule_id;
            $couponsinfo->use_scope=$use_scope;
            if(!empty($couponsModel->stintid)){
                $use_scope_info=$couponsModel->stintid;
                $couponsinfo->use_scope_info=$use_scope_info;
            }
            if ($couponsinfo->save()){
                $this->setAdminUserLog("编辑","添加优惠券限制:id为$coupons_id","new_coupons",$coupons_id);
                $this->success('添加成功','admin/System/couponsList');
            }else{
                $this->error('添加失败');
            }
        }else{
            $usescope=(string)$couponsinfo->use_scope;
            $usescopeinfo=$couponsinfo->use_scope_info;
            if (!empty($usescopeinfo)){
                $usescope=$usescope+1;
            }
            if ($usescope==1){
                $where='disabled =1 and ';
                $where.='store_category_id in ('.$usescopeinfo.')';
                $list=$storeCategoryModel->field(['store_category_id as id','category_name as name'])->where($where)->select();
            }elseif($usescope==2){
                $where='disabled =1 and ';
                $where.='store_id in ('.$usescopeinfo.') and audit_state = 1';
                $list=$storemodel->field(['store_id as id','store_name as name'])->where($where)->select();
            }

            $this->assign('coupons_id',$couponsinfo->coupons_id);
            $this->assign('couponsinfo',$couponsinfo);
            $this->assign('scopelist',$list);
            // 模板输出
            return view("System/coupons_tint");
        }
    }

    //店铺或分类搜索,添加到店铺限制
    public function searchAddTint(StoreModel $storeModel,StoreCategoryModel $storeCategoryModel,Request $request,NavModel $navModel,CategoryModel $categoryModel,UserModel $userModel){
        $storeModel->data($request->param());
        if (!empty($storeModel->stintkind)){
            $stintkind=$storeModel->stintkind;
        }
        if (!empty($storeModel->keyWord)){
            $keyword=$storeModel->keyWord;
        }
        if($stintkind==2){
            //店铺
            $where='disabled = 1 and audit_state =1 and ';
            if (!empty($storeModel->rule_range)){
                $rule_range = $storeModel->rule_range;
                $where .= " nav_id = " . $rule_range . " and ";
            }
            $where.= "(store_name like '%" . $keyword . "%' or store_desc like '%" . $keyword . "%'or store_keywords like '%" . $keyword. "%')";
            $ajaxlist=$storeModel->field(['store_id as id','store_name as name'])->where($where)->select();
        }elseif($stintkind==1){
            //店铺分类
            $where='disabled = 1 and ';
            $where.= "(category_name like '%" . $keyword . "%' or category_desc like '%" . $keyword . "%'or category_keywords like '%" . $keyword. "%')";
            $ajaxlist=$storeCategoryModel->field(['store_category_id as id','category_name as name'])->where($where)->select();
        }elseif($stintkind==3){
            //行业
            $where='disabled = 1 and ';
            $where.= "(nav_name like '%" . $keyword . "%' or nav_desc like '%" . $keyword . "%')";
            $ajaxlist=$navModel->field(['nav_id as id','nav_name as name'])->where($where)->select();
        }elseif($stintkind==4){
            //总分类
            $where='disabled = 1 and ';
            $where.= "(category_name like '%" . $keyword . "%' or category_desc like '%" . $keyword . "%'or category_keywords like '%" . $keyword. "%')";
            $ajaxlist=$categoryModel->field(['category_id as id','category_name as name'])->where($where)->select();
        }elseif($stintkind==5){
            //总分类
            $where='disabled = 1 and ';
            $where.= "(user_name like '%" . $keyword . "%' or mobile like '%" . $keyword . "%')";
            $ajaxlist=$userModel->field(['user_id as id','user_name as name'])->where($where)->select();
        }
        return $ajaxlist;
    }
    //发放优惠券
    public function sendCoupons(Request $request,RankModel $rankModel,UserCouponsModel $userCouponsModel,UserModel $userModel){
        $userCouponsModel->data($request->param());
        if (empty($userCouponsModel->coupons_id)){
            $this->error('优惠券id有误');
        }
        $url='';
        if (!empty($userCouponsModel->page)){
            $url.='/page/'.$userCouponsModel->page;
        }
        if (!empty($userCouponsModel->issale)){
            $url.='/issale/'.$userCouponsModel->issale;
        }
        if (!empty($userCouponsModel->datemin)){
            $url.='/datemin/'.$userCouponsModel->datemin;
        }
        if (!empty($userCouponsModel->datemax)){
            $url.='/datemax/'.$userCouponsModel->datemax;
        }
        if (!empty($userCouponsModel->keywords)){
            $url.='/keywords/'.$userCouponsModel->keywords;
        }
        $coupons_id=$userCouponsModel->coupons_id;
        $job = 'app\job\CreateUserCoupons';
        //如果是提交
        if(!empty($userCouponsModel->is_ajax)){
            if (!empty($userCouponsModel->rank_id)){
                $rank_id=$userCouponsModel->rank_id;
                $users=$userModel->where("rank_id = $rank_id and disabled = 1")->select()->toArray();
                //消息队列
                foreach ($users as $value){
                    $couponsinfo=array(
                        'coupons_id'=>$coupons_id,
                        'user_id'=>$value['user_id']
                    );
                    $request = Queue::push($job, serialize($couponsinfo) , $queue = "CreateUserCoupons");
                    if (!$request){
                        $this->error('优惠券发放失败');
                    }
                }
            }
            if (!empty($userCouponsModel->stintid)){
                $user_id=$userCouponsModel->stintid;
                $user_arr=explode(',',$user_id);
                //消息队列
                foreach ($user_arr as $value){
                    $couponsinfo=array(
                        'coupons_id'=>$coupons_id,
                        'user_id'=>$value
                    );
                    $request = Queue::push($job, serialize($couponsinfo) , $queue = "CreateUserCoupons");
                    if (!$request){
                        $this->error('优惠券发放失败');
                    }
                }
            }
            $this->success('优惠券发放成功','/Admin/System/couponsList'.$url);
        }else{
            //获取等级列表
            $ranklist=$rankModel->select();
            $this->assign('ranklist',$ranklist);
            $this->assign('coupons_id',$coupons_id);
            $this->assign('url',$url);
            // 模板输出
            return view("System/send_coupons");
        }
    }
    //图片库管理
    public function imageList()
    {
        $dir    = './images';
        $filesNames = scandir($dir);
        $imageNames = array();
        if (!empty($filesNames)) {
            foreach ($filesNames as &$filesName) {
                if ($filesName == '.' || $filesName == '..') {
                    unset($filesName);
                }else {
                    $imageNames[] = $filesName;
                }
            }
        }
        $this->assign('imageNames',$imageNames);
        // 模板输出
        return view("System/image_list");
    }
    //图片库信息
    public function imageInfo(Request $request)
    {
        $Info = $request->param();
        $dir    = './small/images/' . $Info['file_image'];
        $filesNames = scandir($dir);
        $imageNames = array();
        $imageTmp = array();
        if (!empty($filesNames)) {
            foreach ($filesNames as &$filesName) {
                if ($filesName == '.' || $filesName == '..') {
                    unset($filesName);
                }else {
                    if (is_dir($dir . '/' . $filesName)){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                    $imageTmp['filesName'] = $filesName;
                    $imageTmp['status'] = $status;
                    $imageTmp['url'] = '/small/images/' . $Info['file_image'] . '/' . $filesName . "?mm=".time();
                    $imageTmp['base_url'] = $Info['file_image'] . '/' . $filesName;
                    $imageTmp['Info_url'] = $Info['file_image'];
                    $imageNames[] = $imageTmp;
                }
            }
        }
        $this->assign('imageNames',$imageNames);
        // 模板输出
        return view("System/image_info");
    }
    //编辑图片
    public function imageEdit(Request $request,UploadService $uploadService)
    {
        $Info = $request->param();
        $imgName    = '/images/' . $Info['file_image'];
        //如果是提交
        if(!empty($Info['is_ajax'])){
            $file = $request->file('image');
            if ($file) {
                unlink('.' . $imgName);
                unlink('./small' . $imgName);
                unlink('./thum' . $imgName);
                $result = $uploadService->uploadImage($file,$imgName);
                if($result){
                    $this->setAdminUserLog("编辑","编辑图片地址为" . $imgName ,'System');
                    $this->success("编辑成功","",$Info['file_image']);
                }else{
                    $this->error("编辑失败");
                }
            }
        }else{
            $imageName    = '/small/images/' . $Info['file_image'];
            $this->assign('imageName',$imageName);
            $this->assign('Info_url',$Info['Info_url']);
            $this->assign('file_image',$Info['file_image']);
            return view("System/image_edit");
        }
    }
    //数据备份列表
    public function backUpList(){
        $db = new BackUp();
        $dataList = $db->dataList();
        $this->assign('dataList',$dataList);
        $this->assign('sql_name',date('Ymd-His'));
        // 模板输出
        return view("System/back_up_list");
    }
    //数据备份
    public function dumpSql(Request $request)
    {
        $Info = $request->param();
        /* 初始化 */
        $dump = new BackUp();

        /* 获取要备份数据列表 */
        $type = empty($Info['type']) ? '' : trim($Info['type']);
        $file=['name'=>$Info['sql_file_name'],'part'=>1];
        switch ($type)
        {
            case 'full':
                $temp = $dump->dataList();
                foreach ($temp AS $table)
                {
                    $start = $dump->setFile($file)->backup($table['name'], 0);
                }
                break;
            case 'custom':
                foreach ($Info['customtables'] AS $table)
                {
                    $start = $dump->setFile($file)->backup($table, 0);
                }
                break;
        }
        if ($start == 0) {
            $this->setAdminUserLog("备份","备份sql文件".$Info['sql_file_name'] ,'System');
            $this->success("备份成功");
        }else{
            $this->error("备份失败");
        }
    }

    public function restore()
    {
        $db = new BackUp();
        $fileList = $db->fileList();
        if (!empty($fileList)) {
            foreach ($fileList as &$file){
                $file['size'] = $this->num_bitunit($file['size']);
                $file['time'] = date('Y-m-d H:i:s',$file['time']);
            }
        }

        $this->assign('fileList',$fileList);
        // 模板输出
        return view("System/restore_list");
    }
    //

    /**
     * 将字节转成可阅读格式
     *
     * @param $num
     * @return string
     * @Author: guanyl
     * @Date: 2018/6/1
     */
    function num_bitunit($num)
    {
        $bitunit = array(' B',' KB',' MB',' GB');
        $num_bitunit_str = '';
        for ($key = 0, $count = count($bitunit); $key < $count; $key++)
        {
            if ($num >= pow(2, 10 * $key) - 1) // 1024B 会显示为 1KB
            {
                $num_bitunit_str = (ceil($num / pow(2, 10 * $key) * 100) / 100) . " $bitunit[$key]";
            }
        }
        return $num_bitunit_str;
    }
    //规则协议
    public function agreementRule(Request $request,AgreementRuleModel $agreementRuleModel)
    {
        $agreementRuleModel->data($request->param());
        if (!empty($agreementRuleModel->show_count)){
            $show_count = $agreementRuleModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($agreementRuleModel->orderBy)){
            $orderBy = $agreementRuleModel->orderBy;
        }else{
            $orderBy = 'agreement_id';
        }
        if(!empty($agreementRuleModel->orderByUpOrDown)){
            $orderByUpOrDown = $agreementRuleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $agreementRule = $agreementRuleModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $agreementRule->render();
        // 模板变量赋值
        $this->assign('agreementRule', $agreementRule);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('agreementRule');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/agreement_rule");
    }
    //添加规则协议
    public function agreementRuleAdd(AgreementRuleModel $agreementRuleModel, Request $request, UploadService $uploadService)
    {
        $agreementRuleInfo= $request->param();
        //如果是提交
        if(!empty($agreementRuleInfo['is_ajax'])){
            $agreementRuleInfo['agreement_info'] = $agreementRuleInfo['editorValue'];
            $result = $agreementRuleModel->create($agreementRuleInfo);
            if($result){
                $agreement_id = $agreementRuleModel->getLastInsID();

                $imgData = Session::get("uploadimg");
                $baseUrl = $this->getConfig('base_url');
                foreach ($imgData as $item){
                    //移动原图片
                    $image  = '.'.$item;
                    $ImgName = rand(100,999).time();
                    $imgUrl = './images/agreementRule/'.$agreement_id.'/';
                    $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                    if($newImgName){
                        $newImgName = str_replace("./","$baseUrl/",$newImgName);
                        //替换content
                        $agreementRuleInfo['agreement_info'] = str_replace($item,$newImgName,$agreementRuleInfo['agreement_info']);
                    }
                }
                $upWhere['agreement_id'] = $agreement_id;
                $agreementRuleModel->update($agreementRuleInfo,$upWhere);

                $this->setAdminUserLog("新增","添加规则协议：id为" . $agreement_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            //清除上传图片session
            Session::delete('uploadimg');
            // 模板输出
            return view("System/agreement_rule_info");
        }
    }
    //编辑规则协议
    public function agreementRuleEdit(AgreementRuleModel $agreementRuleModel, Request $request, UploadService $uploadService){
        $agreementRuleInfo= $request->param();
        //如果是提交
        if(!empty($agreementRuleInfo['is_ajax'])){
            $agreementRule = $agreementRuleModel->where(["agreement_id"=>$agreementRuleInfo['agreement_id']])->find();
            if(!empty($agreementRule)){
                $agreementRuleInfo['agreement_info'] = $agreementRuleInfo['editorValue'];

                $upWhere['agreement_id'] = $agreementRuleInfo['agreement_id'];
                $result = $agreementRuleModel->update($agreementRuleInfo,$upWhere);
                if($result){
                    $imgData = Session::get("uploadimg");
                    $baseUrl = $this->getConfig('base_url');
                    foreach ($imgData as $item){
                        //移动原图片
                        $image  = '.'.$item;
                        $ImgName = rand(100,999).time();
                        $imgUrl = './images/agreementRule/'.$agreementRuleInfo['agreement_id'].'/';
                        $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                        if($newImgName){
                            $newImgName = str_replace("./","$baseUrl/",$newImgName);
                            //替换content
                            $agreementRuleInfo['agreement_info'] = str_replace($item,$newImgName,$agreementRuleInfo['agreement_info']);
                            //删除原图
                            unlink($image);
                        }
                    }
                    $upWhere['agreement_id'] = $agreementRuleInfo['agreement_id'];
                    $agreementRuleModel->update($agreementRuleInfo,$upWhere);

                    $this->setAdminUserLog("编辑","编辑规则协议：id为" . $agreementRuleInfo['agreement_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("规则协议不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $agreementRule = $agreementRuleModel->where(["agreement_id"=>$agreementRuleInfo['agreement_id']])->find();
            if(!empty($agreementRule)){
                $agreementRule = $agreementRule->toArray();
            }
            $agreementRule['agreement_info'] = htmlspecialchars_decode($agreementRule['agreement_info']);
            $this->assign('agreementRule', $agreementRule);
            //清除上传图片session
            Session::delete('uploadimg');
            // 模板输出
            return view("System/agreement_rule_info");
        }
    }
    //删除规则协议
    public function agreementRuleDel(AgreementRuleModel $agreementRuleModel, Request $request){
        $agreementRuleInfo= $request->param();
        $agreementRule = $agreementRuleModel->where(["agreement_id"=>$agreementRuleInfo['agreement_id']])->find();
        if(!empty($agreementRule)){
            $result = $agreementRuleModel->where(["agreement_id"=>$agreementRuleInfo['agreement_id']])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除规则协议：id为" . $agreementRuleInfo['agreement_id']);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("规则协议不存在，删除失败");
        }
    }

    /**
     * 退货原因列表
     *
     * @param RefundReasonModel $refundReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/7
     */
    public function refundReasonList(RefundReasonModel $refundReasonModel,Request $request)
    {
        $refundReasonModel->data($request->param());
        if (!empty($refundReasonModel->show_count)){
            $show_count = $refundReasonModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($refundReasonModel->orderBy)){
            $orderBy = $refundReasonModel->orderBy;
        }else{
            $orderBy = 'reason_id';
        }
        if(!empty($refundReasonModel->orderByUpOrDown)){
            $orderByUpOrDown = $refundReasonModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $refundReasonList = $refundReasonModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $refundReasonList->render();
        // 模板变量赋值
        $this->assign('refundReasonList', $refundReasonList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('refundReasonList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/refund_reason_list");
    }

    /**
     * 添加退货原因
     *
     * @param RefundReasonModel $refundReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/7
     */
    public function refundReasonAdd(RefundReasonModel $refundReasonModel, Request $request)
    {
        $refundReasonInfo= $request->param();
        //如果是提交
        if(!empty($refundReasonInfo['is_ajax'])){
            $result = $refundReasonModel->create($refundReasonInfo);
            if($result){
                $reason_id = $refundReasonModel->getLastInsID();
                $this->setAdminUserLog("新增","添加退货原因：id为" . $reason_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("System/refund_reason_info");
        }
    }

    /**
     * 编辑退货原因
     *
     * @param RefundReasonModel $refundReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/7
     */
    public function refundReasonEdit(RefundReasonModel $refundReasonModel, Request $request){
        $refundReasonInfo= $request->param();
        //如果是提交
        if(!empty($refundReasonInfo['is_ajax'])){
            $refundReason = $refundReasonModel->where(["reason_id"=>$refundReasonInfo['reason_id']])->find();
            if(!empty($refundReason)){
                $upWhere['reason_id'] = $refundReasonInfo['reason_id'];
                $result = $refundReasonModel->update($refundReasonInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑退款原因：id为" . $refundReasonInfo['reason_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("退款原因不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $refundReason = $refundReasonModel->where(["reason_id"=>$refundReasonInfo['reason_id']])->find();
            if(!empty($refundReason)){
                $refundReason = $refundReason->toArray();
            }
            $this->assign('refundReason', $refundReason);
            // 模板输出
            return view("System/refund_reason_info");
        }
    }

    /**
     * 删除退货原因
     *
     * @param RefundReasonModel $refundReasonModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/7
     */
    public function refundReasonDel(RefundReasonModel $refundReasonModel, Request $request){
        $refundReasonInfo= $request->param();
        $refundReason = $refundReasonModel->where(["reason_id"=>$refundReasonInfo['reason_id']])->find();
        if(!empty($refundReason)){
            $result = $refundReasonModel->where(["reason_id"=>$refundReasonInfo['reason_id']])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除退货原因：id为" . $refundReasonInfo['reason_id']);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("退货原因不存在，删除失败");
        }
    }

    public function systemPush(StorePushMessageModel $storePushMessageModel, Request $request){
        $storePushMessageModel->data($request->param());
        if (!empty($storePushMessageModel->show_count)){
            $show_count = $storePushMessageModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($storePushMessageModel->orderBy)){
            $orderBy = $storePushMessageModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storePushMessageModel->orderByUpOrDown)){
            $orderByUpOrDown = $storePushMessageModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $storePushMessageList = $storePushMessageModel
            ->field('b.*,s.store_name')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->where('b.message_type = 0')
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //unserialize  array_merge  //编码  json_encode  解码 json_decode
        foreach ($storePushMessageList as &$push) {
            $message_data = json_decode($push['message_data']);
            $push['title'] = $message_data->title;
            $push['data'] = $message_data->data;
        }

        // 获取分页显示
        $page = $storePushMessageList->render();
        // 模板变量赋值
        $this->assign('storePushMessageList', $storePushMessageList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('systemPush');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("System/message_push");
    }

    public function systemPushAdd(StorePushMessageModel $storePushMessageModel, QueueJobsFailModel $queueJobsFailModel, StorePushMessageLogModel $storePushMessageLogModel, NavModel $navModel, Request $request){
        $storePushMessageInfo = $request->param();
        //如果是提交
        if(!empty($storePushMessageInfo['is_ajax'])){
            if (empty($storePushMessageInfo['stintid'])) {
                $this->error("请选择店铺");
            }
            $storeIds = explode(',',$storePushMessageInfo['stintid']);
            $job = 'app\job\StorePushMessage';
            $a = 0;
            //编码  json_encode  解码 json_decode
            for ($i=0;$i<count($storeIds);$i++){
                $messageInfo = array(
                    'store_id'=>$storeIds[$i],
                    'message_type'=>0,
                    'message_cont'=>'系统推送',
                    'create_time'=>date("Y-m-d H:i:s",time()),
                    'message_data'=>array(
                        'title'=>$storePushMessageInfo['title'],
                        'data'=>$storePushMessageInfo['data'],
                        'create_time'=>date("Y-m-d H:i:s",time())
                    )
                );
                if(!Queue::push($job, serialize($messageInfo) , $queue = "StorePushMessage")){
                    if(!Queue::push($job, serialize($messageInfo) , $queue = "StorePushMessage")){
                        //-- 插入失败队列
                        $data = [
                            'queue'=>'StorePushMessage',
                            'job'=>$job,
                            'data'=>serialize($messageInfo)
                        ];
                        $queueJobsFailModel->create($data);
                        $a++;
                    }
                }
            }
            $number = count($storeIds)-$a;
            $messageLog = array(
                'success_num'=>$number,
                'fail_num'=>$a,
                'message_type'=>0,
                'message_cont'=>'系统推送',
                'admin_id'=>Session::get('admin_user_id'),
                'message_data'=>serialize(array(
                    'title'=>$storePushMessageInfo['title'],
                    'data'=>$storePushMessageInfo['data'],
                    'create_time'=>date("Y-m-d H:i:s",time())
                ))
            );
            $storePushMessageLogModel->create($messageLog);
            $this->setAdminUserLog("推送","推送活动：店铺id为" . $storePushMessageInfo['stintid'] );
            $this->success("推送成功");
        }else{
            //获取行业列表
            $navList = $navModel->where('disabled=1')->select();
            // 模板输出
            $this->assign('navList', $navList);
            return view("System/message_info");
        }
    }

}