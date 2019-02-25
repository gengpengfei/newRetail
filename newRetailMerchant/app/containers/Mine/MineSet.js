import React, {Component} from 'react';
import {
  StyleSheet,
  View,
  Text,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import Header from '../../components/Header/Header';
import {connect} from 'react-redux';
import {defaultFontColor, defaultBackgroundColor} from '../../utils/appTheme';
import {SetSection} from './components/Section';
import {storageLoginStatus} from '../../dataBase/accountStorage';
import {isNotEmptyString} from '../../utils/structureJudgment';
import {handleLoginData} from '../LoginPage/utils/accountHandle';
class MineSet extends Component {
  constructor (props) {
    super (props);
    this._clickItem = this._clickItem.bind (this);
    this._exitLogin = this._exitLogin.bind (this);
  }
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle="设置"
      />
    ),
  });

  _exitLogin () {
    storageLoginStatus ('  ', '  ', false);
    let response = {
      code: 1,
      msg: '登陆成功',
      data: {
        admin_id: null,
        head_img: [],
        is_boss: null,
        last_ip: null,
        last_login_time: null,
        login_count: null,
        mobile: null,
        pay_password: null,
        store_id: null,
        token: null,
        user_name: null,
      },
    };

    handleLoginData (this.props.dispatch, response);
    const {navigate} = this.props.navigation;
    navigate ('loginAccount');
  }

  _clickItem (str) {
    const {navigate} = this.props.navigation;
    if (str === '支付密码') {
      navigate ('PayPassword');
    } else if (str === '登录密码') {
      navigate ('ForgetPassword', {isMineSet: true});
    } else if (str === '换绑手机') {
      navigate ('ModifiyMobile');
    }
  }

  render () {
    var str = this.props.mobile;
    if (isNotEmptyString (str)) {
      var placeholderStr = str.substr (0, 3) + '****' + str.substr (7);
    }

    return (
      <View style={styles.container}>
        <ScrollView style={{flex: 1}}>

          <View style={{width: screenWidth, height: 10}} />
          <SetSection
            clickItem={this._clickItem}
            rightImgPath={require ('./src/rightJ.png')}
            titleStr="登录密码"
            rightStr={'修改'}
          />
          <SetSection
            clickItem={this._clickItem}
            rightImgPath={require ('./src/rightJ.png')}
            titleStr="换绑手机"
            rightStr={placeholderStr}
          />

          {/* <SetSection 
                        clickItem={this._clickItem}
                        rightImgPath = {require("./src/rightJ.png")}
                        titleStr="支付密码"
                        rightStr={'未设置'} 
                        rightStrColor="#F63300"
                          
                    /> */}

        </ScrollView>

        <TouchableOpacity
          activeOpacity={0.5}
          underlayColor={'transparent'}
          style={styles.buttonCommit}
          onPress={this._exitLogin}
        >

          <Text
            style={{
              fontSize: 18,
              color: defaultFontColor.background_Font_Color,
            }}
          >
            退出账户
          </Text>
        </TouchableOpacity>
        <View style={{width: screenWidth, height: 10}} />

      </View>
    );
  }
}
const styles = StyleSheet.create ({
  container: {
    flex: 1,
    // flexDirection: 'column',
    alignItems: 'center',
  },
  buttonCommit: {
    width: screenWidth - 30,
    height: 46,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 6,
    backgroundColor: defaultBackgroundColor.search_Background,
  },
});

function select (store) {
  return {
    mobile: store.userInfo.mobile,
  };
}

export default connect (select) (MineSet);
