<?php
namespace app\api\model;
use app\api\model\CommonModel;
class RegionHotModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_region_hot';
    protected $hidden = ['create_time'];
}