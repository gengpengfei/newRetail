<?php
namespace app\shopapi\model;

use app\shopapi\model\CommonModel;
class CategoryModel extends CommonModel
{
    protected $table = 'new_category';
    //-- 设置主键
    protected $pk = 'category_id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = [
        'create_time', 'update_time', 'is_show_nav', 'grade', 'sort_order', 'disabled',
    ];

    //-- 图片获取器
    public function getCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    /**
     * 查询该类别以及该类别下所有的子级类别（包括下级、下下级...）
     * 查询出来的类别id存放到一个数组里.
     * */
    public function getAllCategory($obj, &$arr = array())
    {
        $res = $this->where("parent_id={$obj['category_id']} and disabled=1")->order('sort_order', 'desc')->select();

        if (count($res) < 1 && $obj['category_id'] > 0) {
            $arr[] = $obj;
        }
        for ($i = 0; $i < count($res); ++$i) {
            $this->getAllCategory($res[$i], $arr);
        }

        return $arr;
    }

    


    /**
     * 根据category_id 查询分类
    */
    public function getCategoryItemWithCategoryId($arr,&$resultArr = array())
    {
        foreach ($arr as $item){
            $res = $this->where("category_id={$item['category_id']} and disabled=1")->order('sort_order')->find();
            $resultArr[]=$res;
        }
        return $resultArr;
    }

    /***
     * 根据nav_id获取分类
     */
    public function getCategoryWithNav_id($nav_id){

        $map['disabled'] = '1';
        if($nav_id){
            $map['nav_id'] = $nav_id;
        }

        $res = $this->where($map)->order('sort_order')->select();
        return $res;

    }








}
