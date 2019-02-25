<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ProScoreIntervalModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_pro_score_interval';
    protected $pk= 'score_interval_id';
    protected $hidden = ['disabled','sort_order','create_time','update_time'];
    //-- 图片获取器
    public function getScoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}