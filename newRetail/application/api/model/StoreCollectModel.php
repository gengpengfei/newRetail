<?php

namespace app\api\model;
use app\api\model\CommonModel;

class StoreCollectModel extends CommonModel
{
    protected $table = 'new_store_collect';
    //-- 设置主键
    protected $pk = 'id';
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}