<?php

namespace app\api\model;
use app\api\model\CommonModel;
use app\api\model\StoreCommentCollectModel;

class StoreCommentModel extends CommonModel
{
    protected $table = 'new_store_comment';
    //-- 设置主键
    protected $pk = 'store_comment_id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = [
        'update_time','state','type'
    ];
    //-- 追加属性
    protected $append = ['is_collect'];
    //-- 图片获取器
    public function getCommentImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getHeadImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    
    //-- 追加属性
    public function getIsCollectAttr($value,$data)
    {
        $is_collect = StoreCommentCollectModel::where(['store_comment_id'=>$data['store_comment_id'],'user_id'=>request()->user->user_id??0])->find();
        return empty($is_collect) ? 0:1;
    }
}