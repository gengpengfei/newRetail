<?php
namespace app\api\controller;

/**
    +----------------------------------------------------------
     * @explain 搜索类
    +----------------------------------------------------------
     * @access class
    +----------------------------------------------------------
     * @return public
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/

use app\api\model\CategoryModel;
use app\api\model\SearchLogModel;
use app\api\model\RegionModel;
use app\api\model\PriceIntervalModel;

use think\Request;

class Search extends Common {
    /*
     * explain:热门搜索词汇
     * params :null
     * authors:Mr.Geng
     * addTime:2018/3/20 15:32
     */
    public function searchHot(SearchLogModel $searchLogModel)
    {
        $searchList = $searchLogModel->order('search_times','desc')->limit(10)->select();
        $this->jkReturn(1,'热门搜索词汇',$searchList);
    }



    /**
     * 区域模糊查询城市区域
     */
    public function areaFuzzyQuery(Request $request,RegionModel $regionModel)
    {

        $areaStr = $request->param('search_str');

        $ddd = $regionModel->queryCityWithSearchStr($areaStr);
        $this->jkReturn(1,'区域查询',$ddd);
    }


    /**
     * 搜索补全
    */
    public function searchComplement(Request $request,SearchLogModel $searchLogModel){

        $searchStr = $request->param('search_str');
        $complementSerachList = $searchLogModel->getSearchStrList($searchStr);
        $this->jkReturn(1,'搜索补全',$complementSerachList);


    }


    /**
     * @deprecated 热门搜索
     * @param Request $request
     * @param SearchLogModel $searchLogModel
     */
    public  function  hotSearchList(Request $request,SearchLogModel $searchLogModel){
        $hostlist = $searchLogModel->limit(10)
            ->order('search_num', 'desc')
            ->select();

        $this->jkReturn(1,'热门搜索',$hostlist);
    }
    /**
     * 获取价格区间
     */
    public function getPriceInterval(PriceIntervalModel $priceIntervalModel){

        $searchList = $priceIntervalModel->order('sort_order')->select();
        $this->jkReturn(1,'价格区间',$searchList);
    }


    /**
     * 分类搜索条件
     * @param Request $request
     * @param CategoryModel $categoryModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchCategoryConditions(Request $request, CategoryModel $categoryModel){
        $nav_id = $request->param('nav_id');
        /**
         * 获取一级分类
         */
        $categoryList = $categoryModel->getCategoryWithNav_id($nav_id);
        foreach ($categoryList as $value){
            if($value['parent_id'] == 0){
                $pList[] = $value;
            }
        }
        /**
         * 获取二级分类
         */
        for ($i = 0; $i < count($pList); $i++){
            $pItem = $pList[$i];

            $cateID = $pItem['category_id'];
            $map['disabled'] = '1';
            $map['parent_id'] = $cateID;
            $cList = $categoryModel->where($map)->order('sort_order')->select();

            $pItem['children'] = $cList;

        }

        $this->jkReturn(1,'分类搜索s条件',$pList);

    }














}