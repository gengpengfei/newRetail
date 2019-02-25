<?php

namespace app\api\model;
use app\api\model\CommonModel;
use traits\model\SoftDelete;
class ProductModel extends CommonModel
{
    use SoftDelete;
    protected $table = 'new_product';
    //-- 设置主键
    protected $pk = 'product_id';
    //-- 软删除
    protected $deleteTime = 'delete_time';
    //--隐藏属性
    protected $hidden = [
            'pro_keywords','create_time','update_time','disabled'
    ];
    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getProBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}