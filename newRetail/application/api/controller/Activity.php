<?php
namespace app\api\controller;

use app\api\model\ActivityInfoModel;
use app\api\model\ActivityListModel;
use app\api\model\ActivityModel;
use app\api\model\ProActivityInfoModel;
use app\api\model\ProActivityListModel;
use app\api\model\ProActivityModel;
use app\api\service\StoreService;
use think\Request;
use app\api\controller\Common;

class Activity extends Common {
    use \app\api\traits\BuildParam;
    
    /*
     * explain:首页活动列表
     * params :null
     * authors:Mr.Geng
     * addTime:2018/4/3 13:40
     */
    public function indexActivity(ActivityModel $activityModel)
    {
        $activityList = $activityModel->where(['disabled'=>1])->order('sort_order','asc')->select();
        $this->jkReturn(1,'活动列表',$activityList);
    }

    /*
     * params :首页活动详情
     * explain:@activity_id
     * authors:Mr.Geng
     * addTime:2018/3/19 14:34
     */
    public function activityInfo(Request $request,ActivityListModel $activityListModel,ActivityInfoModel $activityInfoModel,StoreService $storeService)
    {
        $param = $request->param();
        $time = $this->getTime();
        $is_new = 1;
        //-- 当前进行中的活动
        $activityList = $activityListModel->where("start_time<'$time' and '$time'<end_time and disabled=1 and activity_id={$param['activity_id']}")->find();
        if(empty($activityList)){
            $is_new = 2;
            //-- 获取下一场活动列表
            $activityList = $activityListModel->where("start_time>'$time'")->order('start_time','asc')->limit(1)->find();
            if(empty($activityList)){
                $is_new = 0;
                //-- 获取上一场活动列表
                $activityList = $activityListModel->where("end_time<'$time'")->order('end_time','desc')->limit(1)->find();
            }
        }
        $activityList->is_new = $is_new;
        $where = "a.activity_list_id = $activityList->activity_list_id and v.disabled = 1 and v.sell_start_date < '$time' and '$time' < v.sell_end_date";
        $activityInfo = $activityInfoModel
            ->alias('a')
            ->field('a.*,s.store_name,s.store_address,s.lat,s.lng ,s.store_hot,v.*')
            ->where($where)
            ->join('new_store s','s.store_id=a.store_id','left')
            ->join('new_store_voucher v','v.voucher_id=a.voucher_id','left')
            ->order('a.sort_order','asc')
            ->select();
        foreach ($activityInfo as $v){
            $storeService->getStoreDistance($v);
            if($v['is_main']==1){
                $activityInfoMain[] = $v;
            }else{
                $activityInfoList[] = $v;
            }
        }
        $data = [
            'activity'=>$activityList,
            'activity_main'=>$activityInfoMain??[],
            'activity_list'=>$activityInfoList??[]
        ];
        $this->jkReturn(1,'活动列表',$data);
    }

    /*
     * explain:获取积分商城活动列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 13:40
     */
    public function shopActivity(Request $request,ProActivityModel $proActivityModel)
    {
        $position = $request->param('activity_position');
        $activityList = $proActivityModel->where(['disabled'=>1,'activity_position'=>$position])->order('sort_order','asc')->select();
        $this->jkReturn(1,'活动列表',$activityList);
    }

    /*
     * params :积分商城活动详情
     * explain:@activity_id
     * authors:Mr.Geng
     * addTime:2018/3/19 14:34
     */
    public function shopActivityInfo(Request $request,ProActivityListModel $proActivityListModel,ProActivityInfoModel $proActivityInfoModel)
    {
        $param = $request->param();
        $time = $this->getTime();
        //-- 当前进行中的活动
        $activityList = $proActivityListModel->where("start_time<'$time' and '$time'<end_time and disabled=1 and activity_id={$param['activity_id']}")->find();
        if(empty($activityList)){
            //-- 获取下一场活动列表
            $activityList = $proActivityListModel->where("start_time>'$time'")->order('start_time','asc')->limit(1)->find();
            if(empty($activityList)){
                //-- 获取上一场活动列表
                $activityList = $proActivityListModel->where("end_time<'$time'")->order('end_time','desc')->limit(1)->find();
            }
        }
        $activityInfo = $proActivityInfoModel
            ->alias('a')
            ->field('a.*,p.*')
            ->where(['a.activity_list_id'=>$activityList->activity_list_id,'p.disabled'=>1])
            ->join('new_product p','p.product_id=a.product_id','left')
            ->order('sort_order','asc')
            ->select();
        $data = [
            'activity'=>$activityList,
            'activity_list'=>$activityInfo
        ];
        $this->jkReturn(1,'活动列表',$data);
    }
}