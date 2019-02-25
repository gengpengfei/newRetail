<?php
/**
 * Promotion
 * User: guanyl
 * Date: 2018/4/11
 * Time: 15:32
 */

namespace app\admin\controller;


use app\admin\model\ActivityApplyModel;
use app\admin\model\ActivityInfoModel;
use app\admin\model\ActivityListModel;
use app\admin\model\ActivityModel;
use app\admin\model\NavModel;
use app\admin\model\NearCategoryModel;
use app\admin\model\QueueJobsFailModel;
use app\admin\model\StoreActivityModel;
use app\admin\model\StoreModel;
use app\admin\model\StorePushMessageLogModel;
use app\admin\model\StorePushMessageModel;
use app\admin\model\StoreVoucherModel;
use app\admin\service\UploadService;
use think\Queue;
use think\Request;
use think\Session;

class Promotion extends Common
{
    use \app\api\traits\BuildParam;
    //活动类别
    public function promotionList(ActivityModel $activityModel,Request $request)
    {
        $activityModel->data($request->param());
        if (!empty($activityModel->show_count)){
            $show_count = $activityModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($activityModel->keywords)){
            $keywords = $activityModel->keywords;
            $where .= " and (activity_name like '%" . $keywords . "%' or activity_desc like '%". $keywords . "%')";
        }
        $activity = $activityModel
            ->where($where)
            ->order('activity_id DESC')
            ->paginate($show_count);
        // 获取分页显示
        $page = $activity->render();
        // 模板变量赋值
        $this->assign('activity', $activity);
        $this->assign('where', $activityModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('promotionList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/promotion_list");
    }
    //添加活动类别
    public function promotionAdd(ActivityModel $activityModel,UploadService $uploadService,Request $request){
        $activityModel->data($request->param());
        //如果是提交
        if(!empty($activityModel->is_ajax)){
            $file = $request->file('activity_img');
            if ($file) {
                $imgUrl  = '/images/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityModel->activity_img = $result;
            }
            $activity = $activityModel->where(["activity_name"=>$activityModel->activity_name])->find();
            if(!empty($activity)){
                $this->error("活动分类名称已存在");
            }
            $result = $activityModel->allowField(true)->save($activityModel);
            if($result){
                $activity_id = $activityModel->getLastInsID();
                $this->setAdminUserLog("新增","添加活动分类：id为" . $activity_id . "-" . $activityModel->activity_name);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Promotion/promotion_info");
        }
    }
    //编辑活动类别
    public function promotionEdit(ActivityModel $activityModel, UploadService $uploadService, Request $request){
        $activityModel->data($request->param());
        //如果是提交
        if(!empty($activityModel->is_ajax)){
            $file = $request->file('activity_img');
            if ($file) {
                $imgUrl  = '/images/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityModel->activity_img = $result;
            }else{
                unset($activityModel->activity_img);
            }
            if(!empty($activityModel->position_name)){
                $activity = $activityModel->where(["activity_name"=>$activityModel->activity_name])->find();
                if(!empty($activity)){
                    $this->error("活动分类名称已存在");
                }
            }
            $activity = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
            if(!empty($activity)){
                $upWhere['activity_id'] = $activityModel->activity_id;
                $result = $activityModel->allowField(true)->save($activityModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑活动分类：id为" . $activityModel->activity_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("活动分类不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $activity = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
            if(!empty($activity)){
                $activity = $activity->toArray();
            }
            $this->assign('activity', $activity);
            // 模板输出
            return view("Promotion/promotion_info");
        }
    }
    //删除活动类别
    public function promotionDel(ActivityModel $activityModel, Request $request){
        $activityModel->data($request->param());
        $promotion = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
        if(!empty($promotion)){
            unlink('.' . $promotion->activity_img);
            $result = $activityModel->where(["activity_id"=>$activityModel->activity_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除活动类别：id为" . $activityModel->activity_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("活动类别不存在，删除失败");
        }
    }
    //活动列表
    public function activityList(ActivityListModel $activityListModel,Request $request)
    {
        $activityListModel->data($request->param());
        if (!empty($activityListModel->show_count)){
            $show_count = $activityListModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($activityListModel->activity_id)) {
            $activityListModel->activity_id = 1;
        }
        $where = " 1=1 ";
        if(!empty($activityListModel->keywords)){
            $keywords = $activityListModel->keywords;
            $where .= " and (b.activity_list_name like '%" . $keywords . "%' or b.activity_list_desc like '%". $keywords . "%')";
        }
        if (!empty($activityListModel->activity_id)) {
            $activity_id = $activityListModel->activity_id;
            $where .= " and b.activity_id = " . $activity_id;
        }

        //排序条件
        if(!empty($activityListModel->orderBy)){
            $orderBy = $activityListModel->orderBy;
        }else{
            $orderBy = 'activity_list_id';
        }
        if(!empty($activityListModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityListModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $activity_list = $activityListModel
            ->field('b.*,p.activity_name')
            ->alias('b')
            ->join('new_activity p','p.activity_id=b.activity_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        // 获取分页显示
        $page = $activity_list->render();
        // 模板变量赋值
        $this->assign('activity_list', $activity_list);
        $this->assign('activity_id', $activityListModel->activity_id);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $activityListModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('activityList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/activity_list");
    }
    //参加活动店铺列表
    public function activityStore(ActivityInfoModel $activityInfoModel,Request $request){
        $activityInfoModel->data($request->param());
        if (!empty($activityInfoModel->show_count)){
            $show_count = $activityInfoModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($activityInfoModel->activity_list_id) || empty($activityInfoModel->activity_id)) {
            $this->error("活动参数错误");
        }
        $where = " 1=1 ";
        if(!empty($activityInfoModel->keywords)){
            $keywords = $activityInfoModel->keywords;
            $where .= " and (s.store_name like '%" . $keywords . "%' or v.voucher_name like '%". $keywords . "%')";
        }
        if (!empty($activityInfoModel->activity_list_id)) {
            $activity_list_id = $activityInfoModel->activity_list_id;
            $where .= " and b.activity_list_id = " . $activity_list_id;
        }
        //排序条件
        if(!empty($activityInfoModel->orderBy)){
            $orderBy = $activityInfoModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($activityInfoModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityInfoModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $activityInfo = $activityInfoModel
            ->field('b.*,s.store_name,v.voucher_name')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->join('new_store_voucher v','v.voucher_id = b.voucher_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        // 获取分页显示
        $page = $activityInfo->render();
        // 模板变量赋值
        $this->assign('activity_list', $activityInfo);
        $this->assign('activity_list_id', $activityInfoModel->activity_list_id);
        $this->assign('activity_id', $activityInfoModel->activity_id);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $activityInfoModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('activityStore');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/activity_join");
    }
    //推送活动通知店铺
    public function pushActivityStore(StoreActivityModel $storeActivityModel,Request $request){
        $storeActivityModel->data($request->param());
        if (!empty($storeActivityModel->show_count)){
            $show_count = $storeActivityModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($storeActivityModel->activity_list_id) || empty($storeActivityModel->activity_id)) {
            $this->error("活动参数错误");
        }
        $where = " 1=1 ";
        if(!empty($storeActivityModel->keywords)){
            $keywords = $storeActivityModel->keywords;
            $where .= " and (s.store_name like '%" . $keywords . "%')";
        }
        if (!empty($storeActivityModel->activity_list_id)) {
            $activity_list_id = $storeActivityModel->activity_list_id;
            $where .= " and b.activity_list_id = " . $activity_list_id;
        }
        //排序条件
        if(!empty($storeActivityModel->orderBy)){
            $orderBy = $storeActivityModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeActivityModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeActivityModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $activityInfo = $storeActivityModel
            ->field('b.*,s.store_name')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        // 获取分页显示
        $page = $activityInfo->render();
        // 模板变量赋值
        $this->assign('activity_list', $activityInfo);
        $this->assign('activity_list_id', $storeActivityModel->activity_list_id);
        $this->assign('activity_id', $storeActivityModel->activity_id);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $storeActivityModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('pushActivityStore');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/activity_push_store");
    }
    //添加活动
    public function activityAdd(ActivityListModel $activityListModel,UploadService $uploadService,Request $request){
        $activityListModel->data($request->param());
        if (empty($activityListModel->activity_id)) {
            $activityListModel->activity_id = 1;
        }
        $this->assign('activity_id', $activityListModel->activity_id);
        //如果是提交
        if(!empty($activityListModel->is_ajax)){
            $activityList = $activityListModel->where(["activity_list_name"=>$activityListModel->activity_list_name])->find();
            if(!empty($activityList)){
                $this->error("活动名称已存在");
            }
            $file = $request->file('activity_list_img');
            if ($file) {
                $imgUrl  = '/images/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityListModel->activity_list_img = $result;
            }

            $file1 = $request->file('activity_list_bgimg');
            if ($file1) {
                $imgUrl1  = '/images/activity/';
                $imgName1 = $this->imgName() . '12';
                $result1 = $uploadService->upload($file1,$imgUrl1,$imgName1);
                $activityListModel->activity_list_bgimg = $result1;
            }

            $result = $activityListModel->allowField(true)->save($activityListModel);
            if($result){
                $activity_list_id = $activityListModel->getLastInsID();
                $this->setAdminUserLog("新增","添加活动：id为" . $activity_list_id . "-" . $activityListModel->activity_list_name);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Promotion/activity_info");
        }
    }
    //编辑活动
    public function activityEdit(ActivityListModel $activityListModel,UploadService $uploadService, Request $request){
        $activityListModel->data($request->param());
        //如果是提交
        if(!empty($activityListModel->is_ajax)){
            $file = $request->file('activity_list_img');
            if ($file) {
                $imgUrl  = '/images/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityListModel->activity_list_img = $result;
            }else{
                unset($activityListModel->activity_list_img);
            }
            $file1 = $request->file('activity_list_bgimg');
            if ($file1) {
                $imgUrl1  = '/images/activity/';
                $imgName1 = $this->imgName() . '12';
                $result1 = $uploadService->upload($file1,$imgUrl1,$imgName1);
                $activityListModel->activity_list_bgimg = $result1;
            }else{
                unset($activityListModel->activity_list_bgimg);
            }

            $activityList = $activityListModel->where(["activity_list_id"=>$activityListModel->activity_list_id])->find();
            if(!empty($activityList)){
                $upWhere['activity_list_id'] = $activityListModel->activity_list_id;
                $result = $activityListModel->allowField(true)->save($activityListModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑活动：id为" . $activityListModel->activity_list_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("活动不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $activityList = $activityListModel->where(["activity_list_id"=>$activityListModel->activity_list_id])->find();
            if(!empty($activityList)){
                $activityList = $activityList->toArray();
            }
            $this->assign('activity_id', $activityList['activity_id']);
            $this->assign('activityList', $activityList);
            // 模板输出
            return view("Promotion/activity_info");
        }
    }
    //删除活动
    public function activityDel(ActivityListModel $activityListModel, Request $request){
        $activityListModel->data($request->param());

        if(empty($activityListModel->activity_list_id)){
            $this->error("活动id不能为空");
        }
        $activity_list_id = $activityListModel->activity_list_id;
        if(is_array($activity_list_id)){
            //多个删除
            foreach ($activity_list_id as $v){
                $activityList = $activityListModel->where(["activity_list_id"=>$v])->find();
                if(!empty($activityList)){
                    unlink('.' . $activityList->activity_list_img);
                    unlink('.' . $activityList->activity_list_bgimg);
                    $result = $activityListModel->where(["activity_list_id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除活动：id为" . $v );
                }else{
                    $this->error("活动不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }
        //单个删除
        $activityList = $activityListModel->where(["activity_list_id"=>$activity_list_id])->find();
        if(!empty($activityList)){
            unlink('.' . $activityList->activity_list_img);
            unlink('.' . $activityList->activity_list_bgimg);
            $result = $activityListModel->where(["activity_list_id"=>$activity_list_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除活动：id为" . $activity_list_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("活动不存在，删除失败");
        }

    }
    //抵用券活动列表
    public function goodsList(ActivityInfoModel $activityInfoModel,ActivityListModel $activityListModel,Request $request)
    {
        $activityInfoModel->data($request->param());
        if (!empty($activityInfoModel->show_count)){
            $show_count = $activityInfoModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($activityInfoModel->activity_list_id)) {
            $this->error("活动参数错误");
        }
        $where = " 1=1 ";
        if(!empty($activityInfoModel->keywords)){
            $keywords = $activityInfoModel->keywords;
            $where .= " and (c.store_name like '%" . $keywords . "%' or d.voucher_name like '%". $keywords . "%')";
        }
        if (!empty($activityInfoModel->activity_list_id)) {
            $where .= " and a.activity_list_id = " . $activityInfoModel->activity_list_id;
        }
        //排序条件
        if(!empty($activityInfoModel->orderBy)){
            $orderBy = $activityInfoModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($activityInfoModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityInfoModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $activityInfo = $activityInfoModel
            ->alias('a')
            ->join('new_activity_list b','a.activity_list_id = b.activity_list_id','left')
            ->join('new_store c','a.store_id = c.store_id','left')
            ->join('new_store_voucher d','a.voucher_id = d.voucher_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        $activity = $activityListModel->where(['activity_list_id'=>$activityInfoModel->activity_list_id])->find();
        // 获取分页显示
        $page = $activityInfo->render();
        // 模板变量赋值
        $this->assign('activityInfo', $activityInfo);
        $this->assign('activity_id', $activity->activity_id);
        $this->assign('activity_list_id', $activityInfoModel->activity_list_id);
        $this->assign('where', $activityInfoModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('goodsList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/goods_list");
    }
    //添加抵用券活动
    public function goodsAdd(ActivityInfoModel $activityInfoModel,StoreModel $storeModel,Request $request){
        $activityInfoModel->data($request->param());
        if (empty($activityInfoModel->activity_list_id)) {
            $this->error("活动参数错误");
        }
        $this->assign('activity_list_id', $activityInfoModel->activity_list_id);

        //如果是提交
        if(!empty($activityInfoModel->is_ajax)){
            $result = $activityInfoModel->allowField(true)->save($activityInfoModel);
            if($result){
                $id = $activityInfoModel->getLastInsID();
                $this->setAdminUserLog("新增","添加抵用券活动：id为" . $id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            /**
             * 店铺列表
             */
            $store_list = array();
            $where = " disabled = 1 ";
            if(!empty($activityInfoModel->keywords)){
                $keywords = $activityInfoModel->keywords;
                $where .= " and store_name like '%" . $keywords . "%' ";
                $store_list = $storeModel->where($where)->select();
            }
            $this->assign('store_list', $store_list);
            // 模板输出
            return view("Promotion/goods_info");
        }
    }

    // 编辑抵用券活动
    public function goodsEdit(ActivityInfoModel $activityInfoModel,StoreModel $storeModel,StoreVoucherModel $storeVoucherModel, Request $request){
        $activityInfoModel->data($request->param());

        //如果是提交
        if(!empty($activityInfoModel->is_ajax)){
            $activityInfo = $activityInfoModel->where(["id"=>$activityInfoModel->id])->find();
            if(!empty($activityInfo)){
                $upWhere['id'] = $activityInfoModel->id;
                $result = $activityInfoModel->allowField(true)->save($activityInfoModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑抵用券活动：id为" . $activityInfoModel->id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("抵用券不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $activityInfo = $activityInfoModel->where(["id"=>$activityInfoModel->id])->find();
            /**
             * 店铺列表
             */
            $where = " disabled = 1 ";
            $storeVoucher = array();
            if(!empty($activityInfoModel->keywords)){
                $keywords = $activityInfoModel->keywords;
                $where .= " and store_name like '%" . $keywords . "%' ";
                $store_list = $storeModel->where($where)->select();
            }else {
                $store_list = $storeModel->where(["store_id"=>$activityInfo['store_id']])->select();
                $storeVoucher = $storeVoucherModel->where(["voucher_id"=>$activityInfo['voucher_id']])->select();

            }
            $this->assign('store_list', $store_list);
            $this->assign('voucher_id', $activityInfo['id']);
            $this->assign('storeVoucher', $storeVoucher);
            $this->assign('activity_list_id', $activityInfo['activity_list_id']);
            $this->assign('activityInfo', $activityInfo);
            // 模板输出
            return view("Promotion/goods_info");
        }
    }

    //删除抵用券活动
    public function goodsDel(ActivityInfoModel $activityInfoModel, Request $request){
        $activityInfoModel->data($request->param());
        if(empty($activityInfoModel->id)){
            $this->error("活动id不能为空");
        }
        $activity_Info_id = $activityInfoModel->id;
        if(is_array($activity_Info_id)){
            //多个删除
            foreach ($activity_Info_id as $v){
                $activity_InfoList = $activityInfoModel->where(["id"=>$v])->find();
                if(!empty($activity_InfoList)){
                    $result = $activityInfoModel->where(["id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除抵用券活动：id为" . $v );
                }else{
                    $this->error("抵用券活动不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }
        //单个删除
        $activityInfo = $activityInfoModel->where(["id"=>$activity_Info_id])->find();
        if(!empty($activityInfo)){
            $result = $activityInfoModel->where(["id"=>$activity_Info_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除抵用券活动：id为" . $activity_Info_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("抵用券活动不存在，删除失败");
        }
    }

    //根据店铺ID获取抵用券列表
    public function getVoucher(StoreVoucherModel $storeVoucherModel,Request $request)
    {
        $storeInfo = $request->param();
        //抵用券列表
        $storeVoucher = $storeVoucherModel->where(["disabled"=>1,"store_id"=>$storeInfo['store_id']])->select();
        $this->jkReturn(1,'抵用券列表',$storeVoucher);
    }
    //活动申请
    public function activityApply(Request $request,ActivityApplyModel $activityApplyModel,ActivityInfoModel $activityInfoModel)
    {
        $activityApplyModel->data($request->param());
        if (!empty($activityApplyModel->show_count)){
            $show_count = $activityApplyModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($activityApplyModel->orderBy)){
            $orderBy = $activityApplyModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($activityApplyModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityApplyModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $activityApply = $activityApplyModel
            ->field('a.*,b.activity_list_name,c.store_name,d.voucher_name')
            ->alias('a')
            ->join('new_activity_list b','a.activity_list_id = b.activity_list_id','left')
            ->join('new_store c','a.store_id = c.store_id','left')
            ->join('new_store_voucher d','a.voucher_id = d.voucher_id','left')
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        if (!empty($activityApply)) {
            foreach ($activityApply as &$apply) {
                $apply['state'] = 0;
                $activityInfo = $activityInfoModel
                    ->where(["activity_list_id"=>$apply['activity_list_id'],"store_id"=>$apply['store_id'],"voucher_id"=>$apply['voucher_id']])
                    ->select();
                if (!empty($activityInfo)) {
                    $apply['state'] = 1;
                }
            }
        }
        // 获取分页显示
        $page = $activityApply->render();
        // 模板变量赋值
        $this->assign('activityApply', $activityApply);
        //权限按钮
        $action_code_list = $this->getChileAction('activityApply');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/activity_apply");
    }
    //券详情
    public function voucherInfo(StoreVoucherModel $storeVoucherModel,Request $request){
        $storeVoucher = $request->param();
        $voucher_id = $storeVoucher['voucher_id'];
        if(empty($voucher_id)){
            $this->error("优惠券id无效",'/admin/Promotion/activityApply');
        }
        $voucher_info = $storeVoucherModel
            ->alias('v')
            ->field('v.*,s.store_name')
            ->join('new_store s','s.store_id = v.store_id','left')
            ->where(["voucher_id"=>$voucher_id])
            ->find();
        if(empty($voucher_info)){
            $this->error("该优惠券不存在",'/admin/Promotion/activityApply');
        }
        $voucher_info['voucher_info'] = htmlspecialchars_decode(htmlspecialchars_decode($voucher_info['voucher_info']));

        $this->assign('bannerImage',$voucher_info['voucher_banner_img']);
        $this->assign('voucher_info',$voucher_info);
        return view("Promotion/voucher_show");
    }

    //活动审核
    public function activityApplyEdit(Request $request,StorePushMessageModel $storePushMessageModel,ActivityInfoModel $activityInfoModel,ActivityApplyModel $activityApplyModel)
    {
        $activityApplyInfo = $request->param();
        //如果是提交
        $activityApply = $activityApplyModel
            ->field('a.*,b.activity_list_name,b.activity_list_desc,b.start_time,b.end_time,c.store_name,d.voucher_name')
            ->alias('a')
            ->join('new_activity_list b','a.activity_list_id = b.activity_list_id','left')
            ->join('new_store c','a.store_id = c.store_id','left')
            ->join('new_store_voucher d','a.voucher_id = d.voucher_id','left')
            ->where(["id"=>$activityApplyInfo['id']])
            ->find();

        if(!empty($activityApply)){
            $where = [
                'activity_list_id'=>$activityApply->activity_list_id,
                'store_id'=>$activityApply->store_id
            ];
            $activityInformation = $activityInfoModel->where($where)->find();
            $applyInformation = $activityApplyModel->where($where)->find();
            if (!empty($activityInformation) || !empty($applyInformation)) {
                $this->error("该活动已被申请过,请重新确认");
            }
            $storePushMessage = [
                'store_name'=>$activityApply->store_name ,
                'voucher_name'=>$activityApply->voucher_name ,
                'state'=>$activityApplyInfo['state'],
                'activity_list_name'=>$activityApply->activity_list_name ,
                'activity_list_desc'=>$activityApply->activity_list_desc ,
                'start_time'=>$activityApply->start_time ,
                'end_time'=>$activityApply->end_time
            ];
            if ($activityApplyInfo['state'] == 2) {
                $storePushMessage['reason'] = "您的店铺或优惠券不符合活动规则";
            }else{
                $activityInfo = [
                    'activity_list_id'=>$activityApply->activity_list_id,
                    'store_id'=>$activityApply->store_id,
                    'voucher_id'=>$activityApply->voucher_id
                ];
                $resultAdd = $activityInfoModel->create($activityInfo);
                if($resultAdd){
                    $id = $activityInfoModel->getLastInsID();
                    $this->setAdminUserLog("新增","添加抵用券活动：id为" . $id);
                }else{
                    $this->error("添加抵用券活动失败");
                }
            }
            $storePushInfo = [
                'store_id'=>$activityApply->store_id,
                'message_type'=>4,
                'message_cont'=>'活动审核已受理',
                'message_data'=>json_encode($storePushMessage,JSON_UNESCAPED_UNICODE)
            ];
            $resultMessage = $storePushMessageModel->create($storePushInfo);
            if($resultMessage){
                $message_id = $activityInfoModel->getLastInsID();
                $this->setAdminUserLog("新增","添加推送消息：id为" . $message_id);
            }else{
                $this->error("添加推送消息失败");
            }
            //编码  json_encode  解码 json_decode    'reason'=>'您的店铺或优惠券不符合活动规则',
            $result = $activityApplyModel->where(["id"=>$activityApplyInfo['id']])->delete();
            if(!$result){
                $this->error("删除失败");
            }
            $this->setAdminUserLog("删除","删除活动申请：id为" . $activityApplyInfo['id'] );
            $this->success("添加成功");
        }else{
            $this->error("活动不存在");
        }
    }
    //推送消息
    public function message(ActivityListModel $activityListModel,QueueJobsFailModel $queueJobsFailModel, StorePushMessageLogModel $storePushMessageLogModel, NavModel $navModel, Request $request){
        $pushMessage = $request->param();
        $activityList = $activityListModel->where(["activity_list_id"=>$pushMessage['activity_list_id']])->find();
        //如果是提交
        if(!empty($pushMessage['is_ajax'])){
            if (empty($pushMessage['stintid'])) {
                $this->error("请选择店铺");
            }
            $storeIds = explode(',',$pushMessage['stintid']);
            $job = 'app\job\StorePushMessage';
            $a = 0;
            for ($i=0;$i<count($storeIds);$i++){
                $messageInfo = array(
                    'store_id'=>$storeIds[$i],
                    'message_type'=>3,
                    'message_cont'=>$activityList->activity_list_name . '活动报名通知',
                    'message_data'=>array(
                        'activity_list_id'=>$pushMessage['activity_list_id'],
                        'activity_list_name'=>$activityList->activity_list_name,
                        'activity_list_desc'=>$activityList->activity_list_desc,
                        'start_time'=>$activityList->start_time,
                        'end_time'=>$activityList->end_time,
                    ),
                    'message_state'=>0,
                    'create_time'=>$this->getTime()
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
                'message_type'=>3,
                'message_cont'=>'活动报名通知',
                'admin_id'=>Session::get('admin_user_id'),
                'message_data'=>serialize(array(
                    'activity_list_id'=>$pushMessage['activity_list_id'],
                    'activity_list_name'=>$activityList->activity_list_name,
                    'activity_list_desc'=>$activityList->activity_list_desc,
                    'start_time'=>$activityList->start_time,
                    'end_time'=>$activityList->end_time,
                ))
            );
            $storePushMessageLogModel->create($messageLog);
            $this->setAdminUserLog("推送","推送活动：店铺id为" . $pushMessage['stintid'] );
            $this->success("推送成功",'',['activity_list_id'=>$pushMessage['activity_list_id']]);
        }else{
            //获取行业列表
            $navList = $navModel->where('disabled=1')->select();
            // 模板输出
            $this->assign('navList', $navList);
            $this->assign('activityList', $activityList);
            return view("Promotion/message_info");
        }
    }
    //附近优惠分类
    public function nearCategoryList(NearCategoryModel $nearCategoryModel, Request $request){
        $nearCategoryModel->data($request->param());
        if (!empty($nearCategoryModel->show_count)){
            $show_count = $nearCategoryModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($nearCategoryModel->keywords)){
            $keywords = $nearCategoryModel->keywords;
            $where .= " and c.category_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($nearCategoryModel->orderBy)){
            $orderBy = $nearCategoryModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($nearCategoryModel->orderByUpOrDown)){
            $orderByUpOrDown = $nearCategoryModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $nearCategory = $nearCategoryModel
            ->field('b.*,c.category_name')
            ->alias('b')
            ->join('new_category c','c.category_id = b.category_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $nearCategory->render();
        // 模板变量赋值
        $this->assign('nearCategory', $nearCategory);
        $this->assign('where', $nearCategoryModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('nearCategoryList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Promotion/near_category_list");
    }
    //附近优惠分类添加
    public function nearCategoryAdd(NearCategoryModel $nearCategoryModel, NavModel $navModel, Request $request){
        $nearCategory = $request->param();
        if(!empty($nearCategory['is_ajax'])){
            if (!empty($nearCategory['category_id'])) {
                $nearCategoryInfo = $nearCategoryModel->where('category_id = ' . $nearCategory['category_id'])->find();
                if (!empty($nearCategoryInfo) && !empty($nearCategoryInfo->category_id)) {
                    $this->error("此分类已存在");
                }
            }
            $resultAdd = $nearCategoryModel->create($nearCategory);
            if($resultAdd){
                $id = $nearCategoryModel->getLastInsID();
                $this->setAdminUserLog("新增","添加附近优惠分类：id为" . $id);
                $this->success("添加成功");
            }else{
                $this->error("添加附近优惠分类失败");
            }
        }else{
            //获取行业列表
            $navList = $navModel->field('nav_id,nav_name')->where('disabled=1')->select();
            $this->assign('navList', $navList);
            // 模板输出
            return view("Promotion/near_category_info");
        }
    }
    //附近优惠分类删除
    public function nearCategoryDel(NearCategoryModel $nearCategoryModel, Request $request){
        $nearCategoryModel->data($request->param());
        if(empty($nearCategoryModel->id)){
            $this->error("分类id不能为空");
        }
        $category_id = $nearCategoryModel->id;
        //单个删除
        $nearCategory = $nearCategoryModel->where(["id"=>$category_id])->find();
        if(!empty($nearCategory)){
            $result = $nearCategoryModel->where(["id"=>$category_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除附近优惠分类：id为" . $category_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("附近优惠分类不存在，删除失败");
        }
    }
}