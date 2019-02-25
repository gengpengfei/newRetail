<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class OrderProModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_order_pro';
    protected $pk= 'order_pro_id';
    protected $hidden = ['update_time'];

    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }


}