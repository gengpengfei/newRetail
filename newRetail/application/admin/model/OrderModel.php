<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class OrderModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_order';
    protected $pk= 'order_id';
    protected $hidden = ['update_time'];


}