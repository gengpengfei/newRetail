<?php

namespace app\api\model;
use app\api\model\CommonModel;

class StoreReserveReasonModel extends CommonModel
{
    protected $table = 'new_store_reserve_reason';
    //-- 设置主键
    protected $pk = 'id';
}