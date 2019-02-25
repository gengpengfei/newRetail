<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class UserModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_users';
    protected $hidden = ['disabled','password','update_time'];
    //-- 图片获取器
    public function getHeadImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

}