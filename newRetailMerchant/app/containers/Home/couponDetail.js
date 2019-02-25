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
  tabBarIconStyle,
  defaultContainerStyles,
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';
import Header from '../../components/Header/Header';

import Toast from 'react-native-simple-toast';
import {storeVoucherInfo} from '../../network/shopNetApi';
import {configServerImagePath} from '../../utils/commonMethod';
import {isNotEmptyArray} from '../../utils/structureJudgment';

class Message extends Component {
  constructor (props) {
    super (props);
    this.state = {
      titleIndex: '券信息',
      voucherDes: {},
      voucherInformation: [],
      loading: false,
      titleContent: ['券信息', '统计信息'],
      dataAtrr: [],
    };

    this._titlePublic = this._titlePublic.bind (this);
    this._createEmptyView = this._createEmptyView.bind (this);
    this._goToScreen = this._goToScreen.bind (this);
    this._renderItemInformation = this._renderItemInformation.bind (this);
    this._renderItemTextList = this._renderItemTextList.bind (this);
    this._getVoucherInformation = this._getVoucherInformation.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle={
          navigation.state.params.voucher_type == 1 ? '代金券详情' : '优惠券详情'
        }
      />
    ),
  });

  componentDidMount () {
    this._getVoucherInformation ();
  }
  _getVoucherInformation () {
    let formData = {
      store_id: this.props.store_id,
      voucher_id: this.props.navigation.state.params.voucher_id,
    };

    storeVoucherInfo (formData, responseData => {
      let msg = responseData['msg'];
      let code = responseData['code'];
      let dataItem = responseData['data'];
      if (code === 1) {
        dataItem['voucher_info'].attr_value = isNotEmptyArray (
          dataItem['voucher_attr']
        )
          ? dataItem['voucher_attr'][0].attr_value
          : [];
        const {
          voucher_stock,
          sell_num,
          used_num,
          voucher_price,
          voucher_type,
        } = dataItem['voucher_info'];
        if (voucher_type == 1) {
          var voucherInformationList = [
            {voucher_stock: voucher_stock},
            {sell_num: sell_num},
            {used_num: used_num},
            {voucher_price: voucher_price},
            {voucher_Total: voucher_price * used_num},
          ];
        } else if (voucher_type == 0) {
          var voucherInformationList = [
            {voucher_stock: voucher_stock},
            {sell_num: sell_num},
            {used_num: used_num},
          ];
        }
        this.setState ({
          voucherDes: dataItem['voucher_info'],
          voucherInformation: voucherInformationList,
          dataAtrr: dataItem['voucher_attr'],
        });
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
            this.setState ({
              titleIndex: cont,
              collectList: [],
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

  _renderItemList = item => {
    return (
      <View style={{width: screenWidth, flexDirection: 'column'}}>
        <View
          style={{
            width: screenWidth,
            backgroundColor: '#fff',
            flexDirection: 'column',
            height: 110,
            marginVertical: 15,
            paddingHorizontal: 10,
          }}
        >
          <View
            style={[
              styles.itemList,
              {
                borderBottomWidth: 1,
                borderBottomColor: defaultSeparateLineColor.light_SeparateLine_Color,
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
                {item['voucher_name']}
              </Text>
              {item.voucher_type == 1
                ? <Text
                    numberOfLines={2}
                    style={{fontSize: 12, color: '#f63300', paddingVertical: 5}}
                  >
                    ¥{item.voucher_price}
                  </Text>
                : item.use_method == 0
                    ? <Text
                        numberOfLines={2}
                        style={{
                          fontSize: 12,
                          color: '#f63300',
                          paddingVertical: 5,
                        }}
                      >
                        {item.use_method_info}元优惠券
                      </Text>
                    : <Text
                        numberOfLines={2}
                        style={{
                          fontSize: 12,
                          color: '#f63300',
                          paddingVertical: 5,
                        }}
                      >
                        {item.use_method_info / 10}折优惠券
                      </Text>}
            </View>

            <View
              style={{
                flexDirection: 'column',
                width: 100,
                height: 48,
                alignItems: 'flex-end',
                justifyContent: 'center',
              }}
            >

              {item.voucher_type == 1
                ? null
                : <Text
                    numberOfLines={1}
                    style={{
                      fontSize: 14,
                      color: defaultFontColor.element_Font_Color,
                      paddingVertical: 10,
                    }}
                  >
                    满{item.min_amount}可用
                  </Text>}

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
              <Text
                style={{
                  fontSize: 18,
                  color: defaultFontColor.prompt_Font_Color,
                  paddingVertical: 6,
                }}
              >
                ∙
              </Text>
              有效期 {item.use_start_date} 至 {item.use_end_date}
            </Text>
          </View>
        </View>
        {!isNotEmptyArray (this.state.dataAtrr)
          ? null
          : <View
              style={{
                width: screenWidth,
                backgroundColor: '#fff',
                flexDirection: 'column',
                paddingHorizontal: 15,
              }}
            >
              <View
                style={{
                  flexDirection: 'column',
                  height: 45,
                  alignItems: 'flex-start',
                  justifyContent: 'center',
                }}
              >
                <Text
                  style={{
                    fontSize: 14,
                    color: defaultFontColor.main_Font_Color,
                    paddingTop: 15,
                    paddingBottom: 8,
                  }}
                >
                  使用须知
                </Text>
              </View>
              <View
                style={{
                  borderWidth: 1,
                  borderColor: '#E9EFEF',
                  marginRight: 13,
                }}
              />
              {!isNotEmptyArray (this.state.dataAtrr)
                ? null
                : this.state.dataAtrr.map ((cont, idx) => {
                    return (
                      <View key={idx}>
                        <Text
                          style={{
                            color: '#4A4A4A',
                            fontFamily: 'PingFangSC-Medium',
                            fontSize: 14,
                            marginTop: 16,
                          }}
                        >
                          {cont.attr_name}
                        </Text>
                        <Text
                          style={{
                            color: '#4A4A4A',
                            fontFamily: 'PingFangSC-Regular',
                            fontSize: 12,
                            marginTop: 5,
                            marginLeft: 5,
                            width: screenWidth - 35,
                          }}
                        >
                          {cont.attr_value}
                        </Text>
                      </View>
                    );
                  })}
            </View>}
      </View>
    );
  };

  _renderItemTextList (item, index) {
    var itemName, itemNum;
    if (index === 0) {
      itemName = '总券数';
      itemNum = item.voucher_stock;
    } else if (index === 1) {
      itemName = '已领取';
      itemNum = item.sell_num;
    } else if (index === 2) {
      itemName = '已使用';
      itemNum = item.used_num;
    } else if (index === 3) {
      itemName = '券单价';
      itemNum = '¥' + item.voucher_price;
    } else if (index === 4) {
      itemName = '券总额';
      itemNum = '¥' + item.voucher_Total;
    }
    return (
      <View
        style={[
          styles.itemTextList,
          (index + 1) % 3 === 0
            ? {}
            : {
                borderRightWidth: 1,
                borderRightColor: defaultSeparateLineColor.light_SeparateLine_Color,
              },
        ]}
      >
        <Text
          style={{
            fontSize: 14,
            color: defaultFontColor.main_Font_Color,
            paddingTop: 8,
            paddingBottom: 5,
          }}
        >
          {itemName}
        </Text>
        <Text style={{fontSize: 14, color: defaultFontColor.main_Font_Color}}>
          {itemNum}
        </Text>
      </View>
    );
  }

  _renderItemInformation (dataList) {
    return (
      <View
        style={{
          width: screenWidth,
          flexDirection: 'column',
          marginVertical: 15,
          backgroundColor: '#fff',
        }}
      >
        <FlatList
          data={dataList}
          renderItem={({item, index}) => this._renderItemTextList (item, index)}
          ListEmptyComponent={this._createEmptyView ()}
          numColumns={3} //  每行设置列数
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
        <View style={{flex: 1, width: screenWidth}}>
          {this.state.titleIndex === '券信息'
            ? this._renderItemList (this.state.voucherDes)
            : this._renderItemInformation (this.state.voucherInformation)}
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
  },
  itemTextList: {
    width: screenWidth / 3,
    flexDirection: 'column',
    height: 50,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
function select (store) {
  return {
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (Message);
