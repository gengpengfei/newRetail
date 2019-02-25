<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class BehaviorActionModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    public $table = 'new_behavior_action';
    protected $pk= 'behavior_action_id';
    protected $hidden = ['create_time','update_time'];
}