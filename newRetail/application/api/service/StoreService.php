<?php

namespace app\api\service;


use geohash\Geohash;
class StoreService
{
    use \app\api\traits\GetConfig;
    /*
     * explain:获取距离的查询条件
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/19 15:50
     */
    public function getGeohashLike($nearDistance=1)
    {
        $locationData = request()->locationData;
        $geohash = new Geohash();
        $n_geohash = $geohash->encode($locationData->lat,$locationData->lng);
        //-- 参数n代表Geohash精确的位数,就是大概距离；n=6时候，大概为附近1.2千米 n=5 为附近2.4千米 , n=4 为附近20千米
        switch($nearDistance){
            case 1:
                $n = 6;
                break;
            case 2:
                $n = 5;
                break;
            default:
                $n = 4;
        }
        return substr($n_geohash, 0, $n);
    }

    /*
     * params :获取距离店铺的距离
     * explain: $StoreModel $UserModel
     * authors:Mr.Geng
     * addTime:2018/3/9 14:32
     */
    public function getStoreDistance(&$storeModel)
    {
        $locationData = request()->locationData;
        if(empty($locationData)){
            $storeModel->distance_check = '未知';
            $storeModel->distance = '未知';
        }else{
            $distance = $this->calcDistance($locationData->lat,$locationData->lng,$storeModel->lat,$storeModel->lng);
            $storeModel->distance_check = strlen(floor($distance))>3? sprintf("%.1f",($distance/1000)).'km':sprintf("%.0f",($distance)).'m';
            $storeModel->distance = $distance;
        }
    }
    /**
     * 获取两个经纬度之间的距离
     * @param string $lat1 纬一
     * @param String $lng1 经一
     * @param String $lat2 纬二
     * @param String $lng2 经二
     * @return float 返回两点之间的距离
     */
    protected function calcDistance($lat1, $lng1, $lat2, $lng2) {
        /** 转换数据类型为 double */
        $lat1 = doubleval($lat1);
        $lng1 = doubleval($lng1);
        $lat2 = doubleval($lat2);
        $lng2 = doubleval($lng2);
        /** 以下算法是 Google 出来的，与大多数经纬度计算工具结果一致 */
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515*1000 ;
        return sprintf("%.2f", ($miles* 1.609344));
    }

}