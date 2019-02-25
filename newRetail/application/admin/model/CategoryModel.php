<?php

namespace app\admin\model;

use app\admin\model\CommonModel;
class CategoryModel extends CommonModel
{
    public $table = 'new_category';
    //-- 设置主键
    protected $pk = 'category_id';
    //-- 只读字段
    protected $readonly = [];
    //-- 图片获取器
    public function getCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    /**
     * 查询该类别以及该类别下所有的子级类别（包括下级、下下级...）
     * 查询出来的类别id存放到一个数组里
     * */
    public function getAllCategory($obj,$navId,&$arr=array()){
        $res = $this->where("parent_id={$obj['category_id']} and disabled=1 and nav_id=$navId")->order("sort_order","desc")->select();
        if(count($res)<1&&$obj['category_id']>0)
        {
            $arr[]=$obj;
        }
        for($i=0;$i<count($res);$i++)
        {
            $this->getAllCategory($res[$i],$arr);
        }
        return $arr;
    }

    public function getchildCategory($obj){
        $res = $this->field(['category_id','category_name','grade'])->where("parent_id={$obj['category_id']} and disabled=1")->order("sort_order","desc")->select()->toArray();
        return $res;
    }

}