import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  FlatList,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';

import Toast from 'react-native-simple-toast';
import {storeClearBillInfo} from '../../network/shopNetApi';
import {isNotEmptyArray} from '../../utils/structureJudgment';

export default class CheckPayDetail extends Component {
  constructor (props) {
    super (props);
    this.state = {
      billInfo: [],
      billInfoList: [],
      billList: null,
      loading: false,
    };
    this._getDataList = this._getDataList.bind (this);
    this._renderItem = this._renderItem.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="查看账单"
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._getDataList ();
  }

  _getDataList () {
    let formData = {
      id: this.props.navigation.state.params.id,
      // id: '60'
    };
    storeClearBillInfo (formData, responseData => {
      let {msg, code = -1, data = []} = responseData;

      if (code == 1) {
        this.setState ({
          billList: data,
          billInfo: data['bill_info'],
          billInfoList: data['bill_day_list'],
        });
      } else {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
  }

  _renderItem (item) {
    return (
      <View
        style={{
          height: 258,
          flexDirection: 'row',
          alignItems: 'center',
          backgroundColor: 'white',
          marginBottom: 20,
        }}
      >
        <View style={{flex: 1, alignItems: 'center', paddingVertical: 10}}>
          {isNotEmptyArray (this.state.billInfoList)
            ? this.state.billInfoList.map ((itemData, index) => {
                let day_time = itemData.day_time.split (' ');
                return index === this.state.billInfoList.length - 1
                  ? <View
                      key={index}
                      style={{
                        paddingVertical: 10,
                        justifyContent: 'center',
                        alignItems: 'center',
                        flex: 1,
                      }}
                    >
                      <Text
                        style={{
                          color: '#9b9b9b',
                          fontSize: 14,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        {day_time[0]}
                        {' '}
                        <Text
                          style={{
                            color: '#9b9b9b',
                            fontSize: 14,
                            fontFamily: 'PingFangSC-Regular',
                          }}
                        >
                          {itemData.week}
                        </Text>
                      </Text>

                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 18,
                          fontFamily: 'PingFangSC-Regular',
                          marginTop: 10,
                        }}
                      >
                        {parseFloat (itemData.amount).toFixed (2)}
                      </Text>
                    </View>
                  : <View
                      key={index}
                      style={{
                        borderBottomColor: defaultBackgroundColor.page_Background_Color,
                        borderBottomWidth: 1,
                        paddingVertical: 10,
                        justifyContent: 'center',
                        alignItems: 'center',
                        flex: 1,
                      }}
                    >
                      <Text
                        style={{
                          color: '#9b9b9b',
                          fontSize: 14,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        {day_time[0]}
                        <Text
                          style={{
                            color: '#9b9b9b',
                            fontSize: 14,
                            fontFamily: 'PingFangSC-Regular',
                          }}
                        >
                          {itemData.week}
                        </Text>
                      </Text>

                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 18,
                          fontFamily: 'PingFangSC-Regular',
                          marginTop: 10,
                        }}
                      >
                        {parseFloat (itemData.amount).toFixed (2)}
                      </Text>
                    </View>;
              })
            : null}
        </View>
        <View
          style={{
            width: 1,
            height: 238,
            backgroundColor: defaultBackgroundColor.page_Background_Color,
            marginVertical: 10,
          }}
        />
        <View style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
          <Text
            style={{
              color: '#9b9b9b',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            {item['bill_info']['clear_start_time'].split (' ')[0]}
            至
            {item['bill_info']['clear_end_time'].split (' ')[0]}
          </Text>
          <Text
            style={{
              color: '#4a4a4a',
              fontSize: 18,
              fontFamily: 'PingFangSC-Regular',
              marginTop: 13,
            }}
          >
            {item['bill_info']['pay_price']}
          </Text>
          {item['bill_info']['pay_state'] === 1
            ? <Text
                style={{
                  color: '#F5A623',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  marginTop: 13,
                }}
              >
                已打款
              </Text>
            : <Text
                style={{
                  color: '#F5A623',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  marginTop: 13,
                }}
              >
                预计{item['bill_info']['pay_end_time'].split (' ')[0]}入账
              </Text>}
        </View>
      </View>
    );
  }

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
  render () {
    return (
      <View style={styles.container}>
        <View style={{height: 20}} />
        {this.state.billList !== null
          ? this._renderItem (this.state.billList)
          : null}
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
