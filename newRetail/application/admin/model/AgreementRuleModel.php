<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class AgreementRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_agreement_rule';
    protected $hidden = ['create_time','update_time'];


}