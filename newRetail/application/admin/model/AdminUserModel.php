<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/2
 * Time: 19:46
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class AdminUserModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'admin_user';
    protected $hidden = [];

}