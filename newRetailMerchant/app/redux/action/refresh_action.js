'use strict';

import * as TYPES from './types';

export function refreshData (user_type, data) {
  switch (user_type) {
    case 'refresh_data':
      return {
        type: TYPES.REFRESH_DATA,
        getRefreshData: data,
      };
      break;

    default:
  }
}
