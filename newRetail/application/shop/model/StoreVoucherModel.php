<?php
namespace app\shop\model;

/**
    +----------------------------------------------------------
     * @explain 店铺优惠券类
    +----------------------------------------------------------
     * @access 发放给用户的优惠券列表
    +----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\shop\model\CommonModel;
class StoreVoucherModel extends CommonModel{
    public $table = 'new_store_voucher';
    protected $pk= 'voucher_id';
    protected $hidden = ['create_time','update_time','disabled'];

    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    public function getVoucherBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}