import {
    NetWork_Post,
    NetWork_Get
} from './baseNet';


function accountLogin(bodydata,callback){


    let netapi = 'accountLogin',
    reqError = {
        error: {
            code: '-1',
            msg: '登录失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}

function quickLogin(bodydata,callback){


    let netapi = 'quickLogin',
    reqError = {
        error: {
            code: '-1',
            msg: '快捷登录失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}


function sendMobileCode(bodydata,callback){


    let netapi = 'sendMobileCode',
    reqError = {
        error: {
            code: '-1',
            msg: '发送手机验证码失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}


function register(bodydata,callback){


    let netapi = 'register',
    reqError = {
        error: {
            code: '-1',
            msg: '注册失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}

function updatePassword(bodydata,callback){


    let netapi = 'updatePassword',
    reqError = {
        error: {
            code: '-1',
            msg: '修改密码失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}


function token_login(bodydata,callback){


    let netapi = 'token_login',
    reqError = {
        error: {
            code: '-1',
            msg: '登录失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}

function checkUnbindCode(bodydata,callback){


    let netapi = 'checkUnbindCode',
    reqError = {
        error: {
            code: '-1',
            msg: '解绑手机验证失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}

function bindNewMobile(bodydata,callback){


    let netapi = 'bindNewMobile',
    reqError = {
        error: {
            code: '-1',
            msg: '绑定手机验证失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}


export {
    accountLogin,
    quickLogin,
    sendMobileCode,
    register,
    updatePassword,
    token_login,
    checkUnbindCode,
    bindNewMobile,
}