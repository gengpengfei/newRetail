<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class StoreClearModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_clear';
    protected $hidden = ['update_time'];
    //-- 图片获取器
    public function getHeadImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}