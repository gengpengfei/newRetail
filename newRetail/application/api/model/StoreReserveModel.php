<?php

namespace app\api\model;

use app\api\model\CommonModel;
class StoreReserveModel extends CommonModel
{
    protected $table = 'new_store_reserve';
    //-- 设置主键
    protected $pk = 'id';
}