<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/8
 * Time: 19:46
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class AdminLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'admin_log';
    protected $hidden = [];


}