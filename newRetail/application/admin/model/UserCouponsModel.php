<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class UserCouponsModel extends CommonModel{
    protected $table = 'new_user_coupons';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getCouponsImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}