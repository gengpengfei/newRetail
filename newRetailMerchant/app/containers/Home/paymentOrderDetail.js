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
import {getStoreOrderInfo} from '../../network/shopNetApi';
import {configServerImagePath} from '../../utils/commonMethod';
import {isNotEmptyArray} from '../../utils/structureJudgment';
const width = Dimensions.get ('window').width;
class paymentOrderDetail extends Component {
  constructor (props) {
    super (props);
    this.state = {
      text: '',
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
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        centerTitle={'支付订单'}
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });
  publicTitle (cont1, cont2, index) {
    return cont2 == '-0.00'
      ? null
      : <View
          style={{
            flexDirection: 'row',
            height: 40,
            alignItems: 'center',
            width: width - 28,
            marginLeft: 14,
            justifyContent: 'space-between',
          }}
        >
          <Text style={{fontSize: 15, color: index ? '#F73B0A' : '#9B9B9B'}}>
            {cont1}
          </Text>
          <Text style={{fontSize: 15, color: index ? '#F73B0A' : '#4A4A4A'}}>
            {cont2}
          </Text>
        </View>;
  }
  componentDidMount () {
    this._getStoreOrderInfo ();
  }
  render () {
    if (this.state.data) {
      const getData = this.state.data;
      let allMoney = parseFloat (Number (getData['order_price'])).toFixed (2);
      let userMoney = getData['user_voucher_price'] == '0.00'
        ? parseFloat (
            Number (getData['order_price']) -
              Number (getData['user_voucher_price']) -
              Number (getData['discount_price'])
          ).toFixed (2)
        : parseFloat (
            Number (getData['order_price']) -
              Number (getData['user_voucher_price']) -
              Number (getData['discount_price'])
          ).toFixed (2);
      return (
        <View style={styles.container}>
          <View
            style={{
              width: width,
              paddingTop: 40,
              paddingBottom: 10,
              borderTopColor: '#ececec',
              borderTopWidth: 1,
              justifyContent: 'center',
              alignItems: 'center',
            }}
          >
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
              <Image
                source={{uri: configServerImagePath (getData['store_img'])}}
                style={{width: 40, height: 40, borderRadius: 20}}
              />
              <Text style={{fontSize: 16, color: '#4A4A4A', marginLeft: 10}}>
                {getData['store_name']}
              </Text>
            </View>
            {getData['user_voucher_price'] == '0.00'
              ? <Text style={{fontSize: 35, color: '#4A4A4A', marginTop: 10}}>
                  +{parseFloat (allMoney).toFixed (2)}
                </Text>
              : <Text style={{fontSize: 35, color: '#4A4A4A', marginTop: 10}}>
                  +
                  {parseFloat (
                    Number (getData['order_price']) -
                      Number (getData['user_voucher_price'])
                  ).toFixed (2)}
                </Text>}

            <Text style={{fontSize: 16, color: '#9B9B9B', marginTop: 10}}>
              交易成功
            </Text>
            {this.publicTitle ('订单金额', allMoney)}
            {this.publicTitle ('新零售优惠', '-' + getData['discount_price'], true)}
            {this.publicTitle (
              '店铺优惠',
              '-' + getData['user_voucher_price'],
              true
            )}
            {this.publicTitle ('用户实付', userMoney)}
            {this.publicTitle (
              '付款方式',
              getData['pay_type'] == 0
                ? '余额支付'
                : getData['pay_type'] == 1
                    ? '支付宝'
                    : getData['pay_type'] == 2 ? '微信支付' : '--'
            )}
            <View
              style={{width: width, height: 1, backgroundColor: '#ececec'}}
            />
            {this.publicTitle ('创建时间', getData['create_time'])}
            {this.publicTitle ('订单编号', getData['order_sn'])}
          </View>
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
export default connect (select) (paymentOrderDetail);
