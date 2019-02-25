<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class UserVoucherModel extends CommonModel{
    protected $table = 'new_user_voucher';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getCouponsImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    //通过券状态获取数量
    public function getStateNum($state){
        $count=$this->where("used_state='$state'")->count();
        return $count;
    }
}