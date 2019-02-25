<?php

namespace app\api\model;
use app\api\model\CommonModel;

class StoreReserveConfigModel extends CommonModel
{
    protected $table = 'new_store_reserve_config';
    //-- 设置主键
    protected $pk = 'id';
}