<?php

namespace app\admin\model;
use app\admin\model\CommonModel;
class StoreReserveReasonModel extends CommonModel
{
    protected $table = 'new_store_reserve_reason';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
    ];
}