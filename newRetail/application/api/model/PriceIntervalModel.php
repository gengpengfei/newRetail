<?php
namespace app\api\model;
/**
    +----------------------------------------------------------
     * @explain 人均价格区间模型类
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return 用于筛选条件使用
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\CommonModel;
class PriceIntervalModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_price_interval';
    protected $pk= 'price_interval_id';
    protected $hidden = ['disabled','sort_order','create_time','update_time'];


}