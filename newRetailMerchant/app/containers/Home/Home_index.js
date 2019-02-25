import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  ScrollView,
  StatusBar,
  Platform,
  Alert,
  BackHandler,
} from 'react-native';
import { connect } from 'react-redux';
import { tabBarIconStyle } from '../../utils/appTheme';
import { orderState } from '../../redux/action/order_action';
import { handleUserInfomation } from '../LoginPage/utils/accountHandle';
import Toast from 'react-native-simple-toast';
import { information } from '../../network/shopNetApi';
const width = Dimensions.get('window').width;
import { userInfoAction } from '../../redux/action/user_action';
import { messageList, editMessage } from '../../network/shopNetApi';
import OperateData from './operateData';
import JPushModule from 'jpush-react-native';
import { NavigationEventPayload } from 'react-navigation';
class Home extends Component {
  constructor(props) {
    super(props);

    this._getData = this._getData.bind(this);
    this.processTitle = this.processTitle.bind(this);
    this._PressNextPage = this._PressNextPage.bind(this);
    this._noStroe = this._noStroe.bind(this);
    this._jpushInit = this._jpushInit.bind(this);
    this._setJpushClick = this._setJpushClick.bind(this);
    this._TabBarOnPress = this._TabBarOnPress.bind(this);
    this.titleArr = [
      {
        title: '输码验证',
        imgUrl: require('./src/shuru.png'),
        nav: 'VerCode',
        num: '',
      },
      { title: '扫码验证', imgUrl: require('./src/sys.png'), nav: 'Scan', num: '' },
      {
        title: '买单收款',
        imgUrl: require('./src/maidan.png'),
        nav: 'MineQRCode',
        num: '',
      },
      {
        title: '订单管理',
        imgUrl: require('./src/dingdan.png'),
        nav: 'Order',
        num: 0,
      },
    ];

    this.process = [
      {
        title: '注册账号',
        imgUrl: require('./src/step0.png'),
        imgUrlC: require('./src/step-0-C.png'),
      },
      {
        title: '认领门店',
        imgUrl: require('./src/step1.png'),
        imgUrlC: require('./src/step-1-C.png'),
      },
      {
        title: '提交资质',
        imgUrl: require('./src/step2.png'),
        imgUrlC: require('./src/step-2-C.png'),
      },
      {
        title: '开通业务',
        imgUrl: require('./src/step3.png'),
        imgUrlC: require('./src/step-3-C.png'),
      },
    ];

    this.o_voucher = [
      { title: '优惠券', imgUrl: require('./src/im_1.png') },
      { title: '代金券', imgUrl: require('./src/im_2.png') },
    ];
    this.state = {
      messageData: null,
      messageName: '',
      isRefresh: 0,
    };
  }

  componentWillMount() {
    BackHandler.addEventListener('hardwareBackPress', this.onBackAndroid);

    //当前路由 跳转离开的时候做个监听
    const didBlurSubscription = this.props.navigation.addListener(
      'didBlur',
      payload => {
        BackHandler.removeEventListener(
          'hardwareBackPress',
          this.onBackAndroid
        );
      }
    );

    //由别的路由进入home路由 开启监听
    const willBlurSubscription = this.props.navigation.addListener(
      'willFocus',
      payload => {
        BackHandler.addEventListener('hardwareBackPress', this.onBackAndroid);
      }
    );
  }

  componentWillReceiveProps(props) {
    if (this.props.refreshProgress == props.refreshProgress) {
    } else {
      this._getData();
    }
  }

  onBackAndroid = () => {
    if (this.lastBackPressed && this.lastBackPressed + 2000 >= Date.now()) {
      //最近2秒内按过back键，可以退出应用。
      BackHandler.exitApp();
      return;
    }
    this.lastBackPressed = Date.now();
    Toast.showWithGravity('再按一次退出应用', 1, Toast.CENTER);
    return true;
  };

  publicTitle(cont, index) {
    return (
      <TouchableOpacity
        onPress={() => {
          if (cont.num !== '') {
            this.props.dispatch(orderState('user_order', cont.num));
          }
          this._PressNextPage(cont.nav, { num: cont.num });
        }}
        key={index}
        style={{ alignItems: 'center' }}
      >
        <Image source={cont.imgUrl} style={{ width: 25, height: 25 }} />
        <Text style={{ fontSize: 12, color: '#fff', marginTop: 10 }}>
          {cont.title}
        </Text>
      </TouchableOpacity>
    );
  }

  /**
   * 跳转函数
   * @param {* 要跳转的页面} pageName 
   * @param {* 要跳转的参数} pageParams 
   * @param {* 如果没有店铺，是否提示} shouldGravity 
   */
  _PressNextPage(pageName = '', pageParams = {}, shouldGravity = true) {
    if (this.props.store_id) {
      this._goToScreen(pageName, pageParams);
    } else {
      this._noStroe(shouldGravity);
    }
  }

  _noStoreShowGravity = (goodId, price) => {
    const showMsgList = [
      '您还未登录，请先登录',
      '请先认领门店！谢谢',
      '请先提交门店资质认证',
      '您的门店正在审核中，请耐心等候',
    ];

    Alert.alert('温馨提示', showMsgList[this.props.storeProgress], [
      {
        text: '确定',
        onPress: () => {
          if (this.props.storeProgress == 1 || this.props.storeProgress == 2) {
            this._PressNextPage('', '', false);
          }
        },
      },
      { text: '取消', onPress: () => { } },
    ]);
  };

  _noStroe(shouldGravity) {
    if (shouldGravity) {
      this._noStoreShowGravity();
      return;
    }

    let pageName, pageParams;

    if (
      this.props.storeProgress == 0 ||
      this.props.storeProgress == 1 ||
      this.props.storeProgress == 2
    ) {
      pageName = 'ConfirmIntelligence';
      pageParams = { callBackData: this._getData };
    } else {
      Toast.showWithGravity('您的门店正在审核中，请耐心等候', 1, Toast.CENTER);
    }

    this._goToScreen(pageName, pageParams);
  }

  _goToScreen = (pageName = '', pageParams = {}) => {
    const { navigate } = this.props.navigation;
    navigate(pageName, pageParams);
  };

  processTitle(item, index) {
    var stateImgPath;
    var imgSize = 18;
    if (index < this.props.storeProgress) {
      stateImgPath = require('./src/step-P.png');
    } else if (this.props.storeProgress === index) {
      imgSize = 20;
      stateImgPath = item.imgUrlC;
    } else {
      stateImgPath = item.imgUrl;
    }

    return (
      <View key={index} style={{ position: 'relative' }}>
        {index == 3
          ? null
          : <View
            style={{
              height: 2,
              width: width / 4,
              backgroundColor: '#F73B0A',
              position: 'absolute',
              top: 8,
            }}
          />}

        <View style={{ alignItems: 'center', marginLeft: '-9%' }}>

          <Image
            style={{ width: imgSize, height: imgSize }}
            source={stateImgPath}
          />
          <View style={{ width: 50 }}>
            <Text
              style={{
                fontSize: 12,
                color: index === 3 ? '#C3C3C3' : '#4A4A4A',
                marginTop: 10,
              }}
            >
              {item.title}
            </Text>
          </View>

        </View>

      </View>
    );
  }

  voucher(cont, index) {
    return (
      <TouchableOpacity
        key={index}
        onPress={() => {
          this._PressNextPage('Coupon', { voucher_type: index });
        }}
      >
        <View
          style={{
            width: (width - width / 10) / 2,
            height: 80,
            backgroundColor: '#fff',
            marginTop: 15,
            justifyContent: 'center',
            borderRadius: 2,
          }}
        >
          <View
            style={{
              width: (width - width / 10) / 2,
              flexDirection: 'row',
              justifyContent: 'space-between',
              alignItems: 'center',
            }}
          >
            <View
              style={{
                width: 24,
                height: 24,
                backgroundColor: '#F6F6F6',
                borderRadius: 12,
                marginLeft: -12,
              }}
            />
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <Image source={cont.imgUrl} style={{ width: 30, height: 32 }} />
              <Text style={{ fontSize: 18, color: '#4a4a4a', marginLeft: 5 }}>
                {cont.title}
              </Text>
            </View>
            <View
              style={{
                width: 24,
                height: 24,
                backgroundColor: '#F6F6F6',
                borderRadius: 12,
                marginRight: -12,
              }}
            />
          </View>
        </View>
      </TouchableOpacity>
    );
  }

  static navigationOptions = ({ navigation }) => ({
    tabBarLabel: '首页',
    tabBarIcon: ({ tintColor }) => (
      <Image
        resizeMode="contain"
        source={require('./src/home.png')}
        style={{
          width: tabBarIconStyle.width - 8,
          height: tabBarIconStyle.height - 8,
          tintColor: tintColor,
        }}
      />
    ),
    header: null,
  });
  componentDidMount() {
    this._getMessageList();
    setTimeout(() => {
      this._jpushInit();
    }, 1000);
  }
  _setJpushClick(content) {
    var info = JSON.parse(content);
    var item = JSON.parse(info.txt);
    let message_data = item.message_data;
    let create_time = item.create_time;
    let time = this._configTime(create_time);
    if (item.message_type == 1) {
      //收款消息
      this._goToScreen('Gathering', {
        id: item.id,
        type: item.message_type,
        dataInfo: message_data,
        dataItem: item,
        nTabBarOnPress: this._TabBarOnPress,
      });
    } else if (item.message_type == 2) {
      //和小消息
      this._goToScreen('WithdrawCrash', {
        id: item.id,
        type: item.message_type,
        dataInfo: message_data,
        nTabBarOnPress: this._TabBarOnPress,
      });
    } else if (item.message_type == 3 || item.message_type == 4) {
      //活动消息
      this._goToScreen('Activity', {
        id: item.id,
        type: item.message_type,
        dataInfo: message_data,
        nTabBarOnPress: this._TabBarOnPress,
      });
    } else if (item.message_type == 0) {
      // 系统消息
      this._goToScreen('SystemMessage', {
        id: item.id,
        type: item.message_type,
        dataInfo: message_data,
        nTabBarOnPress: this._TabBarOnPress,
      });
    } else if (item.message_type == 5) {
      //账单消息
      this._goToScreen('BillFinish', {
        id: item.id,
        type: item.message_type,
        dataInfo: message_data,
        nTabBarOnPress: this._TabBarOnPress,
      });
    }
  }

  _configTime = str => {
    let arr = str.split(' ');
    let arr2 = arr[0].split('-');
    if (new Date().getFullYear() - arr2[0] > 0) {
      return arr[0];
    } else {
      return arr2[1] + '-' + arr2[2];
    }
  };
  _TabBarOnPress() {
    const { navigate } = this.props.navigation;
    navigate('RootTabNav', { transition: 'mySearchTransition' });
  }
  _jpushInit() {
    //-- 收到消息前初始化
    if (Platform.OS === 'android') {
      JPushModule.initPush();
      JPushModule.notifyJSDidLoad(resultCode => {
        if (resultCode === 0) {
        }
      });
      JPushModule.setStyleBasic();
    } else {
      JPushModule.setupPush();
    }
    //-- 接收自定义消息监听
    JPushModule.addReceiveCustomMsgListener(map => {
      this._getMessageList();
      this.setState({
        isRefresh: !this.state.isRefresh,
      });
    });
    //-- 接收推送消息监听
    JPushModule.addReceiveNotificationListener(map => {
      this._getMessageList();
      this.setState({
        isRefresh: !this.state.isRefresh,
      });
    });
    //-- 点击推送事件监听
    JPushModule.addReceiveOpenNotificationListener(map => {
      this._setJpushClick(map.extras);
    });
    //-- 打开应用通知事件
    JPushModule.addGetRegistrationIdListener(registrationId => {
      // console.log('Device register succeed, registrationId ' + registrationId);
    });
    //-- 设置别名
    JPushModule.setAlias('store' + this.props.store_id, map => {
      if (map.errorCode === 0) {
        // console.log('set alias succeed', map);
      } else {
        // console.log('set alias failed, errorCode: ' + map.errorCode);
      }
    });
    // --设置分组名称（按分组推送）
    // JPushModule.setTags(['store' + this.props.store_id], success => {
    //   console.log('tag set success', success);
    // })
  }
  _getMessageList = () => {
    if (this.props.store_id !== null) {
      let formData = {
        store_id: this.props.store_id,
      };

      messageList(formData, responseData => {
        let { msg, code, data } = responseData;
        if (code == 1) {
          let messageList = data['store_message'];
          if (messageList.length > 0) {
            this.setState(
              {
                messageData: messageList[0],
              },
              () => {
                if (messageList[0].message_type == 1) {
                  this.setState({
                    messageName: '收款消息',
                  });
                } else if (messageList[0].message_type == 2) {
                  this.setState({
                    messageName: '核销消息',
                  });
                } else if (
                  messageList[0].message_type == 3 ||
                  messageList[0].message_type == 4
                ) {
                  this.setState({
                    messageName: '活动消息',
                  });
                } else if (messageList[0].message_type == 0) {
                  this.setState({
                    messageName: '系统消息',
                  });
                } else if (messageList[0].message_type == 5) {
                  this.setState({
                    messageName: '账单消息',
                  });
                }
              }
            );
          }
        } else {
          Toast.showWithGravity(msg, 1, Toast.CENTER);
        }
      });
    }
  };

  _getData = () => {
    let formData = {
      admin_id: this.props.admin_id,
    };
    this._getMessageList();
    information(formData, response => {
      handleUserInfomation(this.props.dispatch, response);
    });
  };

  _titleButton = () => {
    return (
      <TouchableOpacity
        style={{
          width: 100,
          height: 30,
          marginTop: 16,
          backgroundColor: '#F73B0A',
          borderRadius: 5,
          alignItems: 'center',
          justifyContent: 'center',
        }}
        onPress={() => {
          this._PressNextPage('', '', false);
        }}
      >
        <Text style={{ fontSize: 14, color: '#fff' }}>
          {this.process[this.props.storeProgress].title}
        </Text>
      </TouchableOpacity>
    );
  };

  shouldComponentUpdate(nextProps, nextState) {
    if (nextProps.storeInfo_storeID != this.props.storeInfo_storeID) {
      return false;
    } else if (nextProps.store_name != this.props.store_name) {
      return false;
    } else if (nextProps.store_address != this.props.store_address) {
      return false;
    } else {
      return true;
    }
  }

  render() {
    return (
      <View style={styles.container}>
        <StatusBar backgroundColor="#E33801" barStyle="default" />
        <ScrollView>
          <View
            style={{
              width: width,
              height: 130,
              paddingTop: 44,
              backgroundColor: '#F73B0A',
              flexDirection: 'row',
              justifyContent: 'space-around',
            }}
          >
            {this.titleArr.map((cont, index) => {
              return this.publicTitle(cont, index);
            })}
          </View>

          {this.props.storeProgress == 4
            ? null
            : <View
              style={{
                width: width - width / 15,
                marginLeft: width / 30,
                backgroundColor: '#fff',
                borderRadius: 5,
                height: 138,
                marginTop: -20,
              }}
            >
              <View style={{ position: 'relative' }}>
                <View
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-around',
                    marginTop: 20,
                  }}
                >
                  {this.process.map((item, index) => {
                    return this.processTitle(item, index);
                  })}
                </View>
              </View>
              <View
                style={{
                  width: width - width / 15,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {this._titleButton()}
                <Text style={{ fontSize: 10, color: '#4a4a4a', marginTop: 10 }}>
                  注册门店，让新零售助您经营
                  </Text>
              </View>
            </View>}

          <View
            style={{
              width: width - width / 15,
              marginLeft: width / 30,
              flexDirection: 'row',
              justifyContent: 'space-between',
            }}
          >
            {this.o_voucher.map((cont, index) => {
              return this.voucher(cont, index);
            })}
          </View>
          {this.state.messageData === null
            ? null
            : <TouchableOpacity
              onPress={() => {
                this._PressNextPage('Message');
              }}
            >
              <View
                style={{
                  width: width - width / 15,
                  marginLeft: width / 30,
                  flexDirection: 'row',
                  backgroundColor: '#fff',
                  marginTop: 15,
                  paddingTop: 10,
                  paddingBottom: 10,
                  alignItems: 'center',
                }}
              >
                <View style={{ width: 40, paddingLeft: 4 }}>
                  <Text style={{ fontSize: 16, color: '#4A90E2' }}>消息通知</Text>
                </View>
                <Text
                  style={{
                    width: width - width / 15 - 100,
                    fontSize: 12,
                    color: '#F63300',
                  }}
                >
                  {this.state.messageName}
                  {' '}
                  <Text style={{ fontSize: 12, color: '#4A4A4A' }}>
                    {this.state.messageData['message_cont']}
                  </Text>
                </Text>
              </View>
              <Text
                style={{
                  width: width - width / 15 - 100,
                  fontSize: 12,
                  color: '#F63300',
                }}
              />
            </TouchableOpacity>}
          <OperateData
            ONavigate={this.props}
            isRefresh={this.state.isRefresh}
          />
        </ScrollView>
      </View>
    );
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F6F6F6',
  },
});

function select(store) {
  return {
    orderIndex: store.orderInfo.orderState,
    store_id: store.userInfo.store_id,
    admin_id: store.userInfo.admin_id,
    storeProgress: store.storeInfo.progress,
    storeInfo_storeID: store.storeInfo.store_id,
    store_name: store.storeInfo.store_name,
    store_address: store.storeInfo.store_address,
    order_index: store.orderInfo.indexState,
    refreshProgress: store.refreshInfo.refreshProgress,
  };
}
export default connect(select)(Home);
