<?php
namespace app\api\model;
use app\api\model\CommonModel;

class RewardLimitModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_reward_limit';
    protected $hidden = ['create_time','update_time'];
}