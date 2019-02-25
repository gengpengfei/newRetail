import {Platform, Dimensions} from 'react-native';
import './dataBase/localStorage';
const {width, height} = Dimensions.get ('window');

global.APP_DEBUG = __DEV__;

if (__DEV__) {
  global.global_BASEURL = 'http://139.224.220.33:8099'; //测试ip
} else {
  global.global_BASEURL = 'http://139.224.220.33:8088'; //正式ip
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

global.appId =
  'app_id=2088221309930993&method=alipay.trade.app.pay&format=JSON&charset=utf-8&sign_type=RSA2&version=1.0&return_url=&notify_url=http%3A%2F%2F139.224.220.33%3A8088%2FApi%2Fpay%2FalipayOrderNotify&timestamp=2018-04-28+11%3A15%3A32&sign=&biz_content=%7B%22out_trade_no%22%3A%222018042811153286595%22%2C%22body%22%3A%22subject-%5Cu6d4b%5Cu8bd5%22%2C%22total_fee%22%3A%220.01%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%7D';

global.statusbarHeight = Platform.OS === 'ios' ? (isIphoneX () ? 44 : 20) : 0; //状态栏的高度
global.appBar_Height = Platform.OS === 'ios' ? 44 : 56;
global.header_Height = Platform.select ({
  ios: statusbarHeight + appBar_Height,
  android: statusbarHeight + appBar_Height,
});

global.DebugLog = function () {
  APP_DEBUG ? console.log ('DEBUG--->', arguments) : null;
};
