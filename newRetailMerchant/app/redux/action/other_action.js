
'use strict'

import * as TYPES from '../action/types'

export function update_rootKey(key) {
    return{
        type:TYPES.UPDATE_ROOTKEY,
        data:key,
    }

}