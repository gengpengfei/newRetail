
'use strict'

import * as TYPES from './types';

export function storeInfoAction(store_type,user_param){
    switch (store_type) {
        case 'store_id':
            return{
                type:TYPES.UPDATE_STORE_store_store_id,
                data:user_param,
            }
            break;

        case 'category_id':
            return{
                type:TYPES.UPDATE_STORE_store_category_id,
                data:user_param,
            }
            break;   
            
        case 'nav_id':
            return{
                type:TYPES.UPDATE_STORE_store_nav_id,
                data:user_param,
            }
            break;
            
        case 'store_name':
            return{
                type:TYPES.UPDATE_STORE_store_store_name,
                data:user_param,
            }
            break;

        case 'store_desc':
            return{
                type:TYPES.UPDATE_STORE_store_store_desc,
                data:user_param,
            }
            break;
        case 'store_phone':
            return{
                type:TYPES.UPDATE_STORE_store_store_phone,
                data:user_param,
            }
            break;

        case 'store_img':
            return{
                type:TYPES.UPDATE_STORE_store_store_img,
                data:user_param,
            }
            break;   
            
        case 'store_type':
            return{
                type:TYPES.UPDATE_STORE_store_store_type,
                data:user_param,
            }
            break;
            
        case 'store_address':
            return{
                type:TYPES.UPDATE_STORE_store_store_address,
                data:user_param,
            }
            break;

        case 'is_store_info':
            return{
                type:TYPES.UPDATE_STORE_store_is_store_info,
                data:user_param,
            }
            break;
        case 'store_info':
            return{
                type:TYPES.UPDATE_STORE_store_store_info,
                data:user_param,
            }
            break;

        case 'store_hours':
            return{
                type:TYPES.UPDATE_STORE_store_store_hours,
                data:user_param,
            }
            break;   
            
        case 'lng':
            return{
                type:TYPES.UPDATE_STORE_store_lng,
                data:user_param,
            }
            break;
            
        case 'lat':
            return{
                type:TYPES.UPDATE_STORE_store_lat,
                data:user_param,
            }
            break;

        case 'geohash':
            return{
                type:TYPES.UPDATE_STORE_store_geohash,
                data:user_param,
            }
            break;
        case 'audit_state':
            return{
                type:TYPES.UPDATE_STORE_store_audit_state,
                data:user_param,
            }
            break;

        case 'province':
            return{
                type:TYPES.UPDATE_STORE_store_province,
                data:user_param,
            }
            break;   
            
        case 'city':
            return{
                type:TYPES.UPDATE_STORE_store_city,
                data:user_param,
            }
            break;
            
        case 'district':
            return{
                type:TYPES.UPDATE_STORE_store_district,
                data:user_param,
            }
            break;

        case 'disabled':
            return{
                type:TYPES.UPDATE_STORE_store_disabled,
                data:user_param,
            }
            break;
        case 'store_price':
            return{
                type:TYPES.UPDATE_STORE_store_store_price,
                data:user_param,
            }
            break;

        case 'comment_num':
            return{
                type:TYPES.UPDATE_STORE_store_comment_num,
                data:user_param,
            }
            break;   
            
        case 'store_hot':
            return{
                type:TYPES.UPDATE_STORE_store_store_hot,
                data:user_param,
            }
            break;
            
        case 'store_credit':
            return{
                type:TYPES.UPDATE_STORE_store_credit,
                data:user_param,
            }
            break;

        case 'store_score':
            return{
                type:TYPES.UPDATE_STORE_store_score,
                data:user_param,
            }
            break;

        case 'progress':
            return{
                type:TYPES.UPDATE_STORE_progress,
                data:user_param,
            }
            break;
        default:
    }
}
