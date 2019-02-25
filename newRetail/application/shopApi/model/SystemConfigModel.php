<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class SystemConfigModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_system_config';
    protected $hidden = [
        'id','parent_id','desc','sort_order'
    ];
}