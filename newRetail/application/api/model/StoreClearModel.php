<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreClearModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_clear';
    protected $hidden = ['create_time','update_time'];

}