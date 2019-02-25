import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  FlatList,
  ImageBackground,
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
import {configServerImagePath} from '../../utils/commonMethod';
import {messageList, editMessage} from '../../network/shopNetApi';
import systemMessage from './systemMessage';

class Message extends Component {
  constructor (props) {
    super (props);
    this.state = {
      titleIndex: '业务消息',
      messageList: [],
      systemMessageList: [],
      loading: false,
      titleContent: ['业务消息', '系统消息'],
    };

    this._titlePublic = this._titlePublic.bind (this);
    this._renderList = this._renderList.bind (this);
    this._createEmptyView = this._createEmptyView.bind (this);
    this._goToScreen = this._goToScreen.bind (this);
    this._getMessageList = this._getMessageList.bind (this);
    this._TabBarOnPress = this._TabBarOnPress.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    tabBarLabel: '消息',
    tabBarIcon: ({tintColor}) => (
      <Image
        resizeMode="contain"
        source={require ('./src/messege.png')}
        style={{
          width: tabBarIconStyle.width - 8,
          height: tabBarIconStyle.height - 8,
          tintColor: tintColor,
        }}
      />
    ),
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle="消息"
      />
    ),

    tabBarOnPress: (scene, jumpToIndex) => {
      if (navigation.state.params && navigation.state.params.nTabBarOnPress) {
        navigation.state.params.nTabBarOnPress ();
      }
      scene.jumpToIndex (scene.scene.index);
    },
  });

  componentDidMount () {
    this._getMessageList ();
    this.props.navigation.setParams ({nTabBarOnPress: this._TabBarOnPress});
  }
  _TabBarOnPress () {
    this._getMessageList ();
  }

  _getMessageList () {
    let formData = {
      store_id: this.props.store_id,
    };

    messageList (formData, responseData => {
      let {msg, code, data} = responseData;
      if (code === 1) {
        this.setState ({
          messageList: data['store_message'],
          systemMessageList: data['system_message'],
        });
      } else {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
  }

  _goToScreen (screenName, params, index) {
    const {navigate} = this.props.navigation;
    this.state.messageList[index].message_state = 1;
    this.setState ({
      messageList: this.state.messageList,
    });
    if (params) {
      navigate (screenName, params);
    } else {
      navigate (screenName);
    }
  }

  _titlePublic (cont, index) {
    return (
      <TouchableOpacity
        onPress={() => {
          if (this.state.titleIndex !== cont) {
            this.setState ({
              titleIndex: cont,
            });
          }
        }}
        key={index}
        style={{
          width: screenWidth / 2,
          height: 44,

          justifyContent: 'center',
          alignItems: 'center',
          backgroundColor: '#fff',
          borderBottomWidth: 3,
          borderBottomColor: this.state.titleIndex !== cont
            ? '#fff'
            : defaultFontColor.element_Font_Color,
        }}
      >
        <Text
          style={{
            fontSize: 14,
            fontFamily: 'PingFangSC-Medium',
            color: this.state.titleIndex !== cont
              ? defaultFontColor.main_Font_Color
              : defaultFontColor.element_Font_Color,
          }}
        >
          {cont}
        </Text>
      </TouchableOpacity>
    );
  }

  _createEmptyView = () => {
    return (
      <View
        style={{
          flex: 1,
          flexDirection: 'column',
          height: screenWidth,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Text style={{fontSize: 20, color: defaultFontColor.prompt_Font_Color}}>
          暂无数据
        </Text>
      </View>
    );
  };

  _configTime = str => {
    let arr = str.split (' ');
    let arr2 = arr[0].split ('-');
    if (new Date ().getFullYear () - arr2[0] > 0) {
      return arr[0];
    } else {
      return arr2[1] + '-' + arr2[2];
    }
  };

  _renderItemList = (item, index) => {
    let itemTitle, imgIcon;
    //1:收款消息 2: 核销消息 3:活动消息
    if (item.message_type == 1) {
      (itemTitle = '收款消息'), (imgIcon = require ('./src/gathering1.png'));
    } else if (item.message_type == 2) {
      itemTitle = '核销消息';
      imgIcon = require ('./src/withdrawCrash1.png');
    } else if (item.message_type == 3 || item.message_type == 4) {
      itemTitle = '活动消息';
      imgIcon = require ('./src/activity1.png');
    } else if (item.message_type == 0) {
      itemTitle = '系统消息';
      imgIcon = require ('./src/systemMessage1.png');
    } else if (item.message_type == 5) {
      itemTitle = '账单消息';
      imgIcon = require ('./src/billfinish1.png');
    }
    let message_data = item.message_data;
    let create_time = item.create_time;
    let time = this._configTime (create_time);
    return (
      <TouchableOpacity
        onPress={() => {
          if (item.message_type == 1) {
            //收款消息
            this._goToScreen (
              'Gathering',
              {
                id: item.id,
                type: item.message_type,
                dataInfo: message_data,
                dataItem: item,
                nTabBarOnPress: this._TabBarOnPress,
              },
              index
            );
          } else if (item.message_type == 2) {
            //和小消息

            this._goToScreen (
              'WithdrawCrash',
              {
                id: item.id,
                type: item.message_type,
                dataInfo: message_data,
                nTabBarOnPress: this._TabBarOnPress,
              },
              index
            );
          } else if (item.message_type == 3 || item.message_type == 4) {
            //活动消息

            this._goToScreen (
              'Activity',
              {
                id: item.id,
                type: item.message_type,
                dataInfo: message_data,
                nTabBarOnPress: this._TabBarOnPress,
              },
              index
            );
          } else if (item.message_type == 0) {
            // 系统消息

            this._goToScreen (
              'SystemMessage',
              {
                id: item.id,
                type: item.message_type,
                dataInfo: message_data,
                nTabBarOnPress: this._TabBarOnPress,
              },
              index
            );
          } else if (item.message_type == 5) {
            //账单消息
            this._goToScreen (
              'BillFinish',
              {
                id: item.id,
                type: item.message_type,
                dataInfo: message_data,
                nTabBarOnPress: this._TabBarOnPress,
              },
              index
            );
          }
        }}
        key={index}
      >
        <View style={styles.itemList}>
          <View
            style={{
              position: 'relative',
              flexDirection: 'column',
              width: 50,
              height: 48,
              alignItems: 'center',
              justifyContent: 'center',
              marginHorizontal: 5,
            }}
          >
            <ImageBackground
              resizeMode="cover"
              style={{
                width: 30,
                height: 30,
                alignItems: 'flex-end',
                justifyContent: 'flex-start',
                borderRadius: 15,
              }}
              // source={require('./src/messege.png')}
              source={imgIcon}
            >
              {item.message_state == 1
                ? null
                : <View
                    style={{
                      flexDirection: 'column',
                      width: 10,
                      height: 10,
                      borderRadius: 5,
                      backgroundColor: defaultFontColor.element_Font_Color,
                    }}
                  />}
            </ImageBackground>
          </View>
          <View
            style={{
              flexDirection: 'column',
              flex: 1,
              height: 48,
              alignItems: 'flex-start',
              justifyContent: 'center',
              paddingHorizontal: 10,
            }}
          >
            <Text
              style={{
                fontFamily: 'PingFangSC-Regular',
                fontSize: 14,
                color: defaultFontColor.main_Font_Color,
                paddingVertical: 3,
              }}
            >
              {itemTitle}
            </Text>
            <Text
              style={{
                fontSize: 12,
                color: defaultFontColor.main_Font_Color,
                paddingVertical: 5,
              }}
            >
              {item.message_cont}
            </Text>
          </View>
          <View
            style={{
              flexDirection: 'column',
              width: 100,
              height: 48,
              alignItems: 'center',
              justifyContent: 'center',
              paddingHorizontal: 10,
            }}
          >
            <Text
              style={{
                fontSize: 12,
                color: defaultFontColor.prompt_Font_Color,
                paddingVertical: 10,
              }}
            >
              {time}
            </Text>
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  _renderList (dataList) {
    return (
      <View style={{flex: 1}}>
        <FlatList
          data={dataList}
          renderItem={({item, index}) => this._renderItemList (item, index)}
          ListEmptyComponent={this._createEmptyView ()}
          numColumns={1} // 设置列数
          keyExtractor={(item, index) => index.toString ()}
          refreshing={this.state.loading}
          onEndReachedThreshold={-0.05}
          onEndReached={info => {}}
        />
      </View>
    );
  }

  render () {
    return (
      <View style={styles.container}>
        <View style={styles.itemTitle}>
          {this.state.titleContent.map ((cont, index) => {
            return this._titlePublic (cont, index);
          })}
        </View>
        <View style={{flex: 1, width: screenWidth, backgroundColor: '#fff'}}>
          {this.state.titleIndex == '业务消息'
            ? this._renderList (this.state.messageList)
            : this._renderList (this.state.systemMessageList)}
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
  itemTitle: {
    width: screenWidth,
    height: 46,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: defaultSeparateLineColor.light_SeparateLine_Color,
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
  };
}
export default connect (select) (Message);
