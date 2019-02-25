<?php

namespace app\api\model;

use app\api\model\CommonModel;
use app\api\model\StoreCollectModel;
use app\api\model\StoreReserveConfigModel;
class StoreModel extends CommonModel
{
    protected $table = 'new_store';
    //-- 设置主键
    protected $pk = 'store_id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = ['audit_state','create_time','update_time','user_id','state','sort_order',"is_close","geohash","admin_id","province","city","district"];
    //-- 追加属性
    protected $append = [ 'is_collect','is_reserve' ];
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getStoreBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 关联评论表
    public function comments()
    {
        return $this->hasMany('store_comment_model','store_id');
    }
    //-- 关联订单表
    public function orders()
    {
        return $this->hasMany('order_model','store_id');
    }
    //-- 属性获取器 是否收藏
    public function getIsCollectAttr($value,$data)
    {
        $is_collect = StoreCollectModel::where(['store_id'=>$data['store_id'],'user_id'=>request()->user->user_id??0])->find();
        return empty($is_collect) ? 0:1;
    }
    //-- 属性获取器 是否支持预定
    public function getIsReserveAttr($value,$data)
    {
        $isReserve = StoreReserveConfigModel::where(['store_id'=>$data['store_id']])->find();
        if(empty($isReserve)){
            return 0;
        }
        return $isReserve->is_reserve;
    }

}