<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class UserScoreLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_score_log';
    protected $hidden = ['id'];
}