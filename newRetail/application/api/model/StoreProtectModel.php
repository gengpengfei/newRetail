<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreProtectModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_protect';
    protected $hidden = ['create_time','update_time','disabled'];
    //-- 图片获取器
    public function getIconImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}