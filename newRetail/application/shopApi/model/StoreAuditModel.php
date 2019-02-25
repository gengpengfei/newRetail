<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class StoreAuditModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_store_audit';
    //-- 图片获取器
    public function getTempLicenseAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getAuditIdentityFaceAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getAuditIdentityCoinAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getcontractImageAttr($value)
    {
        return $this->getImgAttr($value);
    }
    public function getAuditLicenseAttr($value)
    {
        return $this->getImgAttr($value);
    }

}