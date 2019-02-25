

import {
    NetWork_Post,
    NetWork_Get
} from './baseNet';


function addMember(bodydata,callback){


    let netapi = 'addMember',
    reqError = {
        error: {
            code: '-1',
            msg: '添加业务员失败'
        }
    }
   NetWork_Post(netapi, bodydata, callback, reqError);

}



export {
    addMember,
}