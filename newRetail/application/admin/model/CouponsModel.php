<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class CouponsModel extends CommonModel{
    protected $table = 'new_coupons';

    //-- 图片获取器
    public function getCouponsImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}