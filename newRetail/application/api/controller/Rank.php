<?php
namespace app\api\controller;

use app\api\model\ProductModel;
use app\api\model\RankModel;
use app\api\model\UsersModel;
use app\api\service\UserRankService;
use think\Request;

class Rank extends Common {
    /*
     * explain:用户等级列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 17:46
     */
    public function userRankList(RankModel $rankModel)
    {
        $rankInfo = $rankModel->order('rank_num','asc')->select();
        $this->jkReturn(1,'等级详情',$rankInfo);
    }

    /*
     * explain:获取用户等级详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/7 11:25
     */
    public function userRankInfo(Request $request,UserRankService $userRankService,RankModel $rankModel,ProductModel $productModel)
    {
        $userId = $request->param('user_id');
        $userRankService->setUserId($userId);
        //-- 计算用户当前等级
        $rankId = $userRankService->checkUserRank();
        //-- 更新用户等级
        $userRankService->updateUserRank($rankId);
        //-- 等级详情
        $rankInfo = $rankModel->where(['rank_id'=>$rankId])->find();
        //-- 当前等级进度
        $targetRank = $userRankService->getTargetRank();
        //-- 下一等级
        $downRank = $rankModel
            ->where("rank_num > (select rank_num from new_rank where rank_id=$rankId)")
            ->order('rank_num','ASC')
            ->limit(1)
            ->find();
        $proList = $productModel
            ->where("disabled=1 and rank_id=".$downRank->rank_id)
            ->select();
        //-- 上一等级
        $upRank = $rankModel
            ->where("rank_num < (select rank_num from new_rank where rank_id=$rankId)")
            ->order('rank_num','DESC')
            ->limit(1)
            ->find();

        $this->jkReturn('1','等级详情',['rank_info'=>$rankInfo,'up_rank'=>$upRank,'down_rank'=>$downRank,'target_rank'=>$targetRank,'pro_list'=>$proList]);
    }

}