<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserRechargeModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_recharge';
    protected $pk = "recharge_id";
    protected $hidden = ['create_time'];
}