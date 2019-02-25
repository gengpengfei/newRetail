<?php

namespace app\api\model;

/**
    +----------------------------------------------------------
     * @explain 新零售补贴规则
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return 新零售补贴规则
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\CommonModel;
class StoreDiscountRuleModel extends CommonModel
{
    protected $table = 'new_store_discount_rule';
    //-- 设置主键
    protected $pk = 'id';
}