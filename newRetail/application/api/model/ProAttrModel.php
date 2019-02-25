<?php

namespace app\api\model;
use app\api\model\CommonModel;
class ProAttrModel extends CommonModel
{
    protected $table = 'new_pro_attr';
    //-- 设置主键
    protected $pk = 'attr_id';
    //--隐藏属性
    protected $hidden = [
            'create_time','update_time','disabled'
    ];
}