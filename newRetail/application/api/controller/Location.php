<?php
namespace app\api\controller;

use app\api\model\DistanceIntervalModel;
use app\api\model\RegionHotModel;
use app\api\model\RegionModel;
use think\Request;

class Location extends Common
{
    /*
     * explain:定位信息处理
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/23 11:34
     */
    public function handLocation(Request $request, RegionModel $regionModel)
    {
        $param = $request->param();
        //-- 判断是否获取定位
        $locationData = json_decode(json_encode($param["locationData"]));
        switch ($locationData->is_type){
            case 0:
                //-- 定位
                $region_info = $regionModel->where("city_code",$locationData->cityCode)->find();
                $locationData->city_id = $region_info->region_id;
                $locationData->lat = $locationData->latitude;
                $locationData->lng = $locationData->longitude;
                $locationData->district_id = '';
                $locationData->display_name = $locationData->zoonName;
                break;
            case 1:
                //-- 城市
                $region = $regionModel->field(['lat','lng','name'=>'display_name'])->find($locationData->region_id);
                $locationData->lat = $region->lat;
                $locationData->lng = $region->lng;
                $locationData->city_id = $locationData->region_id;
                $locationData->district_id = '';
                $locationData->display_name= $region->display_name;
                break;
            case 2:
                //-- 区县
                $region = $regionModel->field(['lat','lng'])->find($locationData->region_id);
                $locationData->lat = $region->lat;
                $locationData->lng = $region->lng;
                $locationData->city_id = $locationData->p_id;
                $locationData->district_id = $locationData->region_id;
                $locationData->display_name= $region->display_name;
                break;
            default:
                //-- 默认取上海
                $locationData->lat = '31.231706';
                $locationData->lng = '121.472644';
                $locationData->city_id = 802;
                $locationData->district_id = '';
                $locationData->display_name= '上海市';
                break;
        }
        $this->jkReturn(1,'locationData',$locationData);
    }

    /*
         * explain:热门城市列表
         * params :
         * authors:Mr.Geng
         * addTime:2018/4/13 14:15
         */
    public function regionHot(RegionHotModel $regionHotModel)
    {
        $regionList = $regionHotModel
            ->alias('h')
            ->join('new_region r','r.region_id=h.region_id','left')
            ->select();
        $this->jkReturn(1,'获取热门城市',$regionList);
    }
    /*
     * explain:获取下级地址
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/13 10:29
     */
    public function getAddressLast(Request $request,RegionModel $regionModel)
    {
        $regionId = $request->param('region_id')??0;
        $regionList = $regionModel->where(['p_id'=>$regionId])->select();
        $this->jkReturn(1,'获取下级地址',$regionList);
    }

    /*
     * explain:根据经纬度获取地址
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/19 10:49
     */
    public function getLocation(Request $request,RegionModel $regionModel){

        $longitude = $request->param('longitude');//经度
        $latitude = $request->param('latitude');//纬度

        $api = "http://api.map.baidu.com/geocoder/v2/?location=$latitude,$longitude&output=json&pois=1&ak=3uAmuvINREoHn5AHz6VtPm4iawiERZ7S";
        $json = @file_get_contents($api);//调用百度IP地址库
        $arr = json_decode($json,true);//解析json
        $resutl = $arr['result'];

        if(!$resutl){
            $api = "http://api.map.baidu.com/geocoder/v2/?location=$latitude,$longitude&output=json&pois=1&ak=3uAmuvINREoHn5AHz6VtPm4iawiERZ7S";
            $json = @file_get_contents($api);//调用百度IP地址库
            $arr = json_decode($json,true);//解析json
            $resutl = $arr['result'];

            if(!$resutl){
                $this->jkReturn('-1','获取地址失败',null);
            }
        }
        unset($resutl['pois']);
        //-- 根据cityCode 获取城市id
        $cityCode = $resutl['cityCode'];
        //-- 定位
        $region_info = $regionModel->where("city_code",$cityCode)->find();
        $resutl['city_id'] = $region_info->region_id;
        $resutl['district_id'] = '';
        $resutl['lat'] = $latitude;
        $resutl['lng'] = $longitude;
        $this->jkReturn('1','获取具体地址',$resutl);
    }
    /*
         * explain:获取距离区间
         * params :
         * authors:Mr.Geng
         * addTime:2018/4/13 10:29
         */
    public function getDistanceInterval(DistanceIntervalModel $distanceIntervalModel)
    {
        $List = $distanceIntervalModel->where('disabled=1')->select();
        $this->jkReturn(1,'获取距离区间列表',$List);
    }

}