<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class ArticleTypeModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_article_type';
    protected $hidden = ['create_time','update_time'];


}