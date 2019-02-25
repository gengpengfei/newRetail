<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class StoreCloseLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'store_close_log';
    //-- 图片获取器
    public function getCloseImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}