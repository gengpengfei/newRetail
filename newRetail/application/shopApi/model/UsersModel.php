<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class UsersModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_users';
    protected $hidden = ['disabled','password','create_time','update_time'];
    //-- 图片获取器
    public function getHeadImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getRankImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}