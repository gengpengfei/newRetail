<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class UserVoucherRefundModel extends CommonModel{
    protected $table = 'new_user_voucher_refund';
    protected $hidden = ['create_time','update_time'];
}