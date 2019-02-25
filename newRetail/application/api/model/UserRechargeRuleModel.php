<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserRechargeRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_recharge_rule';
    protected $pk = "id";
    protected $hidden = ['create_time'];
}