import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
} from 'react-native';
import {connect} from 'react-redux';
import TopBackHeader from '../../components/Header/Header';
import {editStore} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
import DatePicker from 'react-native-datepicker';
class MineWorkTime extends Component {
  constructor (props) {
    super (props);

    this.state = {
      index: null,
      visible: false,
      start_time: '',
      end_time: '',
      visible1: false,
      checkedList: [],
      dateData: [
        {name: '周一', flag: false},
        {name: '周二', flag: false},
        {name: '周三', flag: false},
        {name: '周四', flag: false},
        {name: '周五', flag: false},
        {name: '周六', flag: false},
        {name: '周日', flag: false},
      ],
    };
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="营业时间"
        leftPress={() => {
          navigation.state.params.callBackData ();
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._checkedList ();
  }

  _checkedList = () => {
    // this.props.navigation.state.params.cont.store_hours//  传过来的时间是字符串
    if (this.props.navigation.state.params.cont.store_hours == null) {
      this.setState ({
        checkedList: [],
      });
    } else {
      let hourList = this.props.navigation.state.params.cont.store_hours.split (
        ' '
      );
      let arr = [];
      this.setState (
        {
          checkedList: hourList,
          start_time: hourList[hourList.length - 2],
          end_time: hourList[hourList.length - 1],
        },
        () => {
          for (let i = 0; i < this.state.dateData.length; i++) {
            for (let j = 0; j < hourList.length - 2; j++) {
              if (hourList[j] == this.state.dateData[i].name) {
                this.state.dateData[i]['flag'] = true;
              } else {
              }
            }
            arr.push (this.state.dateData[i]);
          }
          this.setState ({
            dateData: arr,
          });
        }
      );
    }
  };

  //生成想要的时间模式
  _formatDate (date) {
    const pad = n => (n < 10 ? `0${n}` : n);
    const timeStr = `${pad (date.getHours ())}:${pad (date.getMinutes ())}`;
    return `${timeStr}`;
  }

  //编辑店铺时间
  _editStore () {
    if (this.state.end_time === '' || this.state.start_time == '') {
      Toast.showWithGravity ('时间不能为空,请选择时间', 1, Toast.CENTER);
    } else {
      let str = this._configHoursData (this.state.dateData);
      let formData = {
        store_id: this.props.store_id,
        store_hours: str,
      };
      editStore (formData, response => {
        const {code = -1, msg = (''.data = null)} = response;
        if (code == -1) {
        } else {
          this.props.navigation.state.params.callBackData ();
          this.props.navigation.goBack ();
        }
      });
    }
  }

  //拼接字符串
  _configHoursData (dataArray) {
    let temp = '';
    for (let i = 0; i < dataArray.length; i++) {
      if (dataArray[i]['flag'] == true) {
        temp = temp + dataArray[i].name + ' ';
      }
    }
    temp = temp + this.state.start_time + ' ' + this.state.end_time;
    return temp;
  }

  _OClick = (flag, index) => {
    const GetJudge = !flag;
    this.state.dateData[index]['flag'] = GetJudge;
    this.setState ({
      dateData: this.state.dateData,
    });
  };

  _changeDate = () => {
    return this.state.dateData.map ((cont, index) => {
      return (
        <TouchableWithoutFeedback
          onPress={() => {
            this._OClick (cont.flag, index);
          }}
        >
          <View
            style={{
              flexDirection: 'row',
              justifyContent: 'flex-start',
              marginTop: 28,
              marginLeft: 7,
            }}
          >
            <Image
              style={{width: 18, height: 18}}
              source={
                cont.flag ? require ('./src/o1.png') : require ('./src/o2.png')
              }
            />
            <Text
              style={{
                color: '#4A4A4A',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              {cont.name}
            </Text>
          </View>
        </TouchableWithoutFeedback>
      );
    });
  };

  render () {
    return (
      <View style={styles.container}>
        <View style={{height: 141, width: screenWidth, flexDirection: 'row'}}>
          <View
            style={{
              flex: 2,
              flexDirection: 'row',
              justifyContent: 'center',
              marginLeft: 16,
            }}
          >
            <Text
              style={{
                color: '#4A4A4A',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                marginTop: 28,
              }}
            >
              选择周几：
            </Text>
          </View>
          <View style={{flex: 7, flexDirection: 'row', flexWrap: 'wrap'}}>
            {this._changeDate ()}
          </View>
        </View>

        <View
          style={{
            height: 32,
            alignItems: 'center',
            flexDirection: 'row',
            marginLeft: 16,
          }}
        >
          <Text
            style={{
              color: '#4A4A4A',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            选择开始时间：
          </Text>

          <DatePicker
            style={{width: 200}}
            mode="time"
            // format="HH:mm"
            confirmBtnText="Confirm"
            cancelBtnText="Cancel"
            onDateChange={date => {
              this.setState ({start_time: date});
            }}
            date={this.state.start_time}
          />

        </View>

        <View
          style={{
            height: 32,
            alignItems: 'center',
            flexDirection: 'row',
            marginLeft: 16,
            marginTop: 16,
          }}
        >
          <Text
            style={{
              color: '#4A4A4A',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            选择结束时间：
          </Text>

          <DatePicker
            style={{width: 200}}
            mode="time"
            //  format="HH:mm"
            confirmBtnText="Confirm"
            cancelBtnText="Cancel"
            onDateChange={date => {
              this.setState ({end_time: date});
            }}
            date={this.state.end_time}
          />

        </View>

        <TouchableWithoutFeedback
          onPress={() => {
            this._editStore ();
          }}
        >
          <View
            style={{
              marginTop: 61,
              backgroundColor: '#f63300',
              marginHorizontal: 16,
              borderRadius: 3,
              alignItems: 'center',
              justifyContent: 'center',
              paddingVertical: 13,
            }}
          >
            <Text
              style={{
                color: '#fff',
                fontSize: 16,
                fontFamily: 'PingFangSC-Regular',
              }}
            >
              保存
            </Text>
          </View>
        </TouchableWithoutFeedback>

        {/* <DatePicker
          visible={this.state.visible}
          onOk={date =>
            this.setState ({
              start_time: this._formatDate (date),
              visible: false,
            })}
          onDismiss={() => this.setState ({visible: false})}
          mode="time"
          format="HH:mm"
          title="开始时间"
        />

        <DatePicker
          visible={this.state.visible1}
          onOk={date =>
            this.setState ({
              end_time: this._formatDate (date),
              visible1: false,
            })}
          onDismiss={() => this.setState ({visible1: false})}
          mode="time"
          format="HH:mm"
          title="结束时间"
        /> */}

      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
});

function select (store) {
  return {
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (MineWorkTime);
