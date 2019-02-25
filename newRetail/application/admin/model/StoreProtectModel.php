<?php

namespace app\admin\model;
use app\admin\model\CommonModel;
class StoreProtectModel extends CommonModel
{
    protected $table = 'new_store_protect';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
    ];
}