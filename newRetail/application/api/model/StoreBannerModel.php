<?php
namespace app\api\model;
use app\api\model\CommonModel;
class StoreBannerModel extends CommonModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_banner';
    protected $hidden = ['disabled','create_time','update_time'];

    //-- 图片获取器
    public function getImageAttr($value)
    {
        return $this->getImgAttr($value);
    }
}