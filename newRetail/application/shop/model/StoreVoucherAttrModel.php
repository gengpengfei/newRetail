<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreVoucherAttrModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_voucher_attr';
    protected $hidden = ['attr_rule_id','create_time','update_time'];

}