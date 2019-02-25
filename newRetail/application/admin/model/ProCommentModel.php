<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/16
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class ProCommentModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    public $table = 'new_pro_comment';
    protected $pk= 'id';
    protected $hidden = ['create_time'];
    //-- 图片获取器
    public function getProCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    //-- 图片获取器
    public function getCommentImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}