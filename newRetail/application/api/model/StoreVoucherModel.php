<?php
namespace app\api\model;
use app\api\model\CommonModel;
use app\api\model\StoreVoucherCollectModel;
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
class StoreVoucherModel extends CommonModel{
    protected $table = 'new_store_voucher';
    protected $pk= 'voucher_id';
    protected $hidden = ['create_time','update_time','disabled'];
    protected $append = ['is_collect'];
    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getVoucherBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    //-- 属性获取器
    public function getIsCollectAttr($value,$data)
    {
        $is_collect = StoreVoucherCollectModel::where(['voucher_id'=>$data['voucher_id'],'user_id'=>request()->user->user_id??0])->find();
        return empty($is_collect) ? 0:1;
    }
}