<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreVoucherAttrModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_voucher_attr';
    protected $hidden = ['attr_rule_id','create_time','update_time'];

}