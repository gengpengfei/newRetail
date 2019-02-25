<?php

namespace app\api\model;
use app\api\model\CommonModel;
class LoginModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_mobile_code';
    protected $hidden = ['update_time','id','create_time','code_type'];


}