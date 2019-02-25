<?php

namespace app\api\model;

/**
    +----------------------------------------------------------
     * @explain 店铺积分返利规则
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return 店铺积分返利规则
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\CommonModel;
class StoreRebateRuleModel extends CommonModel
{
    protected $table = 'new_store_rebate_rule';
    //-- 设置主键
    protected $pk = 'id';
}