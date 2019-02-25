

import { userInfoAction } from "../../../redux/action/user_action";
import { storageLoginStatus, getLoginStatus } from "../../../dataBase/accountStorage";
import { storeInfoAction } from "../../../redux/action/store_action";
import { information } from "../../../network/shopNetApi";


function handleLoginData(aDispatch,aData,ignoreSave){
    
    
    const {code,data={},msg} = aData;
    const {mobile,token,admin_id} = data;
   
    if(ignoreSave){
        // 保留原来的登录状态，但是刷新账号信息
        getLoginStatus((loginState)=>{
            const {code,data} = loginState
            var shouldLogin = false;
            if(code == 1){
                shouldLogin = data.isLoggedIn;
            }
            storageLoginStatus(mobile, token,shouldLogin);
        })

    }else{
        // 更新登录本地数据库状态
        
        storageLoginStatus(mobile, token,code == 1);
    }

    // 请求一次用户所有信息

    
    information({admin_id:admin_id},(response)=>{
        
        handleUserInfomation(aDispatch,response);
    });

}


function handleUserInfomation(aDispatch,aData){
    
    const { code, msg, data={} } = aData;
    const {user_info={},store_info={},progress} = data;
    updataUserInfo(aDispatch,user_info);
    updataStoreInfo(aDispatch,store_info);
    aDispatch(storeInfoAction("progress",progress));
    
}


function updataUserInfo(aDispatch,aData={}){

    const {admin_id,store_id,is_boss,user_name,head_img,mobile,login_count,last_login_time,last_ip,token,pay_password} = aData;

    
    // 更新redux
    aDispatch(userInfoAction("admin_id",admin_id));
    aDispatch(userInfoAction("store_id",store_id));
    aDispatch(userInfoAction("is_boss",is_boss));
    aDispatch(userInfoAction("user_name",user_name));
    aDispatch(userInfoAction("head_img",head_img));
    aDispatch(userInfoAction("mobile",mobile));
    aDispatch(userInfoAction("login_count",login_count));
    aDispatch(userInfoAction("last_login_time",last_login_time));
    aDispatch(userInfoAction("last_ip",last_ip));
    aDispatch(userInfoAction("token",token));
    aDispatch(userInfoAction("pay_password",pay_password));

}

function updataStoreInfo(aDispatch,aData={}){
        
    const {store_id,category_id,nav_id,store_name,store_desc,store_phone,store_img,store_type,store_address,is_store_info,store_info,store_hours,lng,lat,geohash,audit_state,province,city,district,disabled,store_price,comment_num,store_hot,store_credit,store_score} = aData;
        
    aDispatch(storeInfoAction("store_id",store_id));
    aDispatch(storeInfoAction("category_id",category_id));
    aDispatch(storeInfoAction("nav_id",nav_id));
    aDispatch(storeInfoAction("store_name",store_name));
    aDispatch(storeInfoAction("store_desc",store_desc));
    aDispatch(storeInfoAction("store_phone",store_phone));
    aDispatch(storeInfoAction("store_img",store_img));
    aDispatch(storeInfoAction("store_type",store_type));
    aDispatch(storeInfoAction("store_address",store_address));
    aDispatch(storeInfoAction("is_store_info",is_store_info));
    aDispatch(storeInfoAction("store_info",store_info));
    aDispatch(storeInfoAction("store_hours",store_hours));
    aDispatch(storeInfoAction("lng",lng));
    aDispatch(storeInfoAction("lat",lat));
    aDispatch(storeInfoAction("geohash",geohash));
    aDispatch(storeInfoAction("audit_state",audit_state));
    aDispatch(storeInfoAction("province",province));
    aDispatch(storeInfoAction("city",city));
    aDispatch(storeInfoAction("district",district));
    aDispatch(storeInfoAction("disabled",disabled));
    aDispatch(storeInfoAction("store_price",store_price));
    aDispatch(storeInfoAction("comment_num",comment_num));
    aDispatch(storeInfoAction("store_hot",store_hot));
    aDispatch(storeInfoAction("store_credit",store_credit));
    aDispatch(storeInfoAction("store_score",store_score));

}


export {
    handleLoginData,
    handleUserInfomation,
}


