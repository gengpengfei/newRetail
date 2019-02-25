<?php

namespace app\shopapi\service;

use app\shopapi\model\BehaviorActionModel;
use app\shopapi\model\BehaviorLogModel;
use app\shopapi\model\BehaviorModel;
use app\shopapi\model\StoreOrderModel;
use app\shopapi\model\UserScoreLogModel;
use app\shopapi\model\UsersModel;
use think\Db;

/**
    +-------------------------------------------------------------
     * @explain 用户行为赠送积分和活跃判定
    +-------------------------------------------------------------
     * @access 只有下单的时候,才需要参数store_id 和 order_score
    +-------------------------------------------------------------
     * @return class
    +-------------------------------------------------------------
     * @acter Mr.Geng
    +-------------------------------------------------------------
**/
class BehaviorService extends CommonService
{
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\GetConfig;
    //-- 行为编码
    protected $code;
    //-- 用户详情
    protected $userInfo;
    //-- 店铺id
    protected $store_id;
    //-- 订单详情
    protected $orderInfo;
    //-- 行为类
    protected $behavior;
    //-- 行为类模型
    protected $behaviorModel;
    //-- 是否触发用户行为
    protected $isBehavior = false;

    public function __construct($data)
    {
//        $data = unserialize($data);
        if(empty($data['user_id'])){
            return true;
        }
        $this->userInfo = UsersModel::where(['user_id'=>$data['user_id']])->find();
        $this->code = $data['code'];
        $this->store_id = $data['store_id']??0;
        if($data['order_id']??0){
            $this->orderInfo = StoreOrderModel::where('order_id',$data['order_id'])->find();
        }
    }

    /*
     * explain:执行用户行为
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function execBehavior()
    {
        $this->behaviorModel = new BehaviorModel();
        $this->judgeBehavior();
        if($this->isBehavior){
            //-- 获取行为动作
            $actionList = BehaviorActionModel::where(['behavior_id'=>$this->behavior->behavior_id,'disabled'=>1])->select();
            if(!$actionList->isEmpty()){
                $score = 0;
                $active = 0;
                foreach ($actionList as $action){
                    //-- 指定店铺
                    if($action->is_store){
                        if(!in_array($this->store_id,explode(',',$action->store_id))){
                            continue;
                        }
                    }
                    //-- 积分规则
                    $score += $this->scoreRule($action);
                    //-- 活跃度规则
                    $active += $this->activeRule($action);
                }
                $this->behaviorModel->startTrans();
                //-- 赠送积分(判断是否是下单赠送)
                if(!empty($this->orderInfo)){
                    //-- 订单记录积分
                    if(!StoreOrderModel::update(['user_give_score'=>$score],['order_id'=>$this->orderInfo->order_id])){
                        $this->behaviorModel->rollback();
                        return false;
                    }
                    //-- 线下支付的直接赠送积分
                    if($this->orderInfo->order_type===1){
                        if($score>0){
                            if(!$this->setUserScore($score)){
                                $this->behaviorModel->rollback();
                                return false;
                            }
                        }
                    }
                }else{
                    if($score>0){
                        if(!$this->setUserScore($score)){
                            $this->behaviorModel->rollback();
                            return false;
                        }
                    }
                }

                //-- 增加活跃度
                if($active>0){
                    if(!$this->setUserActive($active)){
                        $this->behaviorModel->rollback();
                        return false;
                    }
                }
                //-- 记录行为日志
                 $result = BehaviorLogModel::create(['user_id'=>$this->userInfo->user_id,'behavior_desc'=>$this->behavior->behavior_desc,'behavior_code'=>$this->code]);
                if(!$result){
                    $this->behaviorModel->rollback();
                    return false;
                }
                //-- 计算用户等级
                $userRankServer = new UserRankService($this->userInfo->user_id);
                $userRank = $userRankServer->checkUserRank();
                if($userRank){
                    //-- 更新用户等级
                    if(!$userRankServer->updateUserRank($userRank)){
                        $this->behaviorModel->rollback();
                        return false;
                    }
                }
                $this->behaviorModel->commit();
                return true;
            }
            return true;
        }
        return true;
    }

    /*
     * explain:用户行为判定
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 11:06
     */
    protected function judgeBehavior()
    {
        $this->behavior = BehaviorModel::get(['behavior_code'=>$this->code]);
        //-- 判定行为
        switch ($this->behavior->behavior_type){
            case 0:
                $this->isBehavior = true;
                break;
            case 1:
                $time = $this->getTimeToday();
                $this->check($time);
                break;
            case 2:
                $time = $this->getTimeWeek();
                $this->check($time);
                break;
            default:
                return false;
        }
    }
    
    /*
     * explain:用户积分规则判断
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 11:07
     */
    protected function scoreRule($action)
    {
        if($action->is_score){
            switch ($action->score_rule_type){
                case 0:
                    return 0;
                    break;
                case 1:
                    return $action->score_rule_info;
                    break;
                case 2:
                    //--区间
                    $rule = unserialize($action->score_rule_info);
                    $score = 0;
                    foreach ($rule as $v){
                        if($this->orderInfo->buy_price>=$v['min'] && $this->orderInfo->buy_price<=$v['max']){
                            $score = $v['score'];
                        }
                    }
                    return $score;
                    break;
                case 3:
                    return (int)$action->score_rule_info*(int)$this->orderInfo->buy_price/100;
                    break;
                default:
                    return 0;
            }
        }else{
            return 0;
        }
    }
    
    /*
     * explain:用户活跃规则判断
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 11:08
     */
    protected function activeRule($action)
    {
        if($action->is_active){
            switch ($action->active_rule_type){
                case 0:
                    return 0;
                    break;
                case 1:
                    return $action->active_rule_info;
                    break;
                case 2:
                    //--区间
                    $rule = unserialize($action->active_rule_info);
                    $active = 0;
                    foreach ($rule as $v){
                        if($this->orderInfo->buy_price>=$v['min'] && $this->orderInfo->buy_price<=$v['max']){
                            $active = $v['active'];
                        }
                    }
                    return $active;
                    break;
                case 3:
                    return (int)$action->active_rule_info*(int)$this->orderInfo->buy_price/100;
                    break;
                default:
                    return 0;
            }
        }else{
            return 0;
        }
    }

    /*
     * explain:确认行为发生次数
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 17:46
     */
    protected function check($time){
        $behaviorNum = BehaviorLogModel::where('user_id='.$this->userInfo->user_id.' and behavior_code="'.$this->code.'" and create_time>"'.$time.'"')->count();
        if($behaviorNum<$this->behavior->behavior_times){
            //-- 判断同一店铺订单数
            if($this->behavior->order_times>0){
                if(empty($this->store_id)){
                    return false;
                }
                $orderNum = StoreOrderModel::where('store_id='.$this->store_id.' and order_state="T03" and create_time>"'.$time.'"')->count();
                if($orderNum<$this->behavior->order_times){
                    //-- 判断当前时间段店铺数
                    if($this->behavior->store_times>0){
                        $storeNum = StoreOrderModel::where('user_id='.$this->userInfo->user_id.' and create_time>"'.$time.'"')->group('store_id')->count();
                        if($storeNum<$this->behavior->store_times){
                            $this->isBehavior = true;
                        }
                    }else{
                        $this->isBehavior = true;
                    }
                }
            }else{
                //-- 判断当前时间段店铺数
                if($this->behavior->store_times>0){
                    $storeNum = StoreOrderModel::where('user_id='.$this->userInfo->user_id.' and create_time>"'.$time.'"')->group('store_id')->count();
                    if($storeNum<$this->behavior->store_times){
                        $this->isBehavior = true;
                    }
                }else{
                    $this->isBehavior = true;
                }
            }
        }
    }

    /*
     * explain:赠送用户积分并记录日志
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 9:51
     */
    protected function setUserScore($score)
    {
        $res = Db::table('new_users')->where('user_id',$this->userInfo->user_id)->setInc('user_score', $score);
        if(!$res) return false;
        $res = UserScoreLogModel::create(['user_id'=>$this->userInfo->user_id,'desc'=>$this->behavior->behavior_desc,'score'=>$score]);
        if(!$res) return false;
        return true;
    }

    /*
     * explain:赠送用户活跃度并记录日志
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 9:51
     */
    protected function setUserActive($active)
    {
        $res = UserActiveLogModel::create(['user_id'=>$this->userInfo->user_id,'desc'=>$this->behavior->behavior_desc,'active'=>$active]);
        if(!$res) return false;
        return true;
    }


}