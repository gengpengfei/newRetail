<?php
namespace app\api\model;
use app\api\model\CommonModel;
class BehaviorModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_behavior';
    protected $pk= 'behavior_id';
    protected $hidden = ['disabled','create_time','update_time'];
    //-- 图片获取器
    public function getBehaviorImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}