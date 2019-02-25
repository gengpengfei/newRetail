import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  ScrollView,
  TextInput,
} from 'react-native';
import {connect} from 'react-redux';
import Header from '../../components/Header/Header';
import Toast from 'react-native-simple-toast';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
const width = Dimensions.get ('window').width;
import {createStore} from '../../network/shopNetApi';
import {isNotEmptyArray} from '../../utils/structureJudgment';
import RegExpTool from '../../utils/RegExpTool';
import {NavigationActions} from 'react-navigation';
import {refreshData} from '../../redux/action/refresh_action';
class CreateShop extends Component {
  constructor (props) {
    super (props);
    this.state = {
      text: '',
      flag: false,
      storeName: '',
      mobileNum: '',
      type: '请选择',
      address: '请选择(省/市/区)',
      category_id: '',
      nav_id: '',
      province: null,
      city: null,
      district: null,
      addressMain: '请选择',
      addressDetail: '请输入详细地址',
      btnActive: true,
    };
    this._goBackData = this._goBackData.bind (this);
    this._goBackData11 = this._goBackData11.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        centerTitle={'创建门店'}
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });
  _goBackData = (name, category_id, nav_id) => {
    this.setState ({
      type: name,
      nav_id: nav_id,
      category_id: category_id,
    });
  };

  _goBackData11 = (arr, address1, address2) => {
    if (isNotEmptyArray (arr)) {
      this.setState ({
        province: arr[0],
        city: arr[1],
        district: arr[2],
        address: address1 + address2,
        addressMain: address1,
        addressDetail: address2,
      });
    } else {
    }
  };

  _goToScreen = (str, dataParams, dataParams2) => {
    const {navigate} = this.props.navigation;
    if (str == 'CreateShopAddress') {
      let temp = {
        callBackFunc11: this._goBackData11,
        addressMain: dataParams,
        addressDetail: dataParams2,
      };
      navigate (str, temp);
    } else {
      navigate (str, {callBackFunc: this._goBackData});
    }
  };

  _createStore () {
    let isPhoneNum = RegExpTool.phoneByReg (this.state.mobileNum);
    let formData = {
      store_name: this.state.storeName,
      store_address: this.state.address,
      lat: '31.264998',
      lng: '121.612833',
      province: this.state.province,
      city: this.state.city,
      district: this.state.district,
      store_phone: this.state.mobileNum,
      nav_id: this.state.nav_id,
      category_id: this.state.category_id,
      admin_id: this.props.admin_id,
    };

    if (
      this.state.storeName == '' ||
      this.state.address == '' ||
      this.state.category_id == ''
    ) {
      Toast.showWithGravity ('数据不能为空', 1, Toast.CENTER);
    } else if (!isPhoneNum['check']) {
      Toast.showWithGravity (isPhoneNum['error'], 1, Toast.CENTER);
    } else {
      createStore (formData, response => {
        const {code = -1, msg = '', data = []} = response;
        if (code == -1) {
          Toast.showWithGravity (msg, 1, Toast.CENTER);
        } else {
          this.setState ({
            btnActive: false,
          });

          Toast.showWithGravity (msg, 1, Toast.CENTER);
          this.props.dispatch (refreshData ('refresh_data', 1));
          setTimeout (() => {
            const navigationAction = NavigationActions.reset ({
              index: 0,
              actions: [
                NavigationActions.navigate ({
                  routeName: 'RootTabNav',
                  action: NavigationActions.navigate ({
                    routeName: 'Home',
                  }),
                }),
              ],
            });
            this.props.navigation.dispatch (navigationAction);
          }, 500);
        }
      });
    }
  }
  render () {
    return (
      <View style={styles.container}>
        <View
          style={{
            width: screenWidth,
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: 'white',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
            height: 46,
          }}
        >
          <View
            style={{
              flex: 1,
              paddingLeft: 12,
              flexDirection: 'row',
              alignItems: 'center',
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              门店名称:
            </Text>
          </View>
          <TextInput
            multiline={true}
            placeholder="若有分店，请具体到分店名"
            underlineColorAndroid="transparent"
            style={{fontSize: 14, color: '#9b9b9b', flex: 4, paddingLeft: 12}}
            onChangeText={text => {
              this.setState ({
                storeName: text,
              });
            }}
          />
        </View>

        <TouchableOpacity
          onPress={() => {
            this._goToScreen (
              'CreateShopAddress',
              this.state.addressMain,
              this.state.addressDetail
            );
          }}
        >
          <View
            style={{
              width: screenWidth,
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: 'white',
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              height: 46,
            }}
          >
            <View
              style={{
                flex: 1,
                paddingLeft: 12,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <Text
                style={{
                  color: '#4a4a4a',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                }}
              >
                门店地址:
              </Text>
            </View>

            <View
              style={{
                flexDirection: 'row',
                flex: 4,
                alignItems: 'center',
                justifyContent: 'space-between',
                paddingHorizontal: 12,
              }}
            >
              <Text
                style={{
                  color: '#9b9b9b',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  flex: 6,
                }}
              >
                {this.state.address}
              </Text>

              <Image
                source={require ('../Mine/src/lnto.png')}
                style={{width: 8, height: 13}}
              />

            </View>
          </View>
        </TouchableOpacity>

        <View
          style={{
            width: screenWidth,
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: 'white',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
            height: 46,
          }}
        >
          <View
            style={{
              flex: 1,
              paddingLeft: 12,
              flexDirection: 'row',
              alignItems: 'center',
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              门店电话:
            </Text>
          </View>
          <TextInput
            multiline={true}
            placeholder="填写座机/手机，座机需加区号"
            underlineColorAndroid="transparent"
            style={{fontSize: 14, color: '#9b9b9b', flex: 4, paddingLeft: 12}}
            onChangeText={text => {
              this.setState ({
                mobileNum: text,
              });
            }}
          />
        </View>

        <TouchableOpacity
          onPress={() => {
            this._goToScreen ('ChooseType');
          }}
        >
          <View
            style={{
              width: screenWidth,
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: 'white',
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
              height: 46,
            }}
          >
            <View
              style={{
                flex: 1,
                paddingLeft: 12,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <Text
                style={{
                  color: '#4a4a4a',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                }}
              >
                经营品类:
              </Text>
            </View>
            <View
              style={{
                flexDirection: 'row',
                flex: 4,
                alignItems: 'center',
                justifyContent: 'space-between',
                paddingHorizontal: 12,
              }}
            >
              <Text
                style={{
                  color: '#9b9b9b',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  flex: 6,
                }}
              >
                {this.state.type}
              </Text>

              <Image
                source={require ('../Mine/src/lnto.png')}
                style={{width: 8, height: 13}}
              />

            </View>
          </View>
        </TouchableOpacity>

        <View
          style={{
            width: screenWidth - 62,
            marginHorizontal: 31,
            position: 'absolute',
            bottom: 23,
            flexDirection: 'row',
            justifyContent: 'space-between',
            marginTop: 29,
            alignItems: 'center',
          }}
        >
          <TouchableOpacity
            onPress={() => {
              this.props.navigation.goBack ();
            }}
          >
            <View
              style={{
                backgroundColor: '#f63300',
                borderRadius: 3,
                width: (screenWidth - 62 - 34) / 2,
                flexDirection: 'row',
                justifyContent: 'center',
                alignItems: 'center',
                paddingVertical: 10,
              }}
            >
              <Text
                style={{
                  color: '#fff',
                  fontSize: 16,
                  fontFamily: 'PingFangSC-Regular',
                }}
              >
                取消
              </Text>
            </View>
          </TouchableOpacity>
          {this.state.btnActive == false
            ? <View
                style={{
                  backgroundColor: defaultBackgroundColor.condition_Background,
                  borderRadius: 3,
                  width: (screenWidth - 62 - 34) / 2,
                  flexDirection: 'row',
                  justifyContent: 'center',
                  alignItems: 'center',
                  paddingVertical: 10,
                }}
              >
                <Text
                  style={{
                    color: '#fff',
                    fontSize: 16,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  创建成功
                </Text>
              </View>
            : <TouchableOpacity
                onPress={() => {
                  this._createStore ();
                }}
              >
                <View
                  style={{
                    backgroundColor: '#f63300',
                    borderRadius: 3,
                    width: (screenWidth - 62 - 34) / 2,
                    flexDirection: 'row',
                    justifyContent: 'center',
                    alignItems: 'center',
                    paddingVertical: 10,
                  }}
                >
                  <Text
                    style={{
                      color: '#fff',
                      fontSize: 16,
                      fontFamily: 'PingFangSC-Regular',
                    }}
                  >
                    确认创建
                  </Text>
                </View>
              </TouchableOpacity>}

        </View>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,

    backgroundColor: defaultBackgroundColor.page_Background_Color,
  },
});

function select (store) {
  return {
    admin_id: store.userInfo.admin_id,
  };
}
export default connect (select) (CreateShop);
