<?php

namespace app\shopapi\model;
use app\shopapi\model\CommonModel;

class StoreProLikeModel extends CommonModel
{
    protected $table = 'new_store_pro_like';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
        'create_time'
    ];
}