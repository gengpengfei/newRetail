<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreReserveConfigModel extends CommonModel{
    public $table = 'new_store_reserve_config';
    protected $pk= 'id';
    protected $hidden = ['create_time'];

}