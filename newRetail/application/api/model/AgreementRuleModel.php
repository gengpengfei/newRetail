<?php
namespace app\api\model;
use app\api\model\CommonModel;
class AgreementRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_agreement_rule';
    protected $hidden = ['agreement_id','agreement_name','agreement_desc','disabled','create_time','update_time'];
}