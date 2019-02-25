<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class StoreCategoryModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_category';

    //-- 图片获取器
    public function getCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getchildCategory($obj){
        $res = $this->field(['store_category_id','category_name','grade'])->where("parent_id={$obj['store_category_id']} and disabled=1")->order("sort_order","desc")->select()->toArray();
        return $res;
    }
}