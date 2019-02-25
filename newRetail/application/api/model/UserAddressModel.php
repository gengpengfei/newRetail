<?php
namespace app\api\model;
use app\api\model\CommonModel;
class UserAddressModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_user_address';
    protected $hidden = ['create_time','update_time'];
    //--  查询的时候自动追加字段
    public $append = ['is_default'];
    //-- 创建自动插入字段
    protected $insert = ['address_name'];
    //-- 属性获取器-判断改地址是否是默认地址
    public function getIsDefaultAttr($value,$data)
    {
        return (request()->user->address_id == $data['address_id']) ? 1 : 0 ;
    }
    //-- 数据完成操作 , 在新增和更新的时候会自动完成字段的插入和更新
    public function setAddressNameAttr()
    {
        $district = request()->param('district')??0;
        $region = RegionModel::field(['merger_name'])->find($district);
        return implode(' ',explode(',',$region->merger_name));
    }
}