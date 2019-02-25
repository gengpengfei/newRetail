<?php

namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreReserveModel extends CommonModel
{
    protected $table = 'new_store_reserve';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
    ];
}