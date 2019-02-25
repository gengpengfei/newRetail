<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreOrderModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_order';
    protected $hidden = [
        'update_time'
    ];
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}