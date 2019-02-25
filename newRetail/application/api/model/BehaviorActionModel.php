<?php
namespace app\api\model;
use app\api\model\CommonModel;
class BehaviorActionModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_behavior_action';
    protected $pk= 'behavior_action_id';
    protected $hidden = ['disabled','create_time','update_time'];

}