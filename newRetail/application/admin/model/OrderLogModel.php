<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class OrderLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_order_log';
    protected $pk= 'order_log_id';


}