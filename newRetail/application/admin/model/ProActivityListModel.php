<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class ProActivityListModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_pro_activity_list';
    protected $pk = "activity_list_id";
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getActivityListImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}