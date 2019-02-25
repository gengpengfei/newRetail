<?php

namespace app\shopapi\model;
use app\shopapi\model\CommonModel;

class StoreModel extends CommonModel
{
    protected $table = 'new_store';
    //-- 设置主键
    protected $pk = 'store_id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = ['store_keywords','create_time','update_time','user_id','state','sort_order',"is_reserve","is_recomm","is_close"];
    //-- 图片获取器
    public function getStoreImgAttr($value)
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

}