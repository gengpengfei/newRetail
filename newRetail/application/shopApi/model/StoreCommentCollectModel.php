<?php

namespace app\shopapi\model;

use app\shopapi\model\CommonModel;
class StoreCommentCollectModel extends CommonModel
{
    protected $table = 'new_store_comment_collect';
    //-- 设置主键
    protected $pk = 'id';
}