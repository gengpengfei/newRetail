import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';

import Toast from 'react-native-simple-toast';
import {storeClearBillList} from '../../network/shopNetApi';
import {isNotEmptyArray} from '../../utils/structureJudgment';
import LinearGradient from 'react-native-linear-gradient';

class ReadyPay extends Component {
  constructor (props) {
    super (props);
    this.state = {
      index: 0,
      loading: false,
      payList: [],
      readyPayList: [],
      readyPayTotal: 0,
    };
    this._getDataList = this._getDataList.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="财务结款"
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._getDataList (0);
    this._getDataList (1);
  }

  _getDataList (pay_state) {
    let formData = {
      store_id: this.props.store_id,
      pay_state: pay_state,
    };

    storeClearBillList (formData, responseData => {
      let {msg, code = -1, data = []} = responseData;
      if (code === 1) {
        if (pay_state === 0) {
          this.setState ({
            readyPayList: data,
          });
        } else if (pay_state === 1) {
          this.setState ({
            payList: data,
          });
        }
        if (isNotEmptyArray (data)) {
          var totalMoney = 0.00;
          for (let i = 0; i < data.length; i++) {
            totalMoney = Number (totalMoney) + Number (data[i]['pay_price']);
          }
          this.setState ({
            readyPayTotal: totalMoney.toFixed (2),
          });
        }
      } else {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
  }

  _renderItem = (item, index) => {
    let clear_start_time = item.clear_start_time;
    let start_time = clear_start_time.split (' ');
    let clear_end_time = item.clear_end_time.split (' ');
    let pay_end_time = item.pay_end_time.split (' ');
    return (
      <TouchableOpacity
        onPress={() => {
          this._goToScreen ('CheckPayDetail', {id: item['id']});
        }}
      >
        <View
          style={{
            height: 68,
            paddingLeft: 14,
            alignItems: 'flex-start',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
            justifyContent: 'center',
          }}
        >
          <Text
            style={{
              color: '#4a4a4a',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            {start_time[0]} 至 {clear_end_time[0]}
          </Text>
          <View
            style={{flexDirection: 'row', marginTop: 5, alignItems: 'center'}}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 17,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
              }}
            >
              {item.pay_price}
            </Text>
            {this.state.index == 0
              ? <Text
                  style={{
                    color: '#F5A623',
                    fontSize: 12,
                    fontFamily: 'PingFangSC-Regular',
                    marginLeft: 21,
                  }}
                >
                  预计{pay_end_time[0]}
                </Text>
              : <Text
                  style={{
                    color: '#F5A623',
                    fontSize: 12,
                    fontFamily: 'PingFangSC-Regular',
                    marginLeft: 21,
                  }}
                >
                  {pay_end_time[0]}已打款
                </Text>}

          </View>

        </View>
      </TouchableOpacity>
    );
  };

  _createEmptyView () {
    return (
      <View style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
        <Text
          style={{
            color: '#4a4a4a',
            fontSize: 16,
            fontFamily: 'PingFangSC-Regular',
          }}
        >
          暂无信息！！
        </Text>
      </View>
    );
  }

  //标签的点击
  tabTitle = (cont, index) => {
    return (
      <TouchableOpacity
        key={index}
        onPress={() => {
          this.setState ({
            index: index,
          });
        }}
        style={
          this.state.index === index
            ? {
                borderBottomWidth: 2,
                borderBottomColor: '#f63300',
                justifyContent: 'center',
                alignItems: 'center',
              }
            : null
        }
      >
        <Text
          style={
            this.state.index === index ? {color: '#f63300'} : {color: '#4a4a4a'}
          }
        >
          {cont}
        </Text>
      </TouchableOpacity>
    );
  };

  _goToScreen = (str, params) => {
    const {navigate} = this.props.navigation;
    navigate (str, params);
  };

  render () {
    return (
      <View style={styles.container}>

        {/* <LinearGradient
                        start={{x:0.35,y:0}}
                        end={{x:0.82,y:0}}
                        colors={['#F73B0A', '#F55d00']} > */}
        <View
          style={{
            position: 'relative',
            backgroundColor: '#F55d00',
            height: 120,
            width: screenWidth,
          }}
        >
          <View
            style={{
              width: screenWidth,
              height: 37,
              flexDirection: 'row',
              justifyContent: 'center',
              paddingHorizontal: 16,
              alignItems: 'center',
              width: screenWidth,
            }}
          >
            <Text
              style={{
                color: '#fff',
                fontSize: 26,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              {this.state.readyPayTotal}
            </Text>

          </View>

          <View
            style={{
              width: screenWidth,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Text
              style={{
                color: '#fff',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              待结算金额
            </Text>

          </View>

          {/*<View style={{width:screenWidth,flexDirection:'row',alignItems:'center',justifyContent:'center',marginTop:10}}>*/}
          {/**/}
          {/*<TouchableOpacity onPress={()=>{*/}
          {/*this._goToScreen('FinalcialWithdraw');*/}
          {/*}}>*/}
          {/*<View style={{backgroundColor:'white',width:90,flexDirection:'row',alignItems:'center',justifyContent:'center',borderRadius:3,paddingVertical:3}}>*/}
          {/*<Text style={{color:'#f63300',fontSize:14,fontFamily:'PingFangSC-Regular'}}>提现</Text>*/}
          {/*</View>*/}
          {/*</TouchableOpacity>*/}
          {/*</View>*/}
        </View>

        {/* </LinearGradient> */}

        <View
          style={{
            position: 'absolute',
            top: 110,
            backgroundColor: 'white',
            width: screenWidth - 32,
            marginHorizontal: 16,
            flexDirection: 'row',
            height: 42,
            borderColor: '#e6e6e6',
            borderWidth: 1,
            justifyContent: 'space-around',
            flexDirection: 'row',
            borderRadius: 3,
            alignItems: 'center',
          }}
        >
          {['待结算账单', '已结算账单'].map ((cont, index) => {
            return this.tabTitle (cont, index);
          })}
        </View>
        {this.state.index === 0
          ? <FlatList
              data={this.state.readyPayList}
              renderItem={({item, index}) => this._renderItem (item, index)}
              ListEmptyComponent={this._createEmptyView ()}
              numColumns={1} // 设置列数
              keyExtractor={(item, index) => index.toString ()}
              refreshing={this.state.loading}
              onEndReachedThreshold={-0.05}
              onEndReached={info => {}}
              style={{backgroundColor: 'white', marginTop: 46}}
            />
          : <FlatList
              data={this.state.payList}
              renderItem={({item, index}) => this._renderItem (item, index)}
              ListEmptyComponent={this._createEmptyView ()}
              numColumns={1} // 设置列数
              keyExtractor={(item, index) => index.toString ()}
              refreshing={this.state.loading}
              onEndReachedThreshold={-0.05}
              onEndReached={info => {}}
              style={{backgroundColor: 'white', marginTop: 46}}
            />}

        {/*<TouchableOpacity onPress={()=>{*/}

        {/*this._goToScreen('CheckPayDetail','');*/}
        {/*if(isNotEmptyArray(this.state.payList)){*/}

        {/*this._goToScreen('CheckPayDetail', {id: this.state.payList[0]['id']});*/}
        {/*}*/}
        {/*}}>*/}
        {/*<View style={{marginTop:17,marginBottom:19,backgroundColor:'#f63300',marginHorizontal:16,borderRadius:3,flexDirection:'row',alignItems:'center',justifyContent:'center',width:screenWidth-32,paddingVertical:13}}>*/}
        {/*<Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>查看账单</Text>*/}
        {/*</View>*/}
        {/*</TouchableOpacity>*/}
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
    admin_id: store.userInfo.admin_id,
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (ReadyPay);
