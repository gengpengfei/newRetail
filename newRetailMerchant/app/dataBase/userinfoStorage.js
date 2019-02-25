
//缓存登陆状态
function storageLoginStatus(mobile,token,isLoggedIn) {
    storage.save({   //注意storage的用法  （save、load？？？）
        key:'loginState',
        data:{
            mobile: mobile,
            token:token,
            // password:password,
            isLoggedIn:isLoggedIn, //是否已登陆
        }
    });
}

// 获取缓存里登陆状态的信息
function getLoginStatus(callback) {
    storage.load({
        key:'loginState',
    }).
    then(ret=>{   //成功时的回调函数
        callback({
            code:1,
            data:ret,
        })

    }).
    catch(err=>{ //失败时的回调函数
        callback({
            code:-1,
            data:err,
        })
    })
}

//缓存登陆状态
function storageLocationStatus(cityCode,district_id,latitude,longitude, is_type,display_name) {
    
    storage.save({   //注意storage的用法  （save、load？？？）
        key:'locationState',
        data:{
       
            display_name:display_name,
            city_id: cityCode,
            district_id: district_id,
            lat: latitude,
            lng: longitude,
            is_type: is_type
        }
    });
}

// 获取缓存里登陆状态的信息
function getLocationStatus(callback) {
    storage.load({
        key:'locationState',
    }).
    then(ret=>{   //成功时的回调函数
        callback({
            code:1,
            data:ret,
        })

    }).
    catch(err=>{ //失败时的回调函数
        callback({
            code:-1,
            data:err,
        })
    })
}

export {storageLoginStatus,getLoginStatus, storageLocationStatus, getLocationStatus}
//注意此处是使用export，使用时import { storegeLoginStatus, getLoginStatus} from '../dataBase/userinfoStorage.js'; 需要加{  }
