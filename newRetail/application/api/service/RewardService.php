<?php
namespace app\api\service;

use app\api\model\RewardLimitModel;
use app\api\model\RewardRuleModel;
use app\api\model\UserMoneyLogModel;
use app\api\model\UserRewardModel;
use app\api\model\UserScoreLogModel;
use app\api\model\UsersModel;
use think\Db;

/**
    +-------------------------------------------------------------
     * @explain 用户奖励金判断
    +-------------------------------------------------------------
     * @access 
    +-------------------------------------------------------------
     * @return class
    +-------------------------------------------------------------
     * @acter Mr.Geng
    +-------------------------------------------------------------
**/
class RewardService extends CommonService{
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;

    protected $orderTotal = 0;
    protected $userInfo;
    protected $rewardUserInfo;
    protected $rewardLimitModel;
    protected $rewardRuleModel;
    protected $userRewardModel;

    public function __construct()
    {
        $this->rewardLimitModel = new RewardLimitModel();
        $this->rewardRuleModel = new RewardRuleModel();
        $this->userRewardModel = new UserRewardModel();
    }

    /*
     * explain:获取奖励用户信息
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function getRewardUserInfo(){
        $invitation_code = $this->userInfo->invitation_code;
        if(!empty($invitation_code)){
            $this->rewardUserInfo = UsersModel::where(['registration_code'=>$invitation_code])->find();
        }
    }
        
    /*
     * explain:判断奖励金发放条件
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function RewardCondition($limitId){
        $limit = $this->rewardLimitModel->where('id='.$limitId)->find();
        if($this->orderTotal>=$limit->min_amount){
            //-- 判断今日奖励数量
            $time = $this->getTimeToday();
            $orderCount = $this->userRewardModel->where('user_id='.$this->rewardUserInfo->user_id.' and create_time>="'.$time.'"')->count();
            if($orderCount < $limit->limit_order){
                return true;
            }
        }
        return false;
    }

     /*
     * explain:执行用户奖励金赠送
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function giveUserReward($userId,$orderTotal){
        $this->userInfo = UsersModel::where(['user_id'=>$userId])->find();
        //-- 设置奖励用户信息
        $this->getRewardUserInfo();
        $this->orderTotal = $orderTotal;
        $time = $this->getTime();
        $list = $this->rewardRuleModel->where("disabled = 1 and start_time<'".$time."' and '".$time."'<end_time")->select();
        foreach($list as $v){
            //-- 判断限制条件
            if($this->RewardCondition($v->limit_id)){
                //-- 计算奖励金额并发放奖励
                switch($v->reward_range){
                     case 0:
                        //-- 积分奖励
                        $this->score($v);
                        break;
                     case 1:
                        //-- 优惠券奖励
                        $this->voucher($v);
                        break;
                     case 2:
                        //-- 余额奖励
                        $this->money($v);
                        break;
                     default:
                        return;
                }
            }
        }
    }

    /*
     * explain:积分奖励
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function score($info){
        switch($info->reward_type){
            case 0:
                break;
            case 1:
                $score = $info->reward_info;
                break;
            case 2:
                $score = $this->orderTotal*$info->reward_info/100;
                break;
            default:
                break;
        }
        //-- 赠送积分
        $res = Db::table('new_users')->where('user_id',$this->rewardUserInfo->user_id)->setInc('user_score', $score);
        if(!$res) return;
        $res = UserScoreLogModel::create(['user_id'=>$this->rewardUserInfo->user_id,'desc'=>'邀请会员得奖励','score'=>$score]);
        if(!$res) return;
        //-- 记录奖励金赠送日志
        $data = array(
            'user_id'=>$this->rewardUserInfo->user_id,
            'user_reward_type'=>$info->reward_type,
            'user_reward_info'=>$score
        );
        $this->userRewardModel->save($data);
    }

    /*
     * explain:优惠券奖励
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function voucher($info){
        //-- 赠送优惠券
        $couponsData = Db::table('new_coupons')
            ->field(['coupons_id','coupons_name','coupons_desc','coupons_img','coupons_type','coupons_price','use_rank','min_amount','use_scope','use_scope_info','use_method','use_method_info','use_start_date','use_end_date'])
            ->where('coupons_id',$info->reward_info)->find();
        $usercouponsData = $couponsData;
        $usercouponsData['user_id']=$this->rewardUserInfo->user_id;
        $usercouponsData['user_state']='C01';
        $usercouponsData['coupons_sn'] = $this->getOrderSn();
        if (!Db::table('new_user_coupons')->insert($usercouponsData)) return;
        //-- 记录优惠券赠送日志
        $data = array(
            'user_id'=>$this->rewardUserInfo->user_id,
            'user_reward_type'=>$info->reward_type,
            'user_reward_info'=>$info->reward_info
        );
        $this->userRewardModel->save($data);
    }

    /*
     * explain:金额奖励
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/16 16:52
     */
    public function money($info){
        switch($info->reward_type){
            case 0:
                break;
            case 1:
                $money = $info->reward_info;
                break;
            case 2:
                $money = $this->orderTotal*$info->reward_info/100;
                break;
            default:
                break;
        }
        //-- 赠送余额
        //-- 改变用户余额
        $usersModel = new UsersModel();
        if(!$usersModel->where('user_id',$this->userInfo->user_id)->setInc('user_money',$money)) return ;
        //-- 记录用户账户变动日志
        $userMoneyLogModel = new UserMoneyLogModel();
        if (!$userMoneyLogModel->create(['money'=>$money,'type'=>3,'desc'=>'邀请会员得奖励','user_id'=>$this->rewardUserInfo->user_id])) return;
        //-- 记录奖励金赠送日志
        $data = array(
            'user_id'=>$this->rewardUserInfo->user_id,
            'user_reward_type'=>$info->reward_type,
            'user_reward_info'=>$money
        );
        $this->userRewardModel->save($data);
    }
}