<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserVoucherRefundModel extends CommonModel{
    protected $table = 'new_user_voucher_refund';
    protected $hidden = ['create_time','update_time'];
}