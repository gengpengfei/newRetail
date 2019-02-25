import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  SafeAreaView,
  TouchableOpacity,
  FlatList,
  BackHandler,
} from 'react-native';
import {connect} from 'react-redux';
import {
  tabBarIconStyle,
  defaultContainerStyles,
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';
import Header from '../../components/Header/Header';
import TopContent from './components/TopContent';

import Toast from 'react-native-simple-toast';
import {configServerImagePath} from '../../utils/commonMethod';
import {isNotEmptyArray} from '../../utils/structureJudgment';
import {messageList, editMessage} from '../../network/shopNetApi';

class Message extends Component {
  constructor (props) {
    super (props);
    this.state = {
      titleIndex: '业务消息',
      messageList: [1, 2, 3],
      loading: false,
      titleContent: ['业务消息', '系统消息'],
    };
    this._goToScreen = this._goToScreen.bind (this);
    this._getEditMessage = this._getEditMessage.bind (this);
    this._renderTitleList = this._renderTitleList.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.state.params.nTabBarOnPress ();
          navigation.goBack ();
        }}
        centerTitle="收款消息"
      />
    ),
  });

  componentDidMount () {
    this._getEditMessage ();
  }

  _getEditMessage () {
    let formData = {
      id: this.props.navigation.state.params.id,
    };

    editMessage (formData, responseData => {
      let {msg, code, data} = responseData;
      if (code !== 1) {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
  }

  componentWillMount () {
    BackHandler.addEventListener ('hardwareBackPress', this.onBackAndroid);

    //当前路由 跳转离开的时候做个监听
    const didBlurSubscription = this.props.navigation.addListener (
      'didBlur',
      payload => {
        BackHandler.removeEventListener (
          'hardwareBackPress',
          this.onBackAndroid
        );
      }
    );

    //由别的路由进入home路由 开启监听
    const willBlurSubscription = this.props.navigation.addListener (
      'willFocus',
      payload => {
        BackHandler.addEventListener ('hardwareBackPress', this.onBackAndroid);
      }
    );
  }

  onBackAndroid = () => {
    this.props.navigation.state.params.nTabBarOnPress ();
    this.props.navigation.goBack ();
    return true;
  };

  componentWillUnmount () {
    BackHandler.removeEventListener ('hardwareBackPress', this.onBackAndroid);
  }

  _goToScreen (screenName, params) {
    const {navigate} = this.props.navigation;
    if (params) {
      navigate (screenName, params);
    } else {
      navigate (screenName);
    }
  }

  _renderTitleList (titleName, contentName, content) {
    return (
      <View
        style={{
          width: screenWidth - 30,
          flexDirection: 'row',
          paddingVertical: 1,
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <View
          style={{flexDirection: 'column', justifyContent: 'center', width: 60}}
        >
          <Text
            style={{
              fontFamily: 'PingFangSC-Regular',
              fontSize: 14,
              color: defaultFontColor.main_Font_Color,
              paddingVertical: 10,
            }}
          >
            {titleName}
          </Text>
        </View>

        <View
          style={{
            flexDirection: 'column',
            flex: 1,
            alignItems: 'flex-end',
            justifyContent: 'center',
            paddingHorizontal: 10,
          }}
        >
          <Text
            style={{
              fontFamily: 'PingFangSC-Regular',
              fontSize: 14,
              color: defaultFontColor.main_Font_Color,
              paddingVertical: contentName === '' ? 10 : 5,
            }}
          >
            {content}
          </Text>
          {contentName === ''
            ? null
            : <Text
                style={{
                  fontFamily: 'PingFangSC-Regular',
                  fontSize: 14,
                  color: defaultFontColor.main_Font_Color,
                }}
              >
                {contentName}
              </Text>}
        </View>
      </View>
    );
  }

  render () {
    let dataInformation = JSON.parse (
      this.props.navigation.state.params.dataInfo
    );
    const {
      user_name,
      head_img,
      clear_price,
      clear_time,
      clear_desc,
      order_sn,
      pay_type,
      admin_name,
      mobile,
    } = dataInformation;
    return (
      <View style={styles.container}>
        <View
          style={{
            width: screenWidth,
            backgroundColor: '#fff',
            alignItems: 'center',
            justifyContent: 'center',
            borderBottomWidth: 1,
            borderBottomColor: defaultSeparateLineColor.light_SeparateLine_Color,
          }}
        >
          <TopContent
            imgUrl={
              isNotEmptyArray (head_img) ? configServerImagePath (head_img) : ''
            }
            topName={user_name}
            number={clear_price}
            contentName={'收款成功'}
          />
        </View>
        <View
          style={{
            flex: 1,
            width: screenWidth,
            flexDirection: 'column',
            backgroundColor: '#fff',
            paddingHorizontal: 15,
          }}
        >
          {this._renderTitleList ('订单金额', '', clear_price)}
          <View
            style={{
              width: screenWidth - 30,
              height: 1,
              backgroundColor: defaultSeparateLineColor.light_SeparateLine_Color,
            }}
          />
          {/*{*/}
          {/*this._renderTitleList('收款方式', 'WEEE', clear_price)*/}
          {/*}*/}
          {this._renderTitleList ('交易说明', '', clear_desc)}
          {this._renderTitleList (
            '付款方式',
            '',
            pay_type === 0 ? '余额支付' : pay_type === 1 ? '支付宝支付' : '微信支付'
          )}
          <View
            style={{
              width: screenWidth - 30,
              height: 1,
              backgroundColor: defaultSeparateLineColor.light_SeparateLine_Color,
            }}
          />
          {this._renderTitleList ('创建时间', '', clear_time)}
          {this._renderTitleList ('订单编码', '', order_sn)}

        </View>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: defaultBackgroundColor.page_Background_Color,
    justifyContent: 'center',
    alignItems: 'center',
  },

  itemList: {
    flex: 1,
    flexDirection: 'row',
    height: 78,
    paddingBottom: 10,
    paddingTop: 20,
    alignItems: 'center',
    justifyContent: 'space-between',
    marginHorizontal: 10,
    // borderBottomWidth: 1,
    // borderBottomColor:defaultSeparateLineColor.light_SeparateLine_Color
  },
});

function select (store) {
  return {};
}
export default connect (select) (Message);
