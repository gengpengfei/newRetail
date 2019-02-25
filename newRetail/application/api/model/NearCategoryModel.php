<?php

namespace app\api\model;
use app\api\model\CommonModel;
class NearCategoryModel extends CommonModel
{
    protected $table = 'new_near_category';
    //-- 设置主键
    protected $pk = 'category_id';
    //--隐藏属性
    protected $hidden = [
        'create_time', 'sort_order',
    ];

    /**
     * 获取附近优惠商品标签.
     *
     * @return arr
     */
    public function getNearCategory()
    {
        $list = $this->all();
        return $list;
    }
}
