<?php
namespace app\api\model;
use think\Model;


class CommonModel extends Model{
    use \app\api\traits\GetConfig;
    /*
     * explain:公共的图片获取器方法
     * params :@value
     * authors:Mr.Geng
     * addTime:2018/3/27 17:50
     */
    public function getImgAttr($value)
    {
        if($value ?? 0 ){
            $img = unserialize($value);
            if(count($img) == count($img, 1)){
                array_unshift($img,$this->getConfig('base_url'));
            }else{
                foreach ($img as $key=>$item) {
                    array_unshift($img[$key],$this->getConfig('base_url'));
                }
            }
        }
        return $img??[];
    }
    
}