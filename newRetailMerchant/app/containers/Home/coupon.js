import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  SafeAreaView,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import {connect} from 'react-redux';
import {
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';
import Header from '../../components/Header/Header';
import Toast from 'react-native-simple-toast';
import {storeVoucherList} from '../../network/shopNetApi';
import {configServerImagePath} from '../../utils/commonMethod';
class Coupon extends Component {
  constructor (props) {
    super (props);
    this.state = {
      titleIndex: '进行中',
      couponList: [],
      couponListInvalid: [],
      loading: false,
      titleContent: ['进行中', '已过期'],
      msg: '',
      flag: false,
    };

    this._titlePublic = this._titlePublic.bind (this);
    this._renderList = this._renderList.bind (this);
    this._createEmptyView = this._createEmptyView.bind (this);
    this._goToScreen = this._goToScreen.bind (this);
    this._getDataList = this._getDataList.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle={
          navigation.state.params.voucher_type == 0 ? '优惠券管理' : '代金券管理'
        }
      />
    ),
  });

  componentDidMount () {
    if (this.props.navigation.state.params.voucher_type === 0) {
      this._getDataList (0, 1);
    } else if (this.props.navigation.state.params.voucher_type === 1) {
      this._getDataList (1, 1);
    }
  }

  _getDataList (voucher_type, isValid, apiMethod) {
    let formData = {
      admin_id: this.props.admin_id,
      store_id: this.props.store_id,
      voucher_type: voucher_type,
      is_invalid: isValid,
    };
    storeVoucherList (formData, responseData => {
      let msg = responseData['msg'];
      let code = responseData['code'];
      if (code === 1) {
        if (isValid === 1) {
          // 进行中
          this.setState (
            {
              couponList: responseData['data'],
            },
            () => {
              if (responseData['data'].length != 0) {
                this.setState ({
                  msg: '',
                });
              } else {
                this.setState ({
                  msg: '暂无相关优惠券信息',
                });
              }
            }
          );
        } else if (isValid === 0) {
          // 已过期
          let arr = responseData['data'];
          if (responseData['data'].length != 0) {
            this.setState ({
              msg: '',
              couponListInvalid: arr,
            });
          } else {
            this.setState ({
              msg: '暂无相关优惠券信息',
              couponListInvalid: [],
            });
          }
        }
      } else {
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

  _titlePublic (cont, index) {
    return (
      <TouchableOpacity
        onPress={() => {
          if (this.state.titleIndex !== cont) {
            this.setState (
              {
                titleIndex: cont,
              },
              () => {
                if (this.props.navigation.state.params.voucher_type === 0) {
                  if (index == 0) {
                    this._getDataList (0, 1);
                  } else {
                    this._getDataList (0, 0);
                  }
                } else {
                  if (index == 0) {
                    this._getDataList (1, 1);
                  } else {
                    this._getDataList (1, 0);
                  }
                }
              }
            );
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
        <Text style={{fontSize: 14}}>
          {this.state.msg != '' ? this.state.msg : '正在加载中....'}
        </Text>
      </View>
    );
  };

  _renderItemList = (item, index) => {
    return (
      <TouchableOpacity
        onPress={() => {
          this._goToScreen ('CouponDetail', {
            voucher_id: item.voucher_id,
            store_id: item.store_id,
            voucher_type: item.voucher_type,
          });
        }}
        key={index}
      >
        <View
          style={{
            width: screenWidth - 30,
            backgroundColor: this.state.titleIndex === '进行中'
              ? '#fff'
              : '#EBEBEB',
            flexDirection: 'column',
            height: 110,
            marginTop: 15,
            paddingHorizontal: 10,
          }}
        >
          <View
            style={[
              styles.itemList,
              {
                borderBottomColor: this.state.titleIndex === '进行中'
                  ? defaultSeparateLineColor.light_SeparateLine_Color
                  : defaultSeparateLineColor.dark_SeparateLine_Color,
              },
            ]}
          >
            <View
              style={{
                flexDirection: 'column',
                width: 50,
                height: 48,
                alignItems: 'center',
                justifyContent: 'center',
                marginHorizontal: 5,
              }}
            >
              <Image
                resizeMode="cover"
                style={{width: 55, height: 55}}
                source={{uri: configServerImagePath (item.voucher_img)}}
              />
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
                numberOfLines={1}
                style={{
                  fontFamily: 'PingFangSC-Regular',
                  fontSize: 14,
                  color: defaultFontColor.main_Font_Color,
                  paddingVertical: 3,
                }}
              >
                {item.voucher_name}
              </Text>

              {item.voucher_type == 1
                ? <Text
                    numberOfLines={2}
                    style={{
                      fontSize: 14,
                      color: this.state.titleIndex === '进行中'
                        ? defaultFontColor.element_Font_Color
                        : defaultFontColor.main_Font_Color,
                      paddingVertical: 5,
                    }}
                  >
                    ¥{item.voucher_price}
                  </Text>
                : item.use_method == 0
                    ? <Text
                        numberOfLines={2}
                        style={{
                          fontSize: 14,
                          color: this.state.titleIndex === '进行中'
                            ? defaultFontColor.element_Font_Color
                            : defaultFontColor.main_Font_Color,
                          paddingVertical: 5,
                        }}
                      >
                        {item.use_method_info}元优惠券
                      </Text>
                    : <Text
                        numberOfLines={2}
                        style={{
                          fontSize: 14,
                          color: this.state.titleIndex === '进行中'
                            ? defaultFontColor.element_Font_Color
                            : defaultFontColor.main_Font_Color,
                          paddingVertical: 5,
                        }}
                      >
                        {item.use_method_inf / 10}折优惠券
                      </Text>}

            </View>
            <View
              numberOfLines={1}
              style={{
                flexDirection: 'column',
                width: 80,
                height: 48,
                alignItems: 'flex-end',
                justifyContent: 'center',
              }}
            >
              <Text
                style={{
                  fontSize: 12,
                  color: this.state.titleIndex === '进行中'
                    ? defaultFontColor.element_Font_Color
                    : defaultFontColor.main_Font_Color,
                  paddingVertical: 10,
                }}
              >
                {this.state.titleIndex === '进行中' ? '进行中' : '已过期'}
              </Text>
            </View>
          </View>
          <View
            style={{
              flexDirection: 'column',
              height: 32,
              alignItems: 'flex-start',
            }}
          >
            <Text
              style={{
                fontSize: 12,
                color: defaultFontColor.main_Font_Color,
                paddingVertical: 6,
              }}
            >
              已售{item.sell_num}张，使用{item.used_num}张
            </Text>
          </View>
        </View>

      </TouchableOpacity>
    );
  };

  _renderList = dataList => {
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
          extraData={this.state}
        />

      </View>
    );
  };

  render () {
    return (
      <View style={styles.container}>

        <View style={styles.itemTitle}>
          {this.state.titleContent.map ((cont, index) => {
            return this._titlePublic (cont, index);
          })}
        </View>
        <View style={{flex: 1, width: screenWidth - 30, marginHorizontal: 15}}>
          {this.state.titleIndex === '进行中'
            ? this._renderList (this.state.couponList)
            : this._renderList (this.state.couponListInvalid)}
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
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: defaultSeparateLineColor.light_SeparateLine_Color,
  },
  itemList: {
    height: 78,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
});
function select (store) {
  return {
    admin_id: store.userInfo.admin_id,
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (Coupon);
