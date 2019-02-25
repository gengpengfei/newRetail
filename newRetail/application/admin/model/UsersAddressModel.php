<?php
namespace app\admin\model;
use app\admin\model\CommonModel;
class UsersAddressModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    public $table = 'new_user_address';
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
        $province = request()->param('province')??0;
        $city = request()->param('city')??0;
        $district = request()->param('district')??0;
        //-- 此处由于数据字段 name 和模型私有属性name 字段重复 , 顾重命名
        $province = RegionModel::field(['name'=>'region_name'])->find($province);
        $city = RegionModel::field(['name'=>'region_name'])->find($city);
        $district = RegionModel::field(['name'=>'region_name'])->find($district);
        return $province->region_name.($city->region_name=='市辖区'? '':$city->region_name).$district->region_name;
    }
}