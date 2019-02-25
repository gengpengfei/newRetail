<?php

namespace app\api\service;

use app\api\model\RankModel;
use app\api\model\RankRuleModel;
use app\api\model\StoreOrderModel;
use app\api\model\UsersModel;

class UserRankService extends CommonService
{
    use \app\api\traits\BuildParam;
    protected $user_id;
    protected $rank_id;
    protected $target_rank;
    protected $user_info;
    public function __construct($userId=0)
    {
        $this->user_id = $userId;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }
    /*
     * explain:确认用户等级
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 15:42
     */
    public function checkUserRank()
    {
        $time = $this->getTime();
        //-- 获取等级规则
        $rankRule = RankRuleModel::where("start_time<'".$time."' and end_time>'".$time."'")->select();
        foreach ($rankRule as $rule){
            switch ($rule->rule_code){
                case 'total_order_times':
                    //-- 判定消费次数
                    $this->checkOrderTimes($rule);
                    break;
                case 'store_order_times':
                    //-- 判定到店次数
                    $this->checkStoreTimes($rule);
                    break;
                case 'total_order_price':
                    //-- 判定总订单金额
                    $this->checkTotalOrder($rule);
                    break;

            }
        }
        $rankNum = 0;
        foreach ( $this->rank_id as $v){
            $rankInfo = RankModel::where("rank_id=$v")->find();
            if($rankNum === 0){
                $rankNum = $rankInfo->rank_num;
                $rankId = $v;
            }else{
                if($rankInfo->rank_num<$rankNum){
                    $rankNum = $rankInfo->rank_num;
                    $rankId = $v;
                }
            }
        }
        if(empty($rankId)){
            //-- 获取最低等级
            $rank = RankModel::order('rank_num','asc')->find();
            $rankId = $rank->rank_id;
        }
        return $rankId;
    }

    /*
     * explain:更新用户等级
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/7 11:40
     */
    public function updateUserRank($rankId=0)
    {
        $newRank = RankModel::where(['rank_id'=>$rankId])->find();
        $userInfo = UsersModel::where(['user_id'=>$this->user_id])->find();
        $oldRank = RankModel::where(['rank_id'=>$userInfo->rank_id])->find();
        if($newRank->rank_num!=$oldRank->rank_num){
            //-- 更新用户等级
            $res = UsersModel::update(['rank_id'=>$rankId],['user_id'=>$this->user_id]);
            if(!$res){
                return false;
            }
            return true;
        }
        return true;
    }
    
    /*
     * explain:获取目标等级详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/7 15:28
     */
    public function getTargetRank()
    {
        return $this->target_rank;
    }
    
    /*
     * explain:判定用户消费次数
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 17:00
     */
    protected function checkOrderTimes($rule)
    {
        if(!empty($rule->rule_info)){
            $orderNum = StoreOrderModel::where("user_id=".$this->user_id." and order_state='T03' and create_time>'".$rule->start_time."' and create_time<'".$rule->end_time."'")->count();
            //-- 格式化等级条件
            $ruleInfo = $this->formatRankInfo($rule->rule_info);
            foreach ($ruleInfo as $key=>$v){
                if($orderNum>=$v['info']){
                    $rankId = $v['rank_id'];
                    $this->rank_id[] = $rankId;
                    $rule->new_value = $orderNum;
                    $rule->target_rank_id = $ruleInfo[$key-1]['rank_id'];
                    $rule->target_value = $ruleInfo[$key-1]['info'];
                    $this->target_rank[] = $rule;
                    break;
                }
            }
        }
    }

    /*
     * explain:判定用户到指定店消费次数
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 17:00
     */
    protected function checkStoreTimes($rule)
    {
        if(!empty($rule->rule_info)) {
            //-- 获取店铺id
            $orderNum = StoreOrderModel::where("user_id=" . $this->user_id . " and LOCATE(store_id,'" . $rule->store_id . "')>0  and order_state='T03' and create_time>'" . $rule->start_time . "' and create_time<'".$rule->end_time."'")->count();
            //-- 格式化等级条件
            $ruleInfo = $this->formatRankInfo($rule->rule_info);
            foreach ($ruleInfo as $key=>$v){
                if($orderNum>=$v['info']){
                    $rankId = $v['rank_id'];
                    $this->rank_id[] = $rankId;
                    $rule->new_value = $orderNum;
                    $rule->target_rank_id = $ruleInfo[$key-1]['rank_id'];
                    $rule->target_value = $ruleInfo[$key-1]['info'];
                    $this->target_rank[] = $rule;
                    break;
                }
            }
        }
    }

    /*
     * explain:判定总订单金额
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:45
     */
    public function checkTotalOrder($rule)
    {
        if(!empty($rule->rule_info)) {
            $orderNum = StoreOrderModel::where('user_id=' .$this->user_id . ' and order_state="T03"')->sum('buy_price');
            //-- 格式化等级条件
            $ruleInfo = $this->formatRankInfo($rule->rule_info);
            foreach ($ruleInfo as $key=>$v){
                if($orderNum>=$v['info']){
                    $rankId = $v['rank_id'];
                    $this->rank_id[] = $rankId;
                    $rule->new_value = $orderNum;
                    $rule->target_rank_id = $ruleInfo[$key-1]['rank_id'];
                    $rule->target_value = $ruleInfo[$key-1]['info'];
                    $this->target_rank[] = $rule;
                    break;
                }
            }
        }
    }

    /*
     * explain:格式化等级判定条件
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:42
     */
    public function formatRankInfo($ruleInfo)
    {
        $ruleInfo = unserialize($ruleInfo);
        //-- 根据info倒序排序
        array_multisort(array_column($ruleInfo,'info'),SORT_DESC,$ruleInfo);
        return $ruleInfo;
    }
}