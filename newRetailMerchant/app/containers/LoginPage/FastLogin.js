import React, {Component} from 'react';
import {
  StyleSheet,
  View,
  Text,
  TextInput,
  TouchableHighlight,
} from 'react-native';

import {
  defaultContainerStyles,
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';
import Toast from 'react-native-simple-toast';
import TimerButton from '../../components/TimerButton';
import Header from '../../components/Header/Header';
import RegExpTool from '../../utils/RegExpTool';
import {isNumber} from '../../utils/structureJudgment';
import {connect} from 'react-redux';
import {sendMobileCode, quickLogin} from '../../network/loginNetApi';
import {handleLoginData} from './utils/accountHandle';
import {NavigationActions} from 'react-navigation';

class FastLogin extends Component {
  constructor (props) {
    super (props);
    this.state = {
      mobile: '',
      code: '',
      receiveCode: '',
    };

    this._clickLoginButton = this._clickLoginButton.bind (this);
    this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind (this);
    this._loginSuccess = this._loginSuccess.bind (this);
  }
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle="手机号码快捷登录"
      />
    ),
  });

  //请求验证码
  _clickGetPhoneReqButton () {
    let formData = {
      mobile: this.state.mobile,
      code_type: '1',
    };

    sendMobileCode (formData, responseData => {
      const {code, msg} = responseData;
      if (code === 1) {
        let codeData = responseData['data'];
        this.setState ({
          receiveCode: codeData['code'],
        });
      }

      Toast.showWithGravity (msg, 1, Toast.CENTER);
    });
  }

  _loginSuccess () {
    const resetAction = NavigationActions.reset ({
      index: 0,
      actions: [
        NavigationActions.navigate ({
          routeName: 'RootTabNav',
          action: NavigationActions.navigate ({
            routeName: 'Home', // 这个是tabs 里面的任何一个tab
          }),
        }),
      ],
    });
    this.props.navigation.dispatch (resetAction);
  }

  //登录
  _clickLoginButton () {
    let formData = {
      mobile: this.state.mobile,
      code_type: '1',
      code: this.state.code,
    };

    quickLogin (formData, responseData => {
      const {code, data, msg} = responseData;
      handleLoginData (this.props.dispatch, responseData);
      if (code == 1) {
        setTimeout (this._loginSuccess, 300);
      } else {
        Toast.showWithGravity (msg, Toast.SHORT, Toast.CENTER);
      }
    });
  }

  render () {
    return (
      <View style={{flex: 1}}>
        <View style={{height: 20}} />
        <View style={styles.loginContent}>
          <View style={styles.userContent}>
            <View style={styles.labelName}>
              <Text
                style={{fontSize: 14, color: defaultFontColor.main_Font_Color}}
              >
                手机号
              </Text>
            </View>
            <TextInput
              style={styles.inputFile}
              underlineColorAndroid="transparent"
              keyboardType="numeric"
              onChangeText={textValue => {
                this.setState ({mobile: textValue});
              }}
              value={this.state.mobile}
              placeholder="请输入手机号"
              placeholderTextColor={defaultFontColor.default_Font_Color}
              maxLength={20}
            />
          </View>
          <View style={{borderBottomWidth: 1, borderBottomColor: '#eee'}} />
          <View style={styles.userContent}>
            <View style={styles.labelName}>
              <Text
                style={{fontSize: 14, color: defaultFontColor.main_Font_Color}}
              >
                验证码
              </Text>
            </View>
            <TextInput
              style={styles.inputFile}
              underlineColorAndroid="transparent"
              onChangeText={textValue => this.setState ({code: textValue})}
              value={this.state.code}
              placeholder={'请输入验证码'}
              placeholderTextColor={defaultFontColor.default_Font_Color}
              maxLength={20}
            />
            <TimerButton
              style={{
                width: 120,
                height: 40,
                padding: 0,
                margin: 0,
                backgroundColor: '#F63300',
              }}
              textStyle={{color: '#fff'}}
              timerCount={60}
              onClick={shouldStartCountting => {
                let isTelpNum = RegExpTool.phoneByReg (this.state.mobile);
                if (!isTelpNum['check']) {
                  //如果校验失败，不能开始倒计时
                  shouldStartCountting (false);
                  Toast.showWithGravity (isTelpNum['error'], 1, Toast.CENTER);
                } else {
                  shouldStartCountting (true);
                  //请求接口，获取验证码
                  this._clickGetPhoneReqButton ();
                }
              }}
              disableColor="#D2D2D2"
              enable={true} //按钮可点击？
            />
          </View>
          <TouchableHighlight
            activeOpacity={0.5}
            underlayColor={'transparent'}
            style={[
              styles.buttonCommit,
              {
                backgroundColor: isNumber (this.state.receiveCode)
                  ? this.state.receiveCode + '' === this.state.code
                      ? defaultBackgroundColor.search_Background
                      : defaultBackgroundColor.condition_Background
                  : defaultBackgroundColor.condition_Background,
              },
            ]}
            onPress={this._clickLoginButton}
          >
            <Text style={{fontSize: 16, color: '#fff'}}>
              登录
            </Text>
          </TouchableHighlight>
          <View>
            <Text style={{fontSize: 12, color: '#898989', textAlign: 'center'}}>
              未注册的手机号码验证后自动创建新零售账户
            </Text>
          </View>
        </View>
      </View>
    );
  }
}
const styles = StyleSheet.create ({
  loginContent: {
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
    width: 120,
    height: 40,
    paddingHorizontal: 20,
    justifyContent: 'center',
    alignItems: 'flex-start',
  },
  inputFile: {
    flex: 1,
    padding: 0,
    margin: 0,
    borderWidth: 0,
    fontSize: 14,
    color: defaultFontColor.main_Font_Color,
  },
  buttonCommit: {
    width: screenWidth - 30,
    height: 46,
    marginLeft: 10,
    marginRight: 10,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 10,
    borderRadius: 6,
  },
});

function select (store) {
  return {};
}
export default connect () (FastLogin);
