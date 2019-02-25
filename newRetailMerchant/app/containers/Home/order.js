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
  FlatList,
  Modal,
} from 'react-native';
import {orderState} from '../../redux/action/order_action';
import {connect} from 'react-redux';
import Header from '../../components/Header/orderHeader';
import Toast from 'react-native-simple-toast';
import LinearGradient from 'react-native-linear-gradient';
import {storeOrderList} from '../../network/shopNetApi';
const width = Dimensions.get ('window').width;
import MyModal from './MyModal';
import {configServerImagePath} from '../../utils/commonMethod';
import {isNotEmptyArray} from '../../utils/structureJudgment';

class VerCode extends Component {
  constructor (props) {
    super (props);
    this.titleArr = ['今日', '最近7日', '自定义时间'];
    this.state = {
      text: '',
      refer: false,
      modalVisible: false,
      index: null,
      title: this.props.orderTimeIndex == 0 ||
        this.props.orderTimeIndex == null ||
        this.props.orderTimeIndex == undefined
        ? '今日'
        : this.props.orderTimeIndex == 1 ? '最近7日' : this.props.time,
      data: [],
      sevenTime: '',
      SpecificDate: new Date ().getFullYear () +
        '-' +
        (new Date ().getMonth () + 1) +
        '-' +
        new Date ().getDate (),
      currentTime: new Date ().getHours () +
        ':' +
        new Date ().getMinutes () +
        ':' +
        new Date ().getSeconds (),
      Zero: ' 0:00:00',
      endT: '',
      num: '',
      order_amount: '0',
      order_num: '0',
      index: 0,
    };
    this.upTime = this.upTime.bind (this);
    this.getStoreOrderList = this.getStoreOrderList.bind (this);
    this.upData = this.upData.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header //提供三个tab的标题
        showBack={true}
        backBtnOnPress={() => {
          navigation.goBack ();
        }}
        //头部标签的切换
        getUpdata={() => {
          navigation.state.params.navigatePress ();
        }}
      />
    ),
  });

  componentWillMount () {
    const date = new Date ();
    const newDate = new Date (date.getTime () - 6 * 24 * 60 * 60 * 1000);
    const time =
      newDate.getFullYear () +
      '-' +
      (newDate.getMonth () + 1) +
      '-' +
      newDate.getDate ();

    this.setState ({
      sevenTime: time + this.state.Zero,
    });
  }

  componentDidMount () {
    //todo 头部标签切换的时候进行数据的刷新
    this.props.navigation.setParams ({navigatePress: this.upData});
    this.getStoreOrderList (this.props.orderIndex, this.props.orderTimeIndex);
  }

  //头部切换进行的页面刷新
  upData () {
    this.setState ({
      data: [],
      title: this.props.orderTimeIndex == 0
        ? '今日'
        : this.props.orderTimeIndex == 1 ? '最近7日' : this.props.time, //选择时间的刷新
      order_amount: '0',
      order_num: '0',
      index: this.props.orderTimeIndex,
    });
    this.setModalVisible (false);
    console.log (
      '---头部切换---->>>>',
      this.props.orderIndex,
      this.props.orderTimeIndex
    );
    if (this.props.orderTimeIndex != 0 && this.props.orderTimeIndex != 1) {
      console.log (
        '---iiipppp',
        this.props.time.split ('至')[0],
        this.props.time.split ('至')[1]
      );
      this.getStoreOrderList (
        this.props.orderIndex,
        2,
        this.props.time.split ('至')[0] + ' ' + '0:00:00',
        this.props.time.split ('至')[1] + ' ' + '23:59:59'
      );
    } else {
      this.getStoreOrderList (this.props.orderIndex, this.props.orderTimeIndex);
    }
  }

  //自定义时间的数据
  upTime (cont1, cont2) {
    const startTime =
      new Date (cont1).getFullYear () +
      '-' +
      (new Date (cont1).getMonth () + 1) +
      '-' +
      new Date (cont1).getDate ();
    const endTime =
      new Date (cont2).getFullYear () +
      '-' +
      (new Date (cont2).getMonth () + 1) +
      '-' +
      new Date (cont2).getDate ();

    // console.log ('----startTIme----endTime---->>>>>', startTime, endTime);

    this.props.dispatch (orderState ('time_state', startTime + '至' + endTime));
    setTimeout (() => {
      this.setState ({
        title: this.props.time,
        endT: endTime + ' 23:59:59',
      });
    }, 500);
    if (cont1) {
      this.setState ({
        index: 2,
      });
    }
    // console.log('------自定义时间的----->>>>>',this.props.orderTimeIndex)
    this.getStoreOrderList (
      this.props.orderIndex,
      2,
      startTime + this.state.Zero,
      endTime + ' 23:59:59'
    );
  }

  /*oIndex是头部标签的下标 真好对应order_type 0是商品订单 1是支付订单
 * index 是选择时间的下标
*/
  getStoreOrderList (oIndex, index, cont1, cont2) {
    let fromData = {
      admin_id: this.props.user.admin_id,
      store_id: this.props.user.store_id,
      order_type: oIndex,
      start_time: index === null || index === 0
        ? this.state.SpecificDate + this.state.Zero
        : index === 1 ? this.state.sevenTime : index === 2 ? cont1 : null,
      end_time: index === 2
        ? cont2
        : this.state.SpecificDate +
            ' ' +
            new Date ().getHours () +
            ':' +
            new Date ().getMinutes () +
            ':' +
            new Date ().getSeconds (),
      sign: '',
    };

    // console.log ('-------nnn------>>>>', fromData);
    storeOrderList (fromData, responesdata => {
      // console.log ('----获取订单列表----->>>>', responesdata);
      if (responesdata['code'] === 1) {
        this.setState ({
          order_amount: responesdata['data']['order_amount'],
          order_num: responesdata['data']['order_num'],
          data: responesdata['data']['order_list'],
        });
      }
    });
  }
  upOrderList () {
    this.getStoreOrderList (this.props.orderIndex, this.props.orderTimeIndex);
  }

  shouldComponentUpdate (nextProps, nextState) {
    if (this.props.orderIndex != nextProps.orderIndex) {
      this.props.dispatch (orderState ('user_order', nextProps.orderIndex));
      //    this.getStoreOrderList (nextProps.orderIndex, null);
      return false;
    } else {
      return true;
    }
  }
  _renderItemView1 = (item, index) => {
    if (this.props.orderIndex === 0) {
      return (
        <TouchableOpacity
          onPress={() => {
            const {navigate} = this.props.navigation;
            navigate ('ShopOrderDetail', {getData: item.item});
          }}
          style={{
            width: width - 50,
            marginLeft: 25,
            alignItems: 'center',
            paddingBottom: 10,
            paddingTop: 10,
            justifyContent: 'space-between',
            borderBottomColor: '#F6F6F6',
            borderBottomWidth: 1,
          }}
        >
          <View
            style={{
              width: width - 50,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
            }}
          >
            <View style={{flexDirection: 'row', alignItems: 'center'}}>

              <Image
                source={
                  isNotEmptyArray (item.item.head_img)
                    ? {uri: configServerImagePath (item.item.head_img)}
                    : require ('./src/defaultHead.png')
                }
                style={{width: 40, height: 40, borderRadius: 20}}
              />
              <View style={{marginLeft: 10}}>

                <Text style={{fontSize: 12, color: '#4A4A4A'}}>
                  {item.item['user_name']}
                </Text>
                <Text style={{fontSize: 10, color: '#4A4A4A', marginTop: 10}}>
                  {item.item.create_time}
                </Text>
              </View>
            </View>
            <View style={{}}>
              <Text style={{fontSize: 18, color: '#F63300'}}>
                +
                {parseFloat (
                  Number (item.item.clear_price) -
                    Number (item.item.user_voucher_price)
                ).toFixed (2)}
              </Text>
              <Text style={{color: '#9b9b9b', fontSize: 14, marginTop: 5}}>
                已使用1份
              </Text>
            </View>
          </View>
          <View
            style={{
              width: width - 50,
              flexDirection: 'row',
              marginTop: 10,
              alignItems: 'center',
              justifyContent: 'space-between',
            }}
          >
            <Text style={{fontSize: 14, color: '#4a4a4a'}}>
              {item.item['voucher_name']}
            </Text>
            <Text style={{fontSize: 12, color: '#8F8E94'}} />
          </View>
        </TouchableOpacity>
      );
    } else {
      return (
        <TouchableOpacity
          onPress={() => {
            const {navigate} = this.props.navigation;
            navigate ('PaymentOrderDetail', {getData: item.item});
          }}
          style={{
            width: width - 50,
            marginLeft: 25,
            flexDirection: 'row',
            alignItems: 'center',
            paddingBottom: 10,
            paddingTop: 10,
            justifyContent: 'space-between',
            borderBottomColor: '#F6F6F6',
            borderBottomWidth: 1,
          }}
        >
          <View style={{flexDirection: 'row', alignItems: 'center'}}>
            {isNotEmptyArray (item.item.head_img)
              ? <Image
                  source={{uri: configServerImagePath (item.item.head_img)}}
                  style={{width: 40, height: 40, borderRadius: 20}}
                />
              : <Image
                  source={require ('./src/defaultHead.png')}
                  style={{width: 40, height: 40, borderRadius: 20}}
                />}
            <View style={{marginLeft: 10}}>
              <Text style={{fontSize: 12, color: '#4A4A4A'}}>
                {item.item['user_name']}
              </Text>
              <Text style={{fontSize: 10, color: '#4A4A4A', marginTop: 10}}>
                {item.item.create_time}
              </Text>
            </View>
          </View>
          <Text style={{fontSize: 18, color: '#F63300'}}>
            +
            {Number (item.item.order_price) -
              Number (item.item.user_voucher_price) >=
              0
              ? parseFloat (
                  Number (item.item.order_price) -
                    Number (item.item.user_voucher_price)
                ).toFixed (2)
              : 0.00}
          </Text>
        </TouchableOpacity>
      );
    }
  };
  createEmptyView () {
    return (
      <Text style={{fontSize: 14, alignSelf: 'center', marginTop: 100}}>
        暂无订单!
      </Text>
    );
  }
  _keyExtractor1 = (item, index) => index.toString ();

  setModalVisible (visible) {
    if (visible) {
      this.myModal.open ();
    } else {
      this.myModal.close ();
    }
    this.setState ({modalVisible: visible});
  }
  render () {
    return (
      <View style={styles.container}>
        <LinearGradient
          start={{x: 0.2, y: 0}}
          end={{x: 0.82, y: 0}}
          colors={['#F73B0A', '#F55d00']}
          style={{
            height: 150,
            justifyContent: 'center',
            alignItems: 'center',
            position: 'relative',
          }}
        >
          <TouchableOpacity
            style={{position: 'absolute', top: 20, left: 20}}
            onPress={() => {
              this.setModalVisible (!this.state.modalVisible);
            }}
          >
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
              <Text
                style={{
                  fontSize: 15,
                  fontWeight: 'bold',
                  color: '#fff',
                  backgroundColor: 'transparent',
                }}
              >
                {this.state.title}
              </Text>
              <Image
                source={
                  !this.state.modalVisible
                    ? require ('./src/ss.png')
                    : require ('./src/sx.png')
                }
                style={{width: 10, height: 10}}
              />
            </View>
          </TouchableOpacity>
          <View style={{alignItems: 'center'}}>
            <Text
              style={{
                fontSize: 30,
                color: '#fff',
                backgroundColor: 'transparent',
              }}
            >
              {parseFloat (this.state.order_amount).toFixed (2)}
            </Text>
            <Text
              style={{
                fontSize: 16,
                color: '#fff',
                marginTop: 10,
                backgroundColor: 'transparent',
              }}
            >
              共计{this.state.order_num}笔
            </Text>
          </View>
        </LinearGradient>
        <FlatList
          ref={flatList => (this._flatList = flatList)}
          data={this.state.data}
          renderItem={this._renderItemView1}
          ListEmptyComponent={this.createEmptyView ()}
          numColumns={1}
          keyExtractor={this._keyExtractor1}
          refreshing={this.state.refer}
          // onRefresh={this._onRefresh1}
          onEndReachedThreshold={-0.05}
          onEndReached={info => {}}
          extraData={this.state}
        />

        <MyModal ref={myModal => (this.myModal = myModal)} topHeight={45}>
          {this.titleArr.map ((cont, index) => {
            return (
              <View
                key={index}
                style={{
                  width: '100%',
                  paddingLeft: 20,
                  backgroundColor: '#fff',
                  height: 40,
                  borderBottomColor: this.state.index === index
                    ? '#F63300'
                    : '#F6F6F6',
                  borderBottomWidth: 1,
                  justifyContent: 'center',
                }}
              >
                <TouchableOpacity
                  onPress={() => {
                    if (index !== 2) {
                      this.setState ({
                        index: index,
                        title: cont,
                      });

                      //每次选择时间刷新的时候都要记住选择的选择的时间
                      this.props.dispatch (orderState ('order_index', index));
                      setTimeout (() => {
                        this.upOrderList ();
                      }, 500);
                    }
                    if (index == 2) {
                      this.props.dispatch (orderState ('order_index', 2));
                      const {navigate} = this.props.navigation;
                      navigate ('TimeSelection', {upTime: this.upTime});
                    }
                    this.setModalVisible (!this.state.modalVisible);
                  }}
                >
                  <Text style={{width: width - 50}}>{cont}</Text>
                </TouchableOpacity>
              </View>
            );
          })}
        </MyModal>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: '#fbfbfb',
  },
});

function select (store) {
  return {
    user: store.userInfo,
    orderIndex: store.orderInfo.orderState,
    orderTimeIndex: store.orderInfo.indexState,
    time: store.orderInfo.timeState,
  };
}
export default connect (select) (VerCode);
