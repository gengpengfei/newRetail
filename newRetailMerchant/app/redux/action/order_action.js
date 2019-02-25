
'use strict'

import * as TYPES from './types';

export function orderState(user_type,data){
    switch (user_type) {
        case 'user_order':
            return{
                type:TYPES.UPDATE_ORDER,
                getOrderData:data,
            }
            break;
        case 'order_index':
            return{
                type:TYPES.UPDATE_INDEX,
                getIndex:data,
            }
            break;
        case 'time_state':
            return{
                type:TYPES.UPDATE_TIME,
                getTime:data,
            }
            break;
        default:
    }         
}
