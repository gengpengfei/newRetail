<?php

namespace app\shopapi\model;

use app\shopapi\model\CommonModel;
class StoreReserveConfigModel extends CommonModel
{
    protected $table = 'new_store_reserve_config';
    //-- 设置主键
    protected $pk = 'id';
}