/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */

import React, {Component} from 'react';
import {connect} from 'react-redux';
import {
  Platform,
  StyleSheet,
  Text,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  SafeAreaView,
} from 'react-native';
const width = Dimensions.get ('window').width;
import {orderState} from '../../redux/action/order_action';
import LinearGradient from 'react-native-linear-gradient';
class orderHeader extends Component {
  constructor (props) {
    super (props);
    this.titleArr = ['商品订单', '支付订单'];
    this.GoToBack = this.GoToBack.bind (this);
  }
  GoToBack () {
    this.props.backBtnOnPress ();
  }
  _onPressInc = index => {
    this.props.dispatch (orderState ('user_order', index));
    setTimeout (() => {
      this.props.getUpdata ();
    }, 500);
  };

  orderTitle (cont, index) {
    return (
      <TouchableOpacity
        key={index}
        style={{
          width: width / 4.52,
          height: 23,
          backgroundColor: this.props.orderIndex === index ? '#fff' : '#F73B0A',
          alignItems: 'center',
          justifyContent: 'center',
        }}
        onPress={() => {
          this._onPressInc (index);
        }}
      >
        <Text
          style={{color: this.props.orderIndex === index ? '#F73B0A' : '#fff'}}
        >
          {cont}
        </Text>
      </TouchableOpacity>
    );
  }
  componentDidMount () {}
  render () {
    return (
      <LinearGradient
        start={{x: 0.15, y: 0}}
        end={{x: 0.82, y: 0}}
        colors={['#F63300', '#F65D00']}
      >
        <SafeAreaView style={{}}>
          <View
            style={{
              width: width,
              flexDirection: 'row',
              alignItems: 'center',
              paddingBottom: 10,
              marginTop: Platform.OS !== 'ios' ? 10 : 0,
            }}
          >
            <TouchableOpacity
              onPress={() => {
                this.GoToBack ();
              }}
            >
              <Image
                source={require ('./src/back_white.png')}
                style={{
                  width: 20,
                  height: 20,
                  resizeMode: 'cover',
                  marginLeft: width / 30,
                }}
              />
            </TouchableOpacity>
            <View
              style={{
                height: 25,
                borderColor: '#fff',
                borderWidth: 1,
                borderRadius: 4,
                marginLeft: width / 5,
                flexDirection: 'row',
              }}
            >
              {this.titleArr.map ((cont, index) => {
                return this.orderTitle (cont, index);
              })}
            </View>
          </View>
        </SafeAreaView>
      </LinearGradient>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F5FCFF',
  },
});
function select (store) {
  return {orderIndex: store.orderInfo.orderState};
}
export default connect (select) (orderHeader);
