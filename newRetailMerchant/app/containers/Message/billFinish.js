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
      titleIndex: '业务消息',
      messageList: [1, 2, 3],
      loading: false,
      titleContent: ['业务消息', '系统消息'],
      create_time: '',
      pay_price: '',
      clear_start_time: '',
      clear_end_time: '',
      pay_end_time: '',
    };

    // this._goToScreen = this._goToScreen.bind(this);
    // this._getDataInfo = this._getDataInfo.bind(this);
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
        centerTitle="账单消息"
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

  _getDataInfo () {
    let dataInformation = JSON.parse (
      this.props.navigation.state.params.dataInfo
    );

    this.setState ({
      create_time: dataInformation['create_time'],
      pay_price: dataInformation['pay_price'],
      clear_start_time: dataInformation['clear_start_time'],
      clear_end_time: dataInformation['clear_end_time'],
      pay_end_time: dataInformation['pay_end_time'],
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
          style={{
            flexDirection: 'column',
            justifyContent: 'center',
            width: 120,
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

  render () {
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
              isNotEmptyArray (this.props.head_img)
                ? configServerImagePath (this.props.head_img)
                : ''
            }
            topName={this.props.user_name}
            number={this.state.pay_price}
            contentName={'账单生成'}
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
          {/* {
                        this._renderTitleList('账单创建时间', this.state.create_time)
                    } */}
          {this._renderTitleList (
            '账单开始时间',
            this.state.clear_start_time.substr (0, 10)
          )}
          {this._renderTitleList (
            '账单结束时间',
            this.state.clear_end_time.substr (0, 10)
          )}
          {this._renderTitleList (
            '最迟打款时间',
            this.state.pay_end_time.substr (0, 10)
          )}
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
    store_name: store.storeInfo.store_name,
  };
}
export default connect (select) (Message);
