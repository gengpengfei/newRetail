<?php

namespace app\api\model;
use app\api\model\CommonModel;
class ProAttrRurlModel extends CommonModel
{
    protected $table = 'new_pro_attr_rule';
    //-- 设置主键
    protected $pk = 'attr_rule_id';
    //--隐藏属性
    protected $hidden = [
            'create_time','update_time','disabled'
    ];
}