<?php

namespace app\shopapi\model;

use app\shopapi\model\CategoryModel;
use app\shopapi\model\CommonModel;
class NavModel extends CommonModel
{
    protected $table = 'new_nav';
    //-- 设置主键
    protected $pk = 'nav_id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','sort_order','disabled'
    ];
    //-- 追加字段category_list
    protected $append = ['category_list'];
    //-- 属性获取
    public function getCategoryListAttr($value,$data)
    {
        $categoryList = CategoryModel::where(['nav_id'=>$data['nav_id']])->select();
        return empty($categoryList) ? []:$categoryList;
    }
    //-- 图片获取器
    public function getNavImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}