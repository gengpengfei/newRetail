<?php

namespace app\admin\model;

use app\admin\model\CommonModel;
class StoreCreditLogModel extends CommonModel
{
    protected $table = 'new_store_credit_log';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time'
    ];
}