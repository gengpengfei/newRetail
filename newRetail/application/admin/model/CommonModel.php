<?php
namespace app\admin\model;
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
                $img = "/small" . $img[0] . $img[1] . $img[2] . $img[3];
            }else{
                foreach ($img as $key=>$item) {
                    $img[$key] = "/small" . $item[0] . $item[1] . $item[2] . $item[3];
                }
            }
            return $img??[];
        }
    }
    
}