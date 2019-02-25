<?php

namespace app\api\service;

use app\api\model\ProCartModel;
use app\api\model\RankModel;

class ShopService extends CommonService
{
    /*
     * explain:商品规则判定
     * params :@product_id
     * authors:Mr.Geng
     * addTime:2018/4/8 11:13
     */
    public function judgeGoods($product)
    {
        //-- 删除判断
        if(empty($product)|| $product->delete_time != null){
            return ['msg'=>'商品不存在'];
        }
        //-- 上下架判断
        if($product->disabled == 0){
            return ['msg'=>'商品已下架'];
        }
    }
    
    /*
     * explain:商品库存判断
     * params :@obj
     * authors:Mr.Geng
     * addTime:2018/4/8 11:58
     */
    public function judgeStock($product)
    {
        $proNum = request()->param('pro_num');
        if($product->pro_stock<$proNum){
            return ['msg'=>'商品'.$product->pro_name.'库存不足,您最多可以购买'.$product->pro_stock.'个'];
        }
    }

    /*
     * explain:商品可购买等级判断
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/8 11:59
     */
    public function judgeRank($product)
    {
        //-- 等级判断
        $proRank = RankModel::where(["rank_id"=>$product->rank_id])->find();
        $userRankId = request()->user->rank_id;
        $userRank = RankModel::where(["rank_id"=>$userRankId])->find();
        if($userRank->rank_num<$proRank->rank_num){
            return ['msg'=>'您的等级不足以购买商品'.$product->pro_name.',可通过完成等级任务来提高等级权限'];
        }
    }

    /*
     * explain:判断是否已经加入购物车
     * params :@obj
     * authors:Mr.Geng
     * addTime:2018/4/8 11:34
     */
    public function isCart()
    {
        $proId = request()->param('product_id');
        $userId = request()->user->user_id;
        return ProCartModel::where(['product_id'=>$proId,'user_id'=>$userId])->find();
    }

}