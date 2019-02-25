<?php
/**
 * ActivityListModel
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class ActivityListModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_activity_list';
    protected $pk= 'activity_list_id';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getActivityListImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getActivityListBgImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}