<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class StoreOrderPayModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_order_pay';
    protected $hidden = [
        'update_time','create_time'
    ];

}