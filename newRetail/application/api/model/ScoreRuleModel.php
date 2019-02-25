<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ScoreRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_score_rule';
    protected $hidden = ['score_rule_id','rule_name','rule_desc','disabled','create_time','update_time'];
}