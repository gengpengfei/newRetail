<?php
namespace app\api\model;
/**
    +----------------------------------------------------------
     * @explain 距离区间模型类
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return 用于筛选条件使用
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\CommonModel;
class DistanceIntervalModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_distance_interval';
}