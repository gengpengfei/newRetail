<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class StorePushMessageModel extends CommonModel{
    public $table = 'store_push_message';
    protected $pk= 'id';
    protected $hidden = ['create_time'];
}