<?php

namespace app\api\model;
use app\api\model\CommonModel;

class ProNavModel extends CommonModel
{
    protected $table = 'new_pro_nav';
    //-- 设置主键
    protected $pk = 'id';
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