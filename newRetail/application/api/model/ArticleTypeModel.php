<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ArticleTypeModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_article_type';
    protected $pk = "activity_id";
}