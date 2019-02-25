
import * as TYPES from '../action/types';

export function order_reducer(state=initState,action){
    
    switch (action.type) {
        case TYPES.UPDATE_ORDER:
            return{
                ...state,
                orderState:action.getOrderData,
            };
            break;

        case TYPES.UPDATE_INDEX:
            return{
                ...state,
                indexState:action.getIndex,
            };
            break;

        case TYPES.UPDATE_TIME:
            return{
                ...state,
                timeState:action.getTime,
            };
            break;
        default:
            return state;
    }

}


const initState = {
    orderState:null,
    indexState:0,
    timeState:'自定义'
}