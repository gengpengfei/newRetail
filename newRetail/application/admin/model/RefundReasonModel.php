<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class RefundReasonModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_refund_reason';
    protected $pk = 'id';
    protected $hidden = ['update_time'];

}