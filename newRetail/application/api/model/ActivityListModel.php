<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ActivityListModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_activity_list';
    protected $pk = "activity_list_id";
    protected $hidden = ['create_time','update_time','disabled'];
    //-- 图片获取器
    public function getActivityListImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getActivityListBgimgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}