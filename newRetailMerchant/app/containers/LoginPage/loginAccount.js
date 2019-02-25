import React, {Component} from 'react';
import {
  Platform,
  StyleSheet,
  View,
  Text,
  TextInput,
  TouchableHighlight,
  TouchableOpacity,
  Image,
  ImageBackground,
  Dimensions,
  ScrollView,
  SafeAreaView,
  BackHandler,
  PixelRatio,
  ToastAndroid,
} from 'react-native';
import {NavigationActions} from 'react-navigation';
import {connect} from 'react-redux';
import Button from '../../components/Button';
import {
  defaultContainerStyles,
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';
import RegExpTool from '../../utils/RegExpTool';
import {isNotEmptyString} from '../../utils/structureJudgment';
import {accountLogin} from '../../network/loginNetApi';
import {handleLoginData} from './utils/accountHandle';
import Toast from 'react-native-simple-toast';

import JPushModule from 'jpush-react-native';

const receiveCustomMsgEvent = 'receivePushMsg';
const receiveNotificationEvent = 'receiveNotification';
const openNotificationEvent = 'openNotification';
const getRegistrationIdEvent = 'getRegistrationId';

class loginAccount extends Component {
  constructor (props) {
    super (props);
    this.state = {
      mobile: '',
      password: '',
      isLoading: false,
    };
    this._loginSuccess = this._loginSuccess.bind (this);
    this._clickLoginButton = this._clickLoginButton.bind (this);
    this._gotoScreen = this._gotoScreen.bind (this);
    this._loginBtnIsActive = this._loginBtnIsActive.bind (this);
  }
  static navigationOptions = ({navigation}) => ({
    header: null,
  });
  componentDidMount () {
    JPushModule.removeReceiveCustomMsgListener (receiveCustomMsgEvent);
    JPushModule.removeReceiveNotificationListener (receiveNotificationEvent);
    JPushModule.removeReceiveOpenNotificationListener (openNotificationEvent);
    JPushModule.removeGetRegistrationIdListener (getRegistrationIdEvent);
    //-- 移除别名
    JPushModule.deleteAlias (map => {
      // console.log('delete alias succeed', map);
    });
    //-- 清除分组
    JPushModule.cleanTags (success => {
      // console.log('delete tags succeed', success);
    });
    JPushModule.clearAllNotifications ();
  }
  componentWillMount () {
    BackHandler.addEventListener ('hardwareBackPress', this.onBackAndroid);
  }
  onBackAndroid = () => {
    if (this.lastBackPressed && this.lastBackPressed + 2000 >= Date.now ()) {
      //最近2秒内按过back键，可以退出应用。
      BackHandler.exitApp ();
      return;
    }
    this.lastBackPressed = Date.now ();
    ToastAndroid.show ('再按一次退出应用', ToastAndroid.SHORT);
    return true;
  };
  _clickLoginButton () {
    if (!this.state.mobile) {
      //如果校验失败，按钮不可点击
      Toast.showWithGravity ('请填写账号', Toast.SHORT, Toast.CENTER);
      return;
    }

    this.setState ({
      isLoading: true,
    });
    let formData = {
      mobile: this.state.mobile,
      password: this.state.password,
    };

    accountLogin (formData, responseData => {
      const {code, data, msg} = responseData;

      handleLoginData (this.props.dispatch, responseData);
      if (code == 1) {
        this._loginSuccess ();
      } else {
        Toast.showWithGravity (msg, Toast.SHORT, Toast.CENTER);
      }
      this.setState ({
        isLoading: false,
      });
    });
  }

  _loginSuccess () {
    BackHandler.removeEventListener ('hardwareBackPress', this.onBackAndroid);
    this.props.navigation.navigate ('RootTabNav', {
      transition: 'mySearchTransition',
    });
  }
  _gotoScreen (screenName, params) {
    BackHandler.removeEventListener ('hardwareBackPress', this.onBackAndroid);
    const {navigate} = this.props.navigation;
    navigate (screenName, params);
  }
  _loginBtnIsActive () {
    let isTelpNum = RegExpTool.phoneByReg (this.state.mobile);
    return isNotEmptyString (this.state.password) && isTelpNum['check'];
  }
  render () {
    return (
      <ImageBackground
        resizeMode="cover"
        source={require ('./src/Bitmap.png')}
        style={{width: screenWidth, height: screenHeight}}
      >
        <View style={styles.statusBar} />
        <SafeAreaView
          style={{
            width: screenWidth,
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <ScrollView
            keyboardShouldPersistTaps="handled"
            scrollEnabled={false} //防止滑动
            contentContainerStyle={{flex: 1}}
          >

            <View
              style={{
                height: PixelRatio.get () > 3
                  ? screenWidth / 3 + 40
                  : Platform.OS === 'ios'
                      ? screenWidth / 3 + 40
                      : screenWidth / 3 + 40,
              }}
            />
            <View style={styles.loginContent}>
              <View style={styles.userContent}>
                <TextInput
                  style={styles.inputFile}
                  underlineColorAndroid="transparent"
                  keyboardType="numeric"
                  onChangeText={textValue => {
                    this.setState ({mobile: textValue});
                  }}
                  onBlur={() => {
                    if (this.state.mobile) {
                      //如果校验失败，按钮不可点击
                      let isTelpNum = RegExpTool.phoneByReg (this.state.mobile);
                      if (!isTelpNum['check']) {
                        //如果校验失败，按钮不可点击
                        Toast.showWithGravity (
                          '请填写正确的账号',
                          Toast.SHORT,
                          Toast.CENTER
                        );
                      }
                    }
                  }}
                  value={this.state.mobile}
                  placeholder="请输入手机号"
                  placeholderTextColor={defaultFontColor.prompt_Font_Color}
                  maxLength={20}
                />
              </View>
              <View
                style={{
                  borderBottomWidth: 1,
                  borderBottomColor: defaultContainerStyles.main_SeprateLine_Color,
                }}
              />
              <View style={styles.userContent}>
                <TextInput
                  style={styles.inputFile}
                  underlineColorAndroid="transparent"
                  onChangeText={password => {
                    this.setState ({password});
                  }}
                  value={this.state.password}
                  placeholder={'请输入登录密码'}
                  secureTextEntry={true}
                  placeholderTextColor={defaultFontColor.prompt_Font_Color}
                  maxLength={20}
                />
              </View>
              <Button
                style={{
                  height: 46,
                  alignSelf: 'flex-end',
                  justifyContent: 'center',
                  backgroundColor: 'transparent',
                }}
                onPress={() => {
                  this._gotoScreen ('ForgetPassword', {isMineSet: false});
                }}
              >
                <Text
                  style={{
                    width: 80,
                    fontSize: 12,
                    color: defaultFontColor.default_Font_Color,
                    textAlign: 'right',
                  }}
                >
                  忘记密码？
                </Text>
              </Button>
              <Button
                isLoading={this.state.isLoading}
                isLoadStr="登录中..."
                isDisabled={!this._loginBtnIsActive ()}
                style={[
                  styles.buttonCommit,
                  {backgroundColor: defaultBackgroundColor.search_Background},
                ]}
                onPress={this._clickLoginButton}
              >
                <Text
                  style={{
                    fontSize: 18,
                    color: defaultFontColor.background_Font_Color,
                  }}
                >
                  登录
                </Text>
              </Button>
              <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'center',
                  paddingVertical: 18,
                }}
              >
                <View
                  style={{
                    width: 80,
                    height: 1,
                    backgroundColor: defaultSeparateLineColor.dark_SeparateLine_Color,
                  }}
                />
                <View
                  style={{
                    width: 3,
                    height: 3,
                    borderRadius: 3,
                    backgroundColor: defaultSeparateLineColor.dark_SeparateLine_Color,
                  }}
                />
                <Text
                  style={{
                    fontSize: 12,
                    color: defaultFontColor.default_Font_Color,
                    textAlign: 'center',
                    paddingHorizontal: 7,
                  }}
                >
                  或
                </Text>
                <View
                  style={{
                    width: 3,
                    height: 3,
                    borderRadius: 3,
                    backgroundColor: defaultSeparateLineColor.dark_SeparateLine_Color,
                  }}
                />
                <View
                  style={{
                    width: 80,
                    height: 1,
                    backgroundColor: defaultSeparateLineColor.dark_SeparateLine_Color,
                  }}
                />
              </View>
              <Button
                // activeOpacity = { 0.5 }
                // underlayColor = {'transparent'}
                style={styles.fastLoginCommit}
                onPress={() => {
                  this._gotoScreen ('FastLogin', {
                    gobackKey: this.props.navigation.state.key,
                  });
                }}
              >
                <Text
                  style={{
                    fontSize: 16,
                    color: defaultFontColor.element_Font_Color,
                  }}
                >
                  手机号码快速登录
                </Text>
              </Button>
              <View style={{marginTop: Platform.OS === 'ios' ? 85 : 45}} />
              <Button
                // activeOpacity = { 0.5 }
                //  underlayColor = {'transparent'}
                style={{width: screenWidth - 30, height: 46}}
                onPress={() => {
                  this._gotoScreen ('Regist', {
                    gobackKey: this.props.navigation.state.key,
                  });
                }}
              >
                <View style={{backgroundColor: 'transparent'}}>
                  <Text style={{fontSize: 17, color: 'rgba(246,51,0,1)'}}>
                    注册
                  </Text>
                </View>
              </Button>

            </View>
          </ScrollView>
        </SafeAreaView>
      </ImageBackground>
    );
  }
}
const styles = StyleSheet.create ({
  container: {
    height: appBar_Height,
    width: screenWidth,
  },
  statusBar: {
    height: statusbarHeight,
  },
  appBar: {
    height: appBar_Height,
    flexDirection: 'row',
  },
  appBarCenter: {
    flex: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },

  loginContent: {
    //height: 40,
    paddingHorizontal: 15,
    paddingTop: 20,
    alignItems: 'center',
  },
  userContent: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    height: 40,
    backgroundColor: 'white',
  },
  labelName: {
    width: 100,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
  },
  inputFile: {
    flex: 1,
    padding: 0,
    paddingLeft: 15,
    margin: 0,
    borderWidth: 0,
    fontSize: 14,
    color: defaultFontColor.main_Font_Color,
  },
  fastLoginCommit: {
    width: screenWidth / 2,
    height: 40,
    marginVertical: 10,
    alignSelf: 'center',
    justifyContent: 'center',
    borderColor: defaultFontColor.element_Font_Color,
    borderWidth: 1,
    borderRadius: 6,
  },
  buttonCommit: {
    width: screenWidth - 30,
    height: 46,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 6,
  },
});

function select (store) {
  return {
    username: store.userInfo.user_name,
  };
}

export default connect (select) (loginAccount);
