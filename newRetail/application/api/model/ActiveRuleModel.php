<?php
namespace app\api\model;
use app\api\model\CommonModel;

class ActiveRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_active_rule';
    protected $hidden = ['active_rule_id','rule_name','rule_desc','disabled','create_time','update_time'];
}