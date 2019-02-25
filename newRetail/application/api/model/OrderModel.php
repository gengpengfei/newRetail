<?php
namespace app\api\model;
use app\api\model\CommonModel;
class OrderModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_order';
    protected $pk = 'order_id';
    protected $hidden = [
        'update_time'
    ];
    
    //-- 关联订单商品表
    public function orderPro()
    {
        return $this->hasMany('order_pro_model','order_id');
    }
}