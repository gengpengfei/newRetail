<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class BehaviorModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    public $table = 'new_behavior';
    protected $pk= 'behavior_id';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getBehaviorImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}