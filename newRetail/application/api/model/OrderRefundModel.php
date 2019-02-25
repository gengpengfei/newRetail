<?php
namespace app\api\model;
use app\api\model\CommonModel;
class OrderRefundModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_order_refund';
    protected $pk = 'id';
    protected $hidden=[
        'create_time','update_time'
    ];
    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}