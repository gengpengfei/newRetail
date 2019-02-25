<?php

namespace app\shopapi\model;
use app\shopapi\model\CommonModel;

class StoreVoucherCollectModel extends CommonModel
{
    protected $table = 'new_store_voucher_collect';
    //-- 设置主键
    protected $pk = 'id';

    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}