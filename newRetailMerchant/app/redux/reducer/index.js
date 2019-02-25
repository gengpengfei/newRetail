import {combineReducers} from 'redux';
import {userInfo_reducer} from './userInfo_reducer';
import {other_reducer} from './other_reducer';
import {order_reducer} from './order_reducer';
import {storeInfo_reducer} from './storeInfo_reducer';
import {refresh_reducer} from './refresh_reducer';

export default combineReducers ({
  userInfo: userInfo_reducer,
  orderInfo: order_reducer,
  other: other_reducer,
  storeInfo: storeInfo_reducer,
  refreshInfo: refresh_reducer,
});
