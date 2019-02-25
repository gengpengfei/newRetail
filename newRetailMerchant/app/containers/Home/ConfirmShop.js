import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  FlatList,
  ScrollView,
  TextInput,
  Alert,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import Toast from 'react-native-simple-toast';

import {NavigationActions} from 'react-navigation';
import {storeList, claimStore} from '../../network/shopNetApi';
import {getPositionInfo} from '../../utils/LocationTool';
class ConfirmShop extends Component {
  constructor (props) {
    super (props);

    this._storeList = this._storeList.bind (this);

    this.state = {
      loading: false,
      storeList: [],
      temp: false,
      keywords: '',
    };
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="认领门店"
        leftPress={() => {
          navigation.state.params.callBackData ();
          // const navigationAction = NavigationActions.reset({
          //     index: 0,
          //     actions: [
          //         NavigationActions.navigate({
          //                 routeName: 'RootTabNav',
          //                 action:NavigationActions.navigate({
          //                     routeName: 'Home',

          //                 })
          //             }
          //         )]
          // })
          // navigation.dispatch(navigationAction);
          navigation.goBack ();
        }}
      />
    ),
  });

  _goToScreen = str => {
    const {navigate} = this.props.navigation;
    navigate (str);
  };

  componentDidMount () {
    this._storeList ();
  }

  _storeList () {
    getPositionInfo (loc => {
      const {data} = loc;
      let formData = {
        locationData: data,
        keywords: this.state.keywords,
      };

      storeList (formData, response => {
        const {code = -1, msg = '', data = []} = response;
        if (code == -1) {
          Toast.showWithGravity ('门店列表获取失败', 1, Toast.CENTER);
        } else {
          this.setState ({
            storeList: data,
          });
        }
      });
    });
  }

  _claimStore (store_id, store_name, store_address, store_type) {
    let formData = {
      store_id: store_id,
      admin_id: this.props.uuid,
    };

    if (store_type == 1) {
      Alert.alert ('温馨提示', '店铺已经被认领,请认领其他店铺？', [
        {text: '确定', onPress: () => {}},
        {text: '取消', onPress: () => {}},
      ]);
    } else {
      claimStore (formData, response => {
        const {code = -1, msg = '', data = []} = response;
        if (code == -1) {
          Toast.showWithGravity (msg, 1, Toast.CENTER);
        } else {
          Toast.showWithGravity ('认领成功', 1, Toast.CENTER);
          let parasss = {
            store_id: store_id,
            store_name: store_name,
            store_address: store_address,
            callBackData: this.props.navigation.state.params.callBackData,
            goBackKey: this.props.navigation.state.key,
          };

          const {navigate} = this.props.navigation;
          navigate ('HomeShopIntelligence', parasss);
        }
      });
    }
  }

  _renderItem = (item, index) => {
    return (
      <View
        style={{
          height: 80,
          flexDirection: 'row',
          alignItems: 'center',
          borderBottomColor: defaultBackgroundColor.page_Background_Color,
          borderBottomWidth: 1,
        }}
      >

        <View
          style={{
            flex: 3,
            justifyContent: 'center',
            alignItems: 'flex-start',
            paddingLeft: 15,
          }}
        >
          <Text
            style={{
              color: '#4a4a4a',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            {item.store_name}
          </Text>
          <Text
            style={{
              color: '#4a4a4a',
              fontSize: 12,
              fontFamily: 'PingFangSC-Regular',
              marginTop: 8,
            }}
            numberOfLines={1}
          >
            {item.store_address}  {item.distance}m
          </Text>
        </View>

        <View
          style={{
            flex: 1,
            justifyContent: 'center',
            alignItems: 'flex-end',
            paddingRight: 18,
          }}
        >
          <TouchableOpacity
            onPress={() => {
              this._claimStore (
                item.store_id,
                item.store_name,
                item.store_address,
                item.store_type
              );
            }}
          >
            <View
              style={{
                borderWidth: 1,
                borderColor: '#f63300',
                width: 62,
                height: 24,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Text
                style={{
                  color: '#f63300',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                }}
              >
                认领
              </Text>
            </View>
          </TouchableOpacity>
        </View>

      </View>
    );
  };

  _createEmptyView = () => {
    return (
      <View style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
        <Text
          style={{
            color: '#4a4a4a',
            fontSize: 16,
            fontFamily: 'PingFangSC-Regular',
          }}
        >
          正在加载中.....
        </Text>
      </View>
    );
  };

  _createFooter = () => {
    return this.state.storeList
      ? <View
          style={{
            marginTop: 27,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text
            style={{
              color: '#9b9b9b',
              fontSize: 12,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            新开门店，或没找到你的门店？
          </Text>
        </View>
      : null;
  };

  render () {
    return (
      <View style={styles.container}>

        <View
          style={{
            height: 46,
            flexDirection: 'row',
            alignItems: 'center',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
          }}
        >
          <TextInput
            placeholder="搜索店铺"
            underlineColorAndroid="transparent"
            style={{fontSize: 14, color: '#9b9b9b', flex: 1, paddingLeft: 15}}
            onChangeText={text => {
              this.setState (
                {
                  keywords: text,
                },
                this._storeList ()
              );
            }}
          />

        </View>

        <FlatList
          data={this.state.storeList}
          renderItem={({item, index}) => this._renderItem (item, index)}
          // ListEmptyComponent={this._createEmptyView()}
          ListFooterComponent={this._createFooter ()}
          numColumns={1} // 设置列数
          keyExtractor={(item, index) => index.toString ()}
          refreshing={this.state.loading}
          onEndReachedThreshold={-0.05}
          onEndReached={info => {}}
          extraData={this.state}
        />

        <TouchableOpacity
          onPress={() => {
            this._goToScreen ('CreateShop');
          }}
        >
          <View
            style={{
              marginTop: 17,
              marginBottom: 15,
              backgroundColor: '#f63300',
              marginHorizontal: 16,
              borderRadius: 3,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'center',
              width: screenWidth - 32,
              paddingVertical: 13,
            }}
          >
            <Text
              style={{
                color: '#fff',
                fontSize: 16,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              创建新门店
            </Text>
          </View>
        </TouchableOpacity>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: 'white',
  },
});

function select (store) {
  return {
    uuid: store.userInfo.admin_id,
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (ConfirmShop);
