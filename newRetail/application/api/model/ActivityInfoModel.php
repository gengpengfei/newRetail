<?php
namespace app\api\model;
use app\api\model\CommonModel;
class ActivityInfoModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_activity_info';
    protected $pk = "id";
    protected $hidden = ['create_time','update_time','is_main','sort_order'];
    //-- 优惠券图片获取器
    public function getStoreImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

    //-- 图片获取器
    public function getVoucherImgAttr($value)
    {
        return $this->getImgAttr($value);
    }

}