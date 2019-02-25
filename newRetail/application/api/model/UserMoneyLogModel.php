<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserMoneyLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_money_log';
    protected $hidden = ['id'];

}