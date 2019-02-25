<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ProCommentModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_pro_comment';
    protected $pk = 'id';
    protected $hidden = [
        'update_time','parent_id','order_id','order_sn'
    ];
    //-- 图片获取器
    public function getCommentImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}