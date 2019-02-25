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
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {MapView, MapTypes, Geolocation} from 'react-native-baidu-map';
import {getPositionInfo} from '../../utils/LocationTool';
import Toast from 'react-native-simple-toast';
var temp = [];
var addressTemp = '';
var addressDetailTemp = '';
export default class CreateShopAddress extends Component {
  constructor (props) {
    super (props);
    this.state = {
      loading: false,
      province: '',
      city: '',
      district: '',
      arrId: [],
      addressDetail: this.props.navigation.state.params.addressDetail,
      center: null,
      address: this.props.navigation.state.params.addressMain,
    };

    this._goBackData = this._goBackData.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="门店地址"
        leftPress={() => {
          navigation.state.params.callBackFunc11 ([], '', '');
          navigation.goBack ();
        }}
        rightPress={() => {
          if (
            addressDetailTemp == '' ||
            addressTemp == '' ||
            addressTemp == '请选择'
          ) {
            Toast.showWithGravity ('地址不能为空', 1, Toast.CENTER);
          } else {
            navigation.state.params.callBackFunc11 (
              temp,
              addressTemp,
              addressDetailTemp
            );
            navigation.goBack ();
          }
        }}
        rightText="确认"
      />
    ),
  });

  _goBackData = (privicne, city, district, arr) => {
    this.setState (
      {
        province: privicne,
        city: city,
        district: district,
        arrId: arr,
        address: privicne + city + district,
      },
      () => {
        temp = this.state.arrId;
      }
    );
  };

  _goToScreen = str => {
    const {navigate} = this.props.navigation;
    navigate (str, {callBackData: this._goBackData});
  };

  componentWillMount () {
    getPositionInfo (location => {
      const {data} = location;
      const {lat, lng, display_name} = data;
      this.setState ({
        center: {longitude: parseFloat (lng), latitude: parseFloat (lat)},
      });
    });
  }

  componentDidMount () {}

  render () {
    return (
      <View style={styles.container}>
        <View
          style={{
            width: screenWidth,
            alignItems: 'center',
            height: 104,
            flexDirection: 'row',
            backgroundColor: 'white',
          }}
        >
          <View
            style={{
              flex: 1,
              alignItems: 'center',
              justifyContent: 'flex-start',
            }}
          >
            <Image
              source={require ('./src/warning.png')}
              style={{width: 18, height: 18}}
            />
          </View>
          <View style={{flex: 8, alignItems: 'flex-start', paddingTop: 5}}>
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
              numberOfLines={1}
            >
              请按格式填写地址，以免影响门店搜索和活动报名
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
              例1：道路+门牌号，“人民东路18号”
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
              例1：道路+门牌号，“人民东路18号南京东路人民广场”
            </Text>
          </View>
        </View>

        <TouchableOpacity
          onPress={() => {
            this._goToScreen ('cityPage');
          }}
        >
          <View
            style={{
              backgroundColor: 'white',
              height: 46,
              flexDirection: 'row',
              alignItems: 'center',
              width: screenWidth,
              borderBottomColor: defaultBackgroundColor.page_Background_Color,
              borderBottomWidth: 1,
            }}
          >
            <View
              style={{
                flex: 2,
                paddingLeft: 14,
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
                省/市/区
              </Text>
            </View>
            <View
              style={{
                flex: 6,
                paddingLeft: 10,
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
                {this.state.address}
              </Text>
            </View>

            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                paddingRight: 14,
              }}
            >
              <Image
                style={{width: 8, height: 13}}
                source={require ('../Mine/src/lnto.png')}
              />
            </View>
          </View>
        </TouchableOpacity>

        <View
          style={{
            backgroundColor: 'white',
            height: 68,
            flexDirection: 'row',
            alignItems: 'flex-start',
            width: screenWidth,
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
          }}
        >
          <View
            style={{
              flex: 1,
              paddingLeft: 14,
              flexDirection: 'row',
              alignItems: 'center',
              paddingTop: 14,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              详细地址
            </Text>
          </View>
          <TextInput
            placeholder={''}
            underlineColorAndroid="transparent"
            value={this.state.addressDetail}
            style={{
              fontSize: 14,
              color: '#9b9b9b',
              flex: 4,
              alignItems: 'flex-start',
              padddingTop: 10,
              margin: 0,
            }}
            onChangeText={text => {
              this.setState (
                {
                  addressDetail: text,
                },
                () => {
                  addressTemp = this.state.address;
                  addressDetailTemp = this.state.addressDetail;
                }
              );
            }}
          />
        </View>

        <View
          style={{
            height: 46,
            flexDirection: 'row',
            alignItems: 'center',
            paddingLeft: 14,
            width: screenWidth,
            backgroundColor: 'white',
          }}
        >
          <Text
            style={{
              color: '#f63300',
              fontSize: 12,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            如定位不准确，请手动选择地图位置
          </Text>
        </View>

        <MapView
          onMapClick={poi => {
            Geolocation.reverseGeoCodeGPS (poi.latitude, poi.longitude)
              .then (data => {})
              .catch (e => {});
          }}
          zoom={14}
          mapType={MapTypes.NORMAL}
          center={this.state.center}
          style={styles.map}
        />

      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: defaultBackgroundColor.page_Background_Color,
  },
  map: {
    flex: 1,
  },
});
