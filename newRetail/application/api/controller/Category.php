<?php

namespace app\api\controller;

/**
 * +----------------------------------------------------------
 * @explain 系统分类
 * +----------------------------------------------------------
 * @access 店铺之上的分类
 * +----------------------------------------------------------
 * @return class
+----------------------------------------------------------
 * @acter Mr.Geng
 * +----------------------------------------------------------
 **/
use app\api\model\CategoryModel;
use app\api\model\NavModel;
use think\Request;
use app\api\model\NearCategoryModel;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/6
 * Time: 16:47
 */
class Category extends Common
{
    /*
     * params :首页分类
     * explain:
     * authors:Mr.Geng
     * addTime:2018/3/12 16:37
     */
    public function navList(NavModel $navModel)
    {
        $navList = $navModel->where(["disabled" => 1])->order('sort_order', 'desc')->select();
        $this->jkReturn(1, "导航列表", $navList);
    }

    /*
     * params :获取分类列表
     * explain:
     * authors:Mr.Geng
     * addTime:2018/3/14 15:01
     */
    public function categoryList(Request $request, CategoryModel $categoryModel)
    {
        $param = $request->param();
        $categary_list = $categoryModel->getAllCategory(['category_id' => $param['category_id']??0]);
        $this->jkReturn(1, "子分类列表", $categary_list);
    }

    /**
     * 附近优惠分类列表
     */
    public function nearCategoryList(NearCategoryModel $nearCategoryModel, CategoryModel $categoryModel)
    {
        $nearList = $nearCategoryModel->getNearCategory();
        foreach ($nearList as $item){
            $res = $categoryModel->where("category_id={$item['category_id']} and disabled=1")->order('sort_order')->find();
            $resultArr[]=$res??[];
        }
        $this->jkReturn(1, "附近优惠分类列表", $resultArr);
    }


}