<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreUserModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'store_user';
    protected $hidden = ['disabled','password','create_time','update_time'];
    //-- 图片获取器
    public function getHeadImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}