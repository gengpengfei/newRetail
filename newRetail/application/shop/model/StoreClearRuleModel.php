<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreClearRuleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_clear_rule';
    protected $hidden = ['create_time','update_time'];

}