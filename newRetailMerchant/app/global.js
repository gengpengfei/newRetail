import {Platform, Dimensions} from 'react-native';
import './dataBase/localStorage';
const {width, height} = Dimensions.get ('window');

global.APP_DEBUG = __DEV__;

if (__DEV__) {
  global.global_BASEURL = ''; //测试ip
} else {
  global.global_BASEURL = ''; //正式ip
}

global.isIOS = Platform.OS === 'ios';
global.screenWidth = width;
global.screenHeight = height;
global.USER_UUID = null; //用户id

// iPhoneX
const X_WIDTH = 375;
const X_HEIGHT = 812;

function isIphoneX () {
  if (
    Platform.OS === 'ios' &&
    ((height === X_HEIGHT && width === X_WIDTH) ||
      (height === X_WIDTH && width === X_HEIGHT))
  ) {
    return true;
  }
}

global.appId = ''
global.statusbarHeight = Platform.OS === 'ios' ? (isIphoneX () ? 44 : 20) : 0; //状态栏的高度
global.appBar_Height = Platform.OS === 'ios' ? 44 : 56;
global.header_Height = Platform.select ({
  ios: statusbarHeight + appBar_Height,
  android: statusbarHeight + appBar_Height,
});

global.DebugLog = function () {
  APP_DEBUG ? console.log ('DEBUG--->', arguments) : null;
};
