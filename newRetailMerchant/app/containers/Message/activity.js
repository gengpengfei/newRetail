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
import Toast from 'react-native-simple-toast';
import {isObject} from '../../utils/structureJudgment';
import {messageList, editMessage} from '../../network/shopNetApi';

class Message extends Component {
  constructor (props) {
    super (props);
    this.state = {
      activityData: [],
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
        centerTitle="活动报名"
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

  _renderTitleList (titleName, content) {
    return (
      <View
        style={{
          width: screenWidth - 30,
          flexDirection: 'row',
          paddingVertical: 1,
          alignItems: 'center',
        }}
      >
        <View
          style={{
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text
            style={{
              fontFamily: 'PingFangSC-Regular',
              fontSize: 14,
              color: defaultFontColor.main_Font_Color,
              paddingVertical: 5,
            }}
          >
            {titleName}：
          </Text>
        </View>

        <View
          style={{
            flexDirection: 'column',
            flex: 1,
            alignItems: 'flex-start',
            justifyContent: 'center',
          }}
        >
          <Text
            style={{
              fontFamily: 'PingFangSC-Regular',
              fontSize: 14,
              color: content === '报名成功'
                ? defaultFontColor.element_Font_Color
                : defaultFontColor.main_Font_Color,
            }}
          >
            {content}
          </Text>
        </View>
      </View>
    );
  }

  render () {
    let dataInformation = JSON.parse (
      this.props.navigation.state.params.dataInfo
    );
    if (this.props.navigation.state.params.type === 3) {
      let {
        activity_list_name,
        activity_list_desc,
        start_time,
        end_time,
        activity_list_id,
      } = dataInformation;
      return (
        <View style={styles.container}>
          <View
            style={{
              flex: 1,
              width: screenWidth,
              flexDirection: 'column',
              backgroundColor: '#fff',
              paddingVertical: 20,
              paddingHorizontal: 15,
            }}
          >
            {this._renderTitleList ('店铺名称', this.props.store_name)}
            {this._renderTitleList ('活动名称', activity_list_name)}
            {this._renderTitleList ('活动说明', activity_list_desc)}
            <View
              style={{
                width: screenWidth - 30,
                height: 1,
                backgroundColor: defaultSeparateLineColor.light_SeparateLine_Color,
              }}
            />
            {this._renderTitleList ('活动开始时间', start_time)}
            {this._renderTitleList ('活动结束时间', end_time)}
          </View>

          <TouchableOpacity
            activeOpacity={0.5}
            underlayColor={'transparent'}
            style={{
              width: screenWidth,
              height: 40,
              position: 'absolute',
              bottom: 0,
              backgroundColor: '#f63300',
              justifyContent: 'center',
              alignItems: 'center',
            }}
            onPress={() => {
              this.props.navigation.navigate ('ActivityDetail', {
                activity_list_id: activity_list_id,
              });
            }}
          >
            <Text style={{fontSize: 18, color: '#fff'}}>
              参加活动
            </Text>
          </TouchableOpacity>
        </View>
      );
    } else {
      let {
        store_name,
        voucher_name,
        state,
        reason,
        activity_list_name,
        activity_list_desc,
        start_time,
        end_time,
      } = dataInformation;
      let activity_state = state === 1 ? '报名成功' : '报名失败';
      return (
        <View style={styles.container}>
          <View
            style={{
              flex: 1,
              width: screenWidth,
              flexDirection: 'column',
              backgroundColor: '#fff',
              paddingVertical: 20,
              paddingHorizontal: 15,
            }}
          >
            {this._renderTitleList ('店铺名称', store_name)}
            {this._renderTitleList ('活动名称', activity_list_name)}
            {this._renderTitleList ('活动说明', activity_list_desc)}
            {this._renderTitleList ('参选活动优惠券', voucher_name)}
            {this._renderTitleList ('活动开始时间', start_time)}
            {this._renderTitleList ('活动结束时间', end_time)}
            <View
              style={{
                width: screenWidth - 30,
                height: 1,
                backgroundColor: defaultSeparateLineColor.light_SeparateLine_Color,
              }}
            />
            {this._renderTitleList ('报名状态', activity_state)}
            {state == 1 ? null : this._renderTitleList ('拒绝原因', reason)}
          </View>
        </View>
      );
    }
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
    user_name: store.userInfo.user_name,
    storeProgress: store.storeInfo.progress,
    store_name: store.storeInfo.store_name,
  };
}
export default connect (select) (Message);
