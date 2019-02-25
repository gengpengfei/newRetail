<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class MobileMessageModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'admin_mobile_message';
    protected $hidden = [];
}