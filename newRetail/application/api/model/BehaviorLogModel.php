<?php
namespace app\api\model;
use app\api\model\CommonModel;
class BehaviorLogModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_behavior_log';
    protected $pk= 'id';
    protected $hidden = ['create_time'];

}