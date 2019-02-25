<?php
namespace app\api\model;
use app\api\model\CommonModel;
class OrderRefundLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_order_refund_log';
    protected $pk = 'id';
    protected $hidden=[
        'id'
    ];
}