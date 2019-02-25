<?php
namespace app\admin\model;

use app\admin\model\CommonModel;
class StoreHotConfigModel extends CommonModel{
    public $table = 'new_store_hot_config';
    protected $pk= 'id';
    protected $hidden = ['create_time'];
}