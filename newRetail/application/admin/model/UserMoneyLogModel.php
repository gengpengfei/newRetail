<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class UserMoneyLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_money_log';
    protected $hidden = ['id'];

}