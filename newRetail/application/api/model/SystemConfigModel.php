<?php
namespace app\api\model;
use app\api\model\CommonModel;
class SystemConfigModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_system_config';
    protected $hidden = [
        'id','parent_id','sort_order'
    ];
}