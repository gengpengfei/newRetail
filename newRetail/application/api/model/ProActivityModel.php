<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ProActivityModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_pro_activity';
    protected $pk = "activity_id";
    protected $hidden = ['sort_order','activity_type','disabled','create_time','update_time'];
    //-- 图片获取器
    public function getActivityImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}