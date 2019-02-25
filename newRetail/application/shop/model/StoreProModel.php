<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/2
 * Time: 19:46
 */

namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreProModel extends CommonModel{
    public $table = 'new_store_pro';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time','store_pro_id','store_id','city'
    ];
    //-- 图片获取器
    public function getStoreProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}