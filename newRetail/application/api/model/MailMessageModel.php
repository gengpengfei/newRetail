<?php
namespace app\api\model;
use app\api\model\CommonModel;
class MailMessageModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_mail';
    protected $hidden = [];

}