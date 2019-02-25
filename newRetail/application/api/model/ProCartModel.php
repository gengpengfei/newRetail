<?php

namespace app\api\model;
use app\api\model\CommonModel;
class ProCartModel extends CommonModel
{
    protected $table = 'new_pro_cart';
    //-- 设置主键
    protected $pk = 'cart_id';
    //--隐藏属性
    protected $hidden = [
        'create_time'
    ];
    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}