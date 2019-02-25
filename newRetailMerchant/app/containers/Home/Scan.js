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
  ScrollView,
  Alert,
} from 'react-native';
import Header from '../../components/Header/Header';
import Barcode from 'react-native-smart-barcode';
import {checkUsedVoucher} from '../../network/shopNetApi';
const width = Dimensions.get ('window').width;
const height = Dimensions.get ('window').height;
class Scan extends Component {
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        centerTitle={'扫一扫'}
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  constructor (props) {
    super (props);
    this.state = {
      viewAppear: false,
      code: null,
    };
    this._checkUsedVoucher = this._checkUsedVoucher.bind (this);
  }
  _checkUsedVoucher (cont) {
    let data = JSON.parse (cont);
    let fromData = {
      store_id: this.props.user.store_id,
      code: data['code'].toString (),
      sign: '',
    };
    checkUsedVoucher (fromData, respoentData => {
      if (respoentData['code'] == 1) {
        Alert.alert ('二维码核销', respoentData.msg, [
          {text: '确认', onPress: () => this._startScan ()},
        ]);
        // this._startScan ();
      } else {
        Alert.alert ('二维码', respoentData.msg, [
          {text: '确认', onPress: () => this._startScan ()},
        ]);
      }
    });
  }
  componentDidMount () {
    //启动定时器
    this.timer = setTimeout (() => this.setState ({viewAppear: true}), 250);
  }

  componentWillUnmount () {
    //清楚定时器
    this.timer && clearTimeout (this.timer);
  }

  _onBarCodeRead = e => {
    this._stopScan ();
    this._checkUsedVoucher (e.nativeEvent.data.code);
  };

  _startScan = e => {
    this._barCode.startScan ();
  };

  _stopScan = e => {
    this._barCode.stopScan ();
  };

  render () {
    return (
      <View style={{flex: 1}}>
        {this.state.viewAppear
          ? <Barcode
              style={{flex: 1}}
              ref={component => (this._barCode = component)}
              onBarCodeRead={this._onBarCodeRead}
            />
          : null}
        <View
          style={{
            width: width,
            justifyContent: 'center',
            alignItems: 'center',
            position: 'absolute',
            top: height / 1.5,
          }}
        >
          <Text style={{color: '#FFF', backgroundColor: '#000'}}>
            将二维码放入镜框中即可扫描
          </Text>
        </View>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  view_container: {
    flex: 1,
  },
});
function select (store) {
  return {
    user: store.userInfo,
  };
}
export default connect (select) (Scan);
