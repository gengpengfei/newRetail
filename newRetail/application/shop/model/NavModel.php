<?php

namespace app\shop\model;

use app\shop\model\CommonModel;
class NavModel extends CommonModel
{
    protected $table = 'new_nav';
    //-- 设置主键
    protected $pk = 'nav_id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','sort_order','disabled'
    ];
    //-- 图片获取器
    public function getNavImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}