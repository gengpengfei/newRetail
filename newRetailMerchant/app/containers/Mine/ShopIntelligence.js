import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  FlatList,
  ScrollView,
  Alert,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {storeAuditInfo} from '../../network/shopNetApi';
import {configServerImagePath} from '../../utils/commonMethod';
import Toast from 'react-native-simple-toast';
class ShopIntelligence extends Component {
  constructor (props) {
    super (props);
    this.state = {
      loading: false,
      data: null,
    };
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="门店资质"
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._storeAuditInfo ();
  }

  _storeAuditInfo () {
    let formData = {
      admin_id: this.props.uuid,
    };
    storeAuditInfo (formData, response => {
      const {code = -1, msg = '', data = null} = response;
      if (code == -1) {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
        this.props.navigation.goBack ();
      } else {
        this.setState ({
          data: data,
        });
      }
    });
  }

  render () {
    let arr = this.state.data;
    let is_liscense = arr == null ? null : arr.is_license;
    return this.state.data == null
      ? <View
          style={{
            flex: 1,
            backgroundColor: '#fff',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text style={{fontSize: 16, color: '#9b9b9b'}}>正在加载中....</Text>
        </View>
      : <ScrollView style={styles.container}>

          <View style={{marginLeft: 14, marginTop: 12}}>
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
              }}
            >
              身份证绑定
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 44,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
              paddingLeft: 15,
              marginTop: 14,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              *姓名
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 28,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
              paddingLeft: 20,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              {arr.real_name}
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 46,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
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
              *身份证号
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 28,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
              paddingLeft: 20,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              {arr.ID_number.substr (0, 6) +
                '********' +
                arr.ID_number.substr (14)}
            </Text>
          </View>
          <View
            style={{backgroundColor: 'white', width: screenWidth, height: 16}}
          />

          <View style={{marginLeft: 14, marginTop: 12}}>
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
              }}
            >
              银行卡绑定
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 39,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
              paddingLeft: 15,
              marginTop: 14,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              银行卡号
            </Text>

            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                marginLeft: 16,
              }}
            >
              {'***************' + arr.audit_bank_card.substr (15)}
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 39,
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              flexDirection: 'row',
              alignItems: 'center',
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
              开户银行
            </Text>

            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                marginLeft: 16,
              }}
            >
              {arr.audit_bank}
            </Text>
          </View>

          <View
            style={{
              backgroundColor: 'white',
              height: 47,
              width: screenWidth,
              flexDirection: 'row',
              alignItems: 'center',
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
              预留手机
            </Text>

            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                marginLeft: 16,
              }}
            >
              {arr.audit_mobile.substr (0, 3) +
                '****' +
                arr.audit_mobile.substr (7)}
            </Text>
          </View>

          <View style={{marginLeft: 14, marginTop: 12}}>
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
              }}
            >
              主体资质
            </Text>
          </View>

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 44,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  paddingLeft: 15,
                  marginTop: 14,
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  * 执照号
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 28,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  paddingLeft: 20,
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  {arr.business_license_number}
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 44,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
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
                  * 营业执照名称：
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 28,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  paddingLeft: 20,
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  {arr.business_license_name}
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 44,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
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
                  * 法人名称
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 28,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  paddingLeft: 20,
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  {arr.real_name}
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 44,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
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
                  * 营业执照有效期
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 32,
                  width: screenWidth,
                  borderBottomColor: defaultBackgroundColor.page_Background_Color,
                  borderBottomWidth: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  paddingLeft: 20,
                  justifyContent: 'flex-start',
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  {arr.business_license_time}
                </Text>
              </View>}

          {is_liscense == 0
            ? null
            : <View
                style={{
                  backgroundColor: 'white',
                  height: 44,
                  width: screenWidth,
                  flexDirection: 'row',
                  alignItems: 'center',
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
                  * 营业执照有效期
                </Text>
              </View>}

          {is_liscense == 1
            ? <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  backgroundColor: '#fff',
                  width: screenWidth,
                }}
              >
                <View
                  style={{
                    justifyContent: 'center',
                    width: 68,
                    height: 68,
                    borderColor: defaultBackgroundColor.page_Background_Color,
                    borderWidth: 1,
                    backgroundColor: '#e6e6e6',
                    alignItems: 'center',
                    marginLeft: 15,
                  }}
                >
                  <Image
                    style={{width: 40, height: 50}}
                    source={{
                      uri: configServerImagePath (
                        this.state.data.audit_license[0]
                      ),
                    }}
                  />
                </View>
              </View>
            : <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  backgroundColor: '#fff',
                  width: screenWidth,
                  paddingTop: 10,
                }}
              >
                <View
                  style={{
                    justifyContent: 'center',
                    width: 68,
                    height: 68,
                    borderColor: defaultBackgroundColor.page_Background_Color,
                    borderWidth: 1,
                    backgroundColor: '#e6e6e6',
                    alignItems: 'center',
                    marginLeft: 15,
                  }}
                >
                  <Image
                    style={{width: 40, height: 50}}
                    source={{uri: configServerImagePath (arr.temp_license[0])}}
                  />
                </View>
              </View>}

          <View
            style={{
              height: 41,
              width: screenWidth,
              flexDirection: 'row',
              alignItems: 'center',
              paddingLeft: 15,
              backgroundColor: '#fff',
            }}
          >
            <Text
              style={{
                color: '#9b9b9b',
                fontSize: 12,
                fontFamily: 'PingFangSC-Regular',
              }}
              numberOfLines={1}
            >
              资质照片可以是原件、副本、复印件(加公章)的照片
            </Text>
          </View>

        </ScrollView>;
  }
}

const styles = StyleSheet.create ({
  container: {
    // flex:1,
    backgroundColor: defaultBackgroundColor.page_Background_Color,
  },
});

function select (store) {
  return {
    uuid: store.userInfo.admin_id,
    store_id: store.userInfo.store_id,
    admin_id: store.userInfo.admin_id,
    storeProgress: store.storeInfo.progress,
    storeInfo_storeID: store.storeInfo.store_id,
    store_name: store.storeInfo.store_name,
    store_address: store.storeInfo.store_address,
  };
}
export default connect (select) (ShopIntelligence);
