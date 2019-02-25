<?php

namespace app\admin\model;

use app\admin\model\CommonModel;
class StoreCloseModel extends CommonModel
{
    public $table = 'store_close_log';
    //-- 设置主键
    protected $pk = 'id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time'
    ];

    //-- 图片获取器
    public function getCloseImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}