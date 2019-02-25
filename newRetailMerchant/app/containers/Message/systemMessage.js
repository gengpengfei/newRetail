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
class SystemMessage extends Component {
  constructor (props) {
    super (props);
    this.state = {
      titleIndex: '业务消息',
      messageList: [1, 2, 3],
      loading: false,
      titleContent: ['业务消息', '系统消息'],
    };
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
        centerTitle="系统消息"
      />
    ),
  });

  componentDidMount () {
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
          width: screenWidth,
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
          borderTopWidth: 1,
          borderTopColor: defaultSeparateLineColor.light_SeparateLine_Color,
        }}
      >
        <View
          style={{
            flexDirection: 'column',
            justifyContent: 'center',
            paddingLeft: 10,
          }}
        >
          <Text
            style={{
              fontFamily: 'PingFangSC-Regular',
              fontSize: 14,
              color: defaultFontColor.main_Font_Color,
            }}
          >
            {titleName}
          </Text>
        </View>

        <View
          style={{
            flexDirection: 'column',
            flex: 1,
            alignItems: titleName ? 'flex-end' : 'flex-start',
            justifyContent: 'center',
            paddingRight: 10,
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
    let {data, title, create_time} = dataInformation;
    return (
      <View style={styles.container}>
        <View
          style={{
            width: screenWidth,
            backgroundColor: '#fff',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <TopContent
            imgUrl={
              isNotEmptyArray (this.props.head_img)
                ? configServerImagePath (this.props.head_img)
                : ''
            }
            topName={this.props.user_name}
            number=""
            contentName=""
          />
        </View>
        <View
          style={{
            flex: 1,
            width: screenWidth,
            flexDirection: 'column',
            backgroundColor: '#fff',
          }}
        >
          {this._renderTitleList ('主题', '', title)}
          {this._renderTitleList ('推送内容', '', '')}
          {this._renderTitleList ('', '', data)}
          {this._renderTitleList ('创建时间', '', create_time)}
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
export default connect (select) (SystemMessage);
