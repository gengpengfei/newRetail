<?php

namespace app\admin\model;
use app\admin\model\CommonModel;

class StoreModel extends CommonModel
{
    public $table = 'new_store';
    //-- 设置主键
    protected $pk = 'store_id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','sort_order'
    ];

    //-- 图片获取器
    public function getStoreBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getAuditImgAttr($value)
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