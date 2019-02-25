<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class UserVoucherRefundModel extends CommonModel{
    protected $table = 'new_user_voucher_refund';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getRefundImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}