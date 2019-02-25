<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
use app\shopapi\model\UserVoucherRefundModel;
class UserVoucherModel extends CommonModel{
    protected $table = 'new_user_voucher';
    protected $hidden = ['create_time','update_time'];
    //-- 追加属性
    protected $append = ['refund_state','refund_time'];
    //-- 图片获取器
    public function getCouponsImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 属性获取器
    public function getRefundStateAttr($value,$data)
    {
        $refundState = UserVoucherRefundModel::where(['user_voucher_id'=>$data['user_voucher_id']])->find();
        return empty($refundState) ? '0':$refundState['refund_state'];
    }
    //-- 属性获取器
    public function getRefundTimeAttr($value,$data)
    {
        $refundState = UserVoucherRefundModel::where(['user_voucher_id'=>$data['user_voucher_id']])->find();
        return empty($refundState) ? null:$refundState['refund_time'];
    }
}