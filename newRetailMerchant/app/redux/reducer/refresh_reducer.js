import * as TYPES from '../action/types';

export function refresh_reducer (state = initState, action) {
  switch (action.type) {
    case TYPES.REFRESH_DATA:
      return {
        ...state,
        refreshProgress: action.getRefreshData,
      };
      break;

    default:
      return state;
  }
}

const initState = {
  refreshProgress: 0,
};
