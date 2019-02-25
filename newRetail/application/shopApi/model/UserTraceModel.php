<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class UserTraceModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_trace';
    protected $hidden = ['create_time'];
    //-- 图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}