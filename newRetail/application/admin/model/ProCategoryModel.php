<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/16
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class ProCategoryModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    public $table = 'new_pro_category';
    protected $pk= 'pro_category_id';
    protected $hidden = ['create_time','update_time'];
    //-- 图片获取器
    public function getProCategoryImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    public function getChildCategory($obj){
        $res = $this->field(['pro_category_id','pro_category_name','grade'])->where("parent_id={$obj['pro_category_id']} and disabled=1")->order("sort_order","desc")->select()->toArray();
        return $res;
    }
}