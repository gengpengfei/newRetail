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
import {getStoreOrderInfo} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
const width = Dimensions.get ('window').width;
class shopOrderDetail extends Component {
  constructor (props) {
    super (props);
    this.state = {
      text: '',
      flag: false,
      data: null,
    };
    this._getStoreOrderInfo = this._getStoreOrderInfo.bind (this);
  }
  _getStoreOrderInfo () {
    let fromData = {
      id: this.props.navigation.state.params.getData.id,
    };
    getStoreOrderInfo (fromData, responentData => {
      if (responentData['code'] == 1) {
        this.setState ({
          data: responentData['data']['order_list'][0],
        });
      }
    });
  }
  componentDidMount () {
    this._getStoreOrderInfo ();
  }
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        centerTitle={'订单详情'}
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });
  publicTitle (cont1, cont2, index) {
    return cont2 == '' || cont2 == null
      ? null
      : <View
          style={{
            flexDirection: 'row',
            height: 30,
            alignItems: 'center',
            justifyContent: 'space-between',
          }}
        >
          <Text
            style={{
              fontSize: 12,
              color: index ? '#F73B0A' : '#9B9B9B',
              marginLeft: 10,
            }}
          >
            {cont1}:
          </Text>
          <Text
            style={{
              fontSize: 12,
              color: index ? '#F73B0A' : '#4A4A4A',
              marginRight: 10,
            }}
          >
            {cont2}
          </Text>
        </View>;
  }
  publicTitle1 (cont1, cont2, index) {
    return cont2 == '+0.00'
      ? null
      : <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            marginRight: 14,
            justifyContent: 'space-between',
            paddingVertical: 5,
          }}
        >
          <Text style={{fontSize: 15, color: '#9B9B9B'}}>{cont1}</Text>
          <Text style={{fontSize: 15, color: '#f63300'}}>{cont2}</Text>
        </View>;
  }
  render () {
    if (this.state.data) {
      const getData = this.state.data;
      return (
        <View style={styles.container}>
          <ScrollView>
            <View
              style={{
                width: width,
                paddingLeft: 16,
                height: 94,
                flexDirection: 'row',
                backgroundColor: '#fff',
              }}
            >
              <View
                style={{
                  flex: 1,
                  height: 94,
                  flexDirection: 'row',
                  justifyContent: 'flex-start',
                  alignItems: 'center',
                }}
              >
                <Image
                  style={{width: screenWidth / 6, height: 67}}
                  source={{
                    uri: getData['store_img'][0] +
                      getData['store_img'][1] +
                      getData['store_img'][2] +
                      getData['store_img'][3],
                  }}
                />
              </View>

              <View
                style={{
                  flex: 3,
                  height: 94,
                  justifyContent: 'flex-start',
                  paddingTop: 13,
                  alignItems: 'flex-start',
                }}
              >
                <Text
                  numberOfLines={2}
                  style={{color: '#4a4a4a', fontSize: 14, marginLeft: 10}}
                >
                  {getData['store_name']}
                </Text>
              </View>

              <View style={{flex: 1, alignItems: 'flex-end', paddingRight: 16}}>
                <Text style={{color: '#4a4a4a', fontSize: 14, marginTop: 15}}>
                  {getData['voucher_price']}
                </Text>
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    textDecorationLine: 'line-through',
                  }}
                >
                  {getData['voucher_amount']}
                </Text>
                <Text style={{color: '#9B9B9B', fontSize: 14}}>X1</Text>
              </View>
            </View>

            <View
              style={{
                width: screenWidth,
                paddingLeft: 14,
                backgroundColor: '#fff',
              }}
            >

              {this.publicTitle1 (
                '新零售补贴',
                '+' + getData['coupons_price'],
                true
              )}
              {this.publicTitle1 (
                '用户抵用券',
                '+' + getData['user_voucher_price'],
                true
              )}

              <View
                style={{
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingVertical: 5,
                }}
              >
                <Text style={{fontSize: 15, color: '#9b9b9b'}}>用户实付:</Text>
                <Text style={{fontSize: 15, color: '#9b9b9b', marginRight: 14}}>
                  {parseFloat (Number (getData['buy_price'])).toFixed (2)}
                </Text>
              </View>

              <View
                style={{
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingVertical: 5,
                }}
              >
                <Text style={{fontSize: 15, color: 'black'}}>实收款</Text>
                <Text style={{fontSize: 15, color: '#f63300', marginRight: 14}}>
                  {parseFloat (Number (getData['voucher_price'])).toFixed (2)}
                </Text>
              </View>

            </View>

            <View
              style={{
                width: width - 28,
                backgroundColor: '#fff',
                marginLeft: 14,
                marginTop: 10,
                height: 100,
                justifyContent: 'center',
                alignItems: 'center',
              }}
            >
              <Text style={{fontSize: 12}}>
                使用时间:
                <Text style={{fontSize: 12}}>{getData['create_time']}</Text>
              </Text>
              <Text style={{fontSize: 12, marginTop: 10}}>
                券号:
                <Text
                  style={{fontSize: 12, textDecorationLine: 'line-through'}}
                >
                  {getData['voucher_sn']}
                </Text>
              </Text>
            </View>

            <View
              style={{width: width, backgroundColor: '#fff', marginTop: 10}}
            >
              {this.publicTitle ('交易号', getData['pay_sn'])}
              {this.publicTitle ('订单编号', getData['order_sn'])}
              {this.publicTitle ('下单时间', getData['create_time'])}
            </View>

          </ScrollView>
        </View>
      );
    } else {
      return (
        <View
          style={[
            styles.container,
            {alignItems: 'center', justifyContent: 'center'},
          ]}
        >
          <Text style={{fontSize: 15, color: 'black'}}>正在加载中....</Text>
        </View>
      );
    }
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: '#F6F6F6',
  },
});

function select (store) {
  return {
    username: store.userInfo.user_name,
  };
}
export default connect (select) (shopOrderDetail);
