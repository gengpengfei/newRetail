<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class ArticleModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_article';
    protected $hidden = ['create_time','update_time'];


}