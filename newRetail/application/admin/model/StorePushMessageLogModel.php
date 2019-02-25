<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class StorePushMessageLogModel extends CommonModel{
    public $table = 'store_push_message_log';
    protected $pk= 'id';
    protected $hidden = ['create_time'];
}