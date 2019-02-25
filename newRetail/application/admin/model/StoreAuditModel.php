<?php

namespace app\admin\model;
use app\admin\model\CommonModel;

class StoreAuditModel extends CommonModel
{
    public $table = 'new_store_audit';
    //-- 设置主键
    protected $pk = 'id';
    //-- 只读字段
    protected $readonly = [];
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time'
    ];

    //-- 图片获取器
    public function getAuditIdentityFaceAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getAuditIdentityCoinAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getAuditLicenseAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //-- 图片获取器
    public function getContractImageAttr($value)
    {
        return $this->getImgAttr($value);
    }
    //temp_license
    public function getTempLicenseAttr($value)
    {
        return $this->getImgAttr($value);
    }
}