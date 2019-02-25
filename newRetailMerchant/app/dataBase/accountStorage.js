
//缓存登陆状态
function storageLoginStatus(mobile,token,isLoggedIn) {
    storage.save({   //注意storage的用法  （save、load？？？）
        key:'loginState',
        data:{
            mobile: mobile,
            token:token,
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

export {storageLoginStatus,getLoginStatus}

