<?php

namespace app\api\model;
use app\api\model\CommonModel;
class ProShowModel extends CommonModel
{
    protected $table = 'new_pro_show';
    //-- 设置主键
    protected $pk = 'id';
    //--隐藏属性
    protected $hidden = [
        'create_time', 'disabled'
    ];

    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);

    }
}