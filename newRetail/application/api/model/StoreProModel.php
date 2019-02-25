<?php

namespace app\api\model;
use app\api\model\CommonModel;
use app\api\model\StoreProLikeModel;
class StoreProModel extends CommonModel
{
    protected $table = 'new_store_pro';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','store_id','city'
    ];
    //-- 追加属性
    protected $append = [ 'is_like' ];
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getStoreProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 属性获取器
    public function getIsLikeAttr($value,$data)
    {
        $data['store_id'];
        $is_like = StoreProLikeModel::where(['store_pro_id'=>$data['store_pro_id'],'user_id'=>request()->user->user_id])->find();
        return empty($is_like) ? 0:1;
    }
}