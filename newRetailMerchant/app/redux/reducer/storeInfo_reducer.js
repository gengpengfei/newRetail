
import * as TYPES from '../action/types';

export function storeInfo_reducer(state=initState,action){

    switch (action.type) {

        case TYPES.UPDATE_STORE_store_store_id:
            return{
                ...state,
                store_id:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_category_id:
            return{
                ...state,
                category_id:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_nav_id:
            return{
                ...state,
                nav_id:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_store_name:
            return{
                ...state,
                store_name:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_store_desc:
            return{
                ...state,
                store_desc:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_store_phone:
            return{
                ...state,
                store_phone:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_store_img:
            return{
                ...state,
                store_img:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_store_type:
            return{
                ...state,
                store_type:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_store_address:
            return{
                ...state,
                store_address:action.data,
            }
            break;

        case TYPES.UPDATE_STORE_store_is_store_info:
            return{
                ...state,
                is_store_info:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_store_info:
            return{
                ...state,
                store_info:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_store_hours:
            return{
                ...state,
                store_hours:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_lng:
            return{
                ...state,
                lng:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_lat:
            return{
                ...state,
                lat:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_geohash:
            return{
                ...state,
                geohash:action.data,
            }
            break;
    case TYPES.UPDATE_STORE_store_audit_state:
            return{
                ...state,
                audit_state:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_province:
            return{
                ...state,
                province:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_city:
            return{
                ...state,
                city:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_district:
            return{
                ...state,
                district:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_disabled:
            return{
                ...state,
                disabled:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_store_price:
            return{
                ...state,
                store_price:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_comment_num:
            return{
                ...state,
                comment_num:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_store_hot:
            return{
                ...state,
                store_hot:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_credit:
            return{
                ...state,
                store_credit:action.data,
            }
            break;
        case TYPES.UPDATE_STORE_store_score:
            return{
                ...state,
                store_score:action.data,
            }
        break;

        case TYPES.UPDATE_STORE_progress:
            return{
                ...state,
                progress:action.data,
            }
        break;
            default:
                return state;
    }
}


// const {store_id,category_id,nav_id,store_name,store_desc,store_phone,store_img,store_type,store_address,is_store_info,store_info,store_hours
//     ,lng,lat,geohash,audit_state,province,city,district,disabled,store_price,comment_num,store_hot,store_credit,store_score} = aData;
const initState = {
    store_id:null,
    category_id:null,
    nav_id:null,
    store_name:null,
    store_desc:null,
    store_phone:null,
    store_img:null,
    store_type:null,
    store_address:null,
    is_store_info:null,
    store_info:null,
    store_hours:null,
    lng:null,
    lat:null,
    geohash:null,
    audit_state:null,
    province:null,
    city:null,
    district:null,
    disabled:null,
    store_price:null,
    comment_num:null,
    store_hot:null,
    store_credit:null,
    store_score:null,
    progress:0,
}