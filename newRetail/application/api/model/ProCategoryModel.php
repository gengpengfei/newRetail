<?php

namespace app\api\model;
use app\api\model\CommonModel;

class ProCategoryModel extends CommonModel
{
    protected $table = 'new_pro_category';
    //-- 设置主键
    protected $pk = 'pro_category_id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','is_show_nav','grade','sort_order','disabled'
    ];
    //-- 图片获取器
    public function getProCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    /**
     * 查询该类别以及该类别下所有的子级类别（包括下级、下下级...）
     * 查询出来的类别id存放到一个数组里
     * */
    public function getAllCategory($obj,&$arr=array()){
        $res = $this->where("parent_id={$obj['pro_category_id']} and disabled=1 ")->order("sort_order","desc")->select();
        if(count($res)<1&&$obj['pro_category_id']>0)
        {
            $arr[]=$obj;
        }
        for($i=0;$i<count($res);$i++)
        {
            $this->getAllCategory($res[$i],$arr);
        }
        return $arr;
    }
}