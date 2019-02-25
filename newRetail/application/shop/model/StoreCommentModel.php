<?php
namespace app\shop\model;
use app\shop\model\CommonModel;
class StoreCommentModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_store_comment';
    protected $hidden = [];

//-- 图片获取器
    public function getCommentImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}