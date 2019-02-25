
'use strict'

import * as TYPES from './types';

export function userInfoAction(user_type,user_param){
    switch (user_type) {
        case 'admin_id':
            return{
                type:TYPES.UPDATE_ADMIN_ID,
                data:user_param,
            }
            break;

        case 'store_id':
            return{
                type:TYPES.UPDATE_STORE_ID,
                data:user_param,
            }
            break;   
            
        case 'is_boss':
            return{
                type:TYPES.UPDATE_IS_BOSS,
                data:user_param,
            }
            break;
            
        case 'user_name':
            return{
                type:TYPES.UPDATE_NAME,
                data:user_param,
            }
            break;

        case 'head_img':
            return{
                type:TYPES.UPDATE_HEAD_IMG,
                data:user_param,
            }
            break;

        case 'mobile':
            return{
                type:TYPES.UPDATE_MOBILE,
                data:user_param,
            }
            break;

        case 'login_count':
            return{
                type:TYPES.UPDATE_LOGIN_COUNT,
                data:user_param,
            }
            break;

        case 'last_login_time':
            return{
                type:TYPES.UPDATE_LAST_LOGIN_TIME,
                data:user_param,
            }
            break;

        case 'last_ip':
            return{
                type:TYPES.UPDATE_LAST_IP,
                data:user_param,
            }
            break;

        case 'token':
            return{
                type:TYPES.UPDATE_TOKEN,
                data:user_param,
            }
            break;

        case 'pay_password':
            return{
                type:TYPES.UPDATE_PAY_PASSWORD,
                data:user_param,
            }
            break;
        
        default:
    }
}
