<?php
namespace app\api\model;
use app\api\model\CommonModel;
use app\api\model\OrderRefundModel;
class OrderProModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_order_pro';
    protected $pk = 'order_pro_id';
    protected $hidden=[
        'create_time','update_time'
    ];
    protected $append = ['refund_state'];
    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    //-- 属性获取器
    public function getRefundStateAttr($value,$data)
    {
        $refundState = OrderRefundModel::where("order_pro_id=".$data['order_pro_id'])->find();
        return empty($refundState) ? '0': $refundState->refund_state;
    }
}