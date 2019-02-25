<?php

namespace app\api\model;
use app\api\model\CommonModel;

class StoreCommentCollectModel extends CommonModel
{
    protected $table = 'new_store_comment_collect';
    //-- 设置主键
    protected $pk = 'id';
}