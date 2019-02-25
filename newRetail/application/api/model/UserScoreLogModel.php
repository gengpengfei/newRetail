<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserScoreLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_score_log';
    protected $hidden = ['id'];
}