import * as TYPES from '../action/types';

export function other_reducer(state=initState,action){

    switch (action.type) {

        case TYPES.UPDATE_ROOTKEY:
            return{
                ...state,
                rootKey:action.data,
            }
            break;

        default:
            return state;
    }
}

const initState = {
    rootKey:'rootKey',
}