<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class StoreOpinionsModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'store_opinions';
    //-- 图片获取器
    public function getOpinionImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}