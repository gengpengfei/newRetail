<?php
namespace app\api\model;
use app\api\model\CommonModel;
class RankModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_rank';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getRankImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}