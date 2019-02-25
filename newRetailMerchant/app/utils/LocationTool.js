import {
    Platform
} from 'react-native';

import {getCurrentHandLocation} from '../network/OtherNetApi';
import {storageLocationStatus, getLocationStatus } from "../dataBase/userinfoStorage";
import {Geolocation} from 'react-native-baidu-map';

const defautlData = {
                city_id:802,
                district_id:"",
                is_type:"1",// 0gps，1城市选择，2，区县
                lat:"31.264998",
                lng:"121.612833",
                display_name:"上海",
            }
/**
 * 
 * @param {*} isSave 获取最新的定位信息是否保存，默认true
 * @param {code,data} callBack 
 * 如果code == -1，获取gps定位失败、获取最近一次定位缓存信息失败，data，为默认定位值defautlData
 * 如果code == 1，获取gps定位失败、获取最近一次定位缓存信息成功，data为最近一次的定位缓存信息
 * 如果code == 2，获取gps定位成功，data为最新定位信息
 */
function getLastPositionInfo(callBack,isSave=true) {

    Geolocation.getCurrentPosition()
        .then(data => {
            let callBackData = {
                cityCode: data.cityCode,
                district_id: "",
                longitude: data.longitude,
                latitude: data.latitude,
                is_type: '0',
                zoonName: data.district,
            };


            getHandLocation({locationData:callBackData},callBack,isSave)

        })
        .catch(e => {
            getPositionInfo(callBack)
        });
}


function getHandLocation(inputdata,result,isSave){

    getCurrentHandLocation(inputdata, (responseData) => {
        
        let {code = -1 ,data ,msg} = responseData;
    
        if (code == 1) {

            if(isSave){
                storageLocationStatus(data.city_id, data.district_id, data.lat, data.lng, data.is_type,data.display_name);//本地保存最新gps定位
            }
            result({code:2,data:data});
            
        } else {
            getPositionInfo(result)
        }
    })
}

/**
 * 从本地获取定位信息
 * @param {code,data} resultCallBack ,
 * 如果code == -1，则从本地获取定位信息失败，data，为默认定位值defautlData,
 * 如果code == 1，则从本地获取定位信息成功，data为最近一次的定位缓存信息
 */
function getPositionInfo(resultCallBack){
    getLocationStatus((callBack)=>{
        
        const {code,data} = callBack;
        if(code === -1){
            // 给默认值
            resultCallBack({code:-1,data:defautlData})
        }else{
            resultCallBack({code:1,data:data})
        }

    });
}

function savaLocationInfo(data){
    
    storageLocationStatus(data.city_id, data.district_id, data.lat, data.lng, data.is_type,data.display_name);//本地保存最新gps定位
}

export {
    getPositionInfo,//获取定位信息（直接读取定位信息，本地-->默认）
    getLastPositionInfo,// 获取最新定位信息（gps定位-->本店-->默认）
    savaLocationInfo,

}