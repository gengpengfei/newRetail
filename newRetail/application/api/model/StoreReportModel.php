<?php

namespace app\api\model;
/**
    +----------------------------------------------------------
     * @explain 店铺日报统计表
    +----------------------------------------------------------
     * @access class
    +----------------------------------------------------------
     * @return 店铺日报统计表
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\CommonModel;
class StoreReportModel extends CommonModel
{
    protected $table = 'new_store_report';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
    ];
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}