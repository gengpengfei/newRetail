<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreUserActionModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'store_user_action';
    protected $hidden = ['create_time','update_time'];


}