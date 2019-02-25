import React, { Component } from 'react';
import {
  View,
  Image,
  Text,
  Platform,
  ToastAndroid
} from 'react-native';
import { connect } from 'react-redux';
import { getLoginStatus } from '../../dataBase/accountStorage';
import { token_login } from '../../network/loginNetApi';
import { handleLoginData } from '../LoginPage/utils/accountHandle';
import JPushModule from 'jpush-react-native'

const receiveCustomMsgEvent = 'receivePushMsg'
const receiveNotificationEvent = 'receiveNotification'
const openNotificationEvent = 'openNotification'
const getRegistrationIdEvent = 'getRegistrationId'

class Welcome extends Component {
  constructor(props) {
    super(props)
    this._tokenLogin = this._tokenLogin.bind(this);
    this._defaultLogin = this._defaultLogin.bind(this);
  }
  static navigationOptions = ({ navigation }) => ({
    header: null
  });
  componentDidMount() {
    JPushModule.removeReceiveCustomMsgListener(receiveCustomMsgEvent)
    JPushModule.removeReceiveNotificationListener(receiveNotificationEvent)
    JPushModule.removeReceiveOpenNotificationListener(openNotificationEvent)
    JPushModule.removeGetRegistrationIdListener(getRegistrationIdEvent)
    //-- 移除别名
    JPushModule.deleteAlias(map => {
      console.log('delete alias succeed', map)
    })
    //-- 清除分组
    JPushModule.cleanTags(success => {
      console.log('delete tags succeed', success)
    })
    JPushModule.clearAllNotifications()
    this._defaultLogin();
  }


  _defaultLogin() {

    getLoginStatus((loginState) => {
      const { code, data } = loginState
      if (code == 1 && data.isLoggedIn) {
        // 已登录---> token 验证
        this._tokenLogin(data);
      } else {
        // 未登录---> 跳转登录界面
        setTimeout(() => {
          this._goToScreen("loginAccount", { transition: 'mySearchTransition' });
        }, 2000);
      }
    })
  }

  _tokenLogin(defaultLoginData) {
    const { mobile, token } = defaultLoginData;
    let formData = {
      mobile,
      token,
    };
    token_login(formData, (responseData) => {
      handleLoginData(this.props.dispatch, responseData);
      const { code } = responseData;
      if (code != 1) {
        // token 验证失败---> 跳转登录界面
        setTimeout(() => {
          this._goToScreen("loginAccount", { transition: 'mySearchTransition' });
        }, 2000);

      } else {
        //-- 跳转home
        setTimeout(() => {
          this._goToScreen("RootTabNav", { transition: 'mySearchTransition' });
        }, 2000);
      }
    })
  }
  _goToScreen = (pageName = "", pageParams = {}) => {
    const { navigate } = this.props.navigation;
    navigate(pageName, pageParams)
  }
  render() {
    return (
      <View>
        <Image source={require('./src/welcome.png')} style={{ width: screenWidth, height: screenHeight }} />
      </View>
    );
  }
}
export default connect()(Welcome);