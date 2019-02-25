
import * as TYPES from '../action/types';

export function userInfo_reducer(state=initState,action){

    switch (action.type) {

        case TYPES.UPDATE_ADMIN_ID:
            return{
                ...state,
                admin_id:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_ID:
            return{
                ...state,
                store_id:action.data,
            }
            break;

        case TYPES.UPDATE_IS_BOSS:
            return{
                ...state,
                is_boss:action.data,
            }
            break;

        case TYPES.UPDATE_NAME:
            return{
                ...state,
                user_name:action.data,
            }
            break;

        case TYPES.UPDATE_HEAD_IMG:
            return{
                ...state,
                head_img:action.data,
            }
            break;

        case TYPES.UPDATE_MOBILE:
            return{
                ...state,
                mobile:action.data,
            }
            break;

        case TYPES.UPDATE_LOGIN_COUNT:
            return{
                ...state,
                login_count:action.data,
            }
            break;

        case TYPES.UPDATE_LAST_LOGIN_TIME:
            return{
                ...state,
                last_login_time:action.data,
            }
            break;

        case TYPES.UPDATE_LAST_IP:
            return{
                ...state,
                last_ip:action.data,
            }
            break;

        case TYPES.UPDATE_TOKEN:
            return{
                ...state,
                token:action.data,
            }
            break;

        case TYPES.UPDATE_PAY_PASSWORD:
            return{
                ...state,
                pay_password:action.data,
            }
            break;
        

        default:
            return state;
    }
}


// admin_id,store_id,is_boss,user_name,head_img,mobile,login_count,last_login_time,last_ip,token,pay_password
const initState = {
    admin_id:null,
    store_id:null,
    is_boss:null,
    user_name:null,
    head_img:null,
    mobile:null,
    login_count:null,
    last_login_time:null,
    last_ip:null,
    token:null,
    pay_password:null,
}