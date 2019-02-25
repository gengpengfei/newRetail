<?php

namespace app\admin\model;

use app\admin\model\CommonModel;
class StoreDiscountRuleModel extends CommonModel
{
    public $table = 'new_store_discount_rule';
    //-- 设置主键
    protected $pk = 'id';
}