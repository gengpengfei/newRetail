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
import {isNotEmptyArray} from '../../utils/structureJudgment';
import Toast from 'react-native-simple-toast';
import {configServerImagePath} from '../../utils/commonMethod';
import {messageList, editMessage} from '../../network/shopNetApi';

class Message extends Component {
  constructor (props) {
    super (props);
    this.state = {
      user_name: '',
      head_img: '',
      clear_price: '',
      clear_time: '',
      clear_desc: '',
      order_sn: '',
    };

    // this._goToScreen = this._goToScreen.bind(this);
    this._getDataInfo = this._getDataInfo.bind (this);
    this._renderTitleList = this._renderTitleList.bind (this);
    this._getEditMessage = this._getEditMessage.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.state.params.nTabBarOnPress ();
          navigation.goBack ();
        }}
        centerTitle="核销消息"
      />
    ),
  });

  componentDidMount () {
    this._getDataInfo ();
    this._getEditMessage ();
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

  _getDataInfo () {
    let dataInformation = JSON.parse (
      this.props.navigation.state.params.dataInfo
    );
    // this.setState({
    //     create_time: dataInformation['create_time'],
    //     pay_price: dataInformation['pay_price'],
    //     clear_start_time: dataInformation['clear_start_time'],
    //     clear_end_time: dataInformation['clear_end_time'],
    //     pay_end_time: dataInformation['pay_end_time'],
    // })

    let {
      user_name,
      head_img,
      clear_price,
      clear_time,
      clear_desc,
      order_sn,
    } = dataInformation;
    this.setState ({
      user_name: user_name,
      head_img: head_img,
      clear_price: clear_price,
      clear_time: clear_time,
      clear_desc: clear_desc,
      order_sn: order_sn,
    });
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

  _goToScreen (screenName, params) {
    const {navigate} = this.props.navigation;
    if (params) {
      navigate (screenName, params);
    } else {
      navigate (screenName);
    }
  }

  _renderTitleList (titleName, content) {
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
              paddingVertical: 10,
            }}
          >
            {content}
          </Text>
        </View>
      </View>
    );
  }
  _renderProgress (titleName) {
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
          {[
            ['处理进度', '2012-23-32 00:34:12'],
            ['处理进度', '2012-23-32 00:34:12'],
            ['处理进度', ''],
          ].map ((cont, index) => {
            return (
              <View
                key={index}
                style={{
                  position: 'relative',
                  flexDirection: 'row',
                  height: 40,
                  alignItems: 'flex-start',
                  justifyContent: 'space-between',
                  paddingLeft: 60,
                }}
              >
                <View
                  style={{
                    flexDirection: 'column',
                    width: 12,
                    height: 40,
                    paddingHorizontal: 5,
                    alignItems: 'center',
                  }}
                >
                  {index === 0
                    ? <View
                        style={{
                          width: 2,
                          height: 4,
                          borderWidth: 1,
                          borderColor: '#fff',
                          backgroundColor: '#fff',
                        }}
                      />
                    : <View
                        style={{
                          width: 2,
                          height: 4,
                          borderWidth: 1,
                          borderColor: cont[1] === ''
                            ? defaultFontColor.prompt_Font_Color
                            : defaultFontColor.element_Font_Color,
                          backgroundColor: cont[1] === ''
                            ? defaultFontColor.prompt_Font_Color
                            : defaultFontColor.element_Font_Color,
                        }}
                      />}
                  <Image
                    resizeMode="cover"
                    style={{width: 12, height: 12}}
                    source={
                      cont[1] === ''
                        ? require ('./src/notChoose.png')
                        : require ('./src/choose.png')
                    }
                  />
                  {index === 2
                    ? null
                    : <View
                        style={{
                          width: 2,
                          height: 24,
                          borderWidth: 1,
                          borderColor: cont[1] === ''
                            ? defaultFontColor.prompt_Font_Color
                            : defaultFontColor.element_Font_Color,
                          backgroundColor: cont[1] === ''
                            ? defaultFontColor.prompt_Font_Color
                            : defaultFontColor.element_Font_Color,
                        }}
                      />}
                </View>
                <View
                  style={{
                    flexDirection: 'column',
                    width: 80,
                    alignItems: 'center',
                    justifyContent: 'flex-start',
                  }}
                >
                  <Text
                    style={{
                      fontFamily: 'PingFangSC-Regular',
                      fontSize: 14,
                      color: cont[1] === ''
                        ? defaultFontColor.prompt_Font_Color
                        : defaultFontColor.element_Font_Color,
                    }}
                  >
                    {cont[0]}
                  </Text>
                </View>
                <View
                  style={{
                    flexDirection: 'column',
                    flex: 1,
                    alignItems: 'flex-end',
                    justifyContent: 'flex-start',
                  }}
                >
                  <Text
                    style={{
                      fontFamily: 'PingFangSC-Regular',
                      fontSize: 14,
                      color: defaultFontColor.prompt_Font_Color,
                    }}
                  >
                    {cont[1]}
                  </Text>
                </View>
              </View>
            );
          })}
        </View>
      </View>
    );
  }

  render () {
    let dataInformation = JSON.parse (
      this.props.navigation.state.params.dataInfo
    );
    let {
      user_name,
      head_img,
      clear_price,
      clear_time,
      clear_desc,
      order_sn,
    } = dataInformation;
    return (
      <View style={styles.container}>
        <View
          style={{
            width: screenWidth,
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
            contentName={'核销成功'}
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
          {this._renderTitleList ('订单金额', clear_price)}
          {this._renderTitleList ('交易说明', clear_desc)}
          <View
            style={{
              width: screenWidth - 30,
              height: 1,
              backgroundColor: defaultSeparateLineColor.light_SeparateLine_Color,
            }}
          />
          {this._renderTitleList ('创建时间', clear_time)}
          {this._renderTitleList ('订单编码', order_sn)}

        </View>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: 'white',
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
    borderBottomWidth: 1,
    borderBottomColor: defaultSeparateLineColor.light_SeparateLine_Color,
  },
});

function select (store) {
  return {
    store_id: store.userInfo.store_id,
    admin_id: store.userInfo.admin_id,
    head_img: store.userInfo.head_img,
    user_name: store.userInfo.user_name,

    storeProgress: store.storeInfo.progress,
    storeInfo_storeID: store.storeInfo.head_img,
    store_name: store.storeInfo.store_name,
    store_address: store.storeInfo.store_address,
  };
}
export default connect (select) (Message);
