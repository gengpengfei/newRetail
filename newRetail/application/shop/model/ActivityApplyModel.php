<?php
/**
 * ActivityApplyModel
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\shop\model;
use app\shop\model\CommonModel;
class ActivityApplyModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_activity_apply';
    protected $pk= 'id';
    protected $hidden = ['create_time'];
}