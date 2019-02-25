<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/5
 * Time: 16:39
 */

namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class BannerModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_banner';
    protected $pk= 'banner_id';
    protected $hidden = ['ad_code','ad_name','start_time','end_time','disabled','create_time','update_time'];
    //-- 图片获取器
    public function getImageAttr($value)
    {
        return $this->getImgAttr($value);
    }
}