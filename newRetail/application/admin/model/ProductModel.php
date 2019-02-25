<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\admin\model;

use traits\model\SoftDelete;
use app\admin\model\CommonModel;
class ProductModel extends CommonModel
{
    use SoftDelete;
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_product';
    protected $pk= 'product_id';
    protected $hidden = ['create_time','update_time','delete_time'];
    //-- 软删除
    protected $deleteTime = 'delete_time';

    //-- 图片获取器
    public function getProImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getProBannerImgAttr($value)
    {
        return $this->getImgAttr($value);
    }
}