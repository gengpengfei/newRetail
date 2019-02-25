<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class ProActivityInfoModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_pro_activity_info';
    protected $pk = "id";
    protected $hidden = ['create_time','update_time'];

    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}