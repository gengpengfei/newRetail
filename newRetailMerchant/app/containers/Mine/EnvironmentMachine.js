import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  TextInput,
  Alert,
  ScrollView,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {editStore} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
import {isNotEmptyArray} from '../../utils/structureJudgment';
let arr = [];
class EnvironmentMachine extends Component {
  constructor (props) {
    super (props);
    this.state = {
      arr: [1],
      good: '',
      itemList: [],
      dataList: [],
      str: this.props.navigation.state.params.store_info,
    };
    this._deleteCont = this._deleteCont.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="环境配套设施"
        leftPress={() => {
          navigation.state.params.callBackData ();
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    //从店铺详情传入的配套设施字符串
    this._checkString (this.props.navigation.state.params.store_info);
  }

  //解析字符串 显示内容
  _checkString = str => {
    if (str == '' || str == null) {
    } else {
      let arr = str.split (',');
      this.setState ({
        itemList: arr,
      });
    }
  };

  _editStore () {
    let formData = {
      store_id: this.props.store_id,
      store_info: this.state.str,
    };

    if (!isNotEmptyArray (this.state.str.split (','))) {
      let arr = [];
      arr = this.state.str.split ('');
      if (arr.length > 7) {
        Toast.showWithGravity ('字数超过限制,请重新输入', 1, Toast.CENTER);
      } else {
        editStore (formData, response => {
          const {code = -1, msg = (''.data = null)} = response;
          if (code == -1) {
          } else {
            this.props.navigation.state.params.callBackData ();
            this.props.navigation.goBack ();
          }
        });
      }
    } else {
      let arr = this.state.str.split (',');
      if (arr.length > 8) {
        Toast.showWithGravity ('设施个数超过限制,请重新输入', 1, Toast.CENTER);
      } else {
        for (let a = 0; a < arr.length; a++) {
          if (arr[a].length > 7) {
            Toast.showWithGravity ('字数超过限制,请重新输入', 1, Toast.CENTER);
            return;
          } else {
          }
        }
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
  }

  _deleteCont (str, index) {
    let strr = '';
    for (let i = 0; i < this.state.itemList.length; i++) {
      if (str == this.state.itemList[index]) {
        this.state.itemList.splice (index, 1);
      }
    }
    for (let i = 0; i < this.state.itemList.length; i++) {
      if (i == this.state.itemList.length - 1) {
        strr = strr + this.state.itemList[i];
      } else {
        strr = strr + this.state.itemList[i] + ',';
      }
    }

    this.setState ({
      itemList: this.state.itemList,
      str: strr,
    });
  }

  _test = str => {
    if (!isNotEmptyArray (this.state.itemList)) {
      this.state.itemList.push (str);
    } else {
      this.state.itemList.splice (this.state.itemList.length - 1, 1);
      this.state.itemList.push (str);
      let result = [];
      let str1 = '';
      for (let i = 0; i < this.state.itemList.length; i++) {
        if (this.state.itemList[i] == '') {
        } else {
          result.push (this.state.itemList[i]);
          if (i == this.state.itemList.length - 1) {
            str1 = str1 + this.state.itemList[i];
          } else {
            str1 = str1 + this.state.itemList[i] + ',';
          }
        }
      }
      this.setState ({
        itemList: result,
        str: str1,
      });
    }
  };
  _itemInfo = () => {
    return (
      <View style={{width: screenWidth}}>
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'space-between',
            borderBottomColor: '#e6e6e6',
            borderBottomWidth: 1,
            height: 40,
          }}
        >
          <TextInput
            style={{
              color: '#9b9b9b',
              paddingLeft: 16,
              width: screenWidth - 46,
            }}
            underlineColorAndroid="transparent"
            placeholder={'请输入设施名称'}
            onChangeText={text => {
              this.setState (
                {
                  good: text,
                },
                () => {
                  this._test (text);
                }
              );
            }}
            value={this.state.good}
            maxLength={7}
          />
          <TouchableWithoutFeedback
            onPress={() => {
              this.state.itemList.push ('');
              this.setState ({
                good: '',
                itemList: this.state.itemList,
              });
            }}
          >
            <Image
              style={{width: 20, height: 20, marginRight: 12}}
              source={require ('./src/add.png')}
            />
          </TouchableWithoutFeedback>
        </View>
      </View>
    );
  };

  render () {
    return (
      <View style={styles.container}>
        {this._itemInfo ()}
        <View
          style={{
            width: screenWidth,
            height: 20,
            marginTop: 10,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text style={{color: '#9b9b9b', fontSize: 10}}>
            提示:每个配套设施最多七个字,最多八套设施
          </Text>
        </View>

        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            width: screenWidth,
            flexWrap: 'wrap',
          }}
        >
          {this.state.itemList.map ((cont, index) => {
            return (
              <View style={{}}>
                <TouchableWithoutFeedback
                  onPress={() => {
                    this._deleteCont (cont, index);
                  }}
                >
                  <Image
                    style={{
                      width: 20,
                      height: 20,
                      position: 'relative',
                      bottom: -18,
                      left: screenWidth / 3.5,
                    }}
                    source={require ('./src/cancel.png')}
                  />
                </TouchableWithoutFeedback>

                <View
                  style={{
                    width: screenWidth / 3.5,
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: defaultBackgroundColor.page_Background_Color,
                    paddingVertical: 10,
                    marginTop: 10,
                    marginLeft: 10,
                    height: 30,
                    borderRadius: 5,
                  }}
                >
                  <Text style={{fontSize: 14, color: '#9b9b9b'}}>{cont}</Text>
                </View>
              </View>
            );
          })}
        </View>
        <TouchableWithoutFeedback
          onPress={() => {
            this._editStore ();
          }}
        >
          <View
            style={{
              marginTop: 15,
              backgroundColor: '#f63300',
              marginHorizontal: 16,
              borderRadius: 3,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'center',
              width: screenWidth - 32,
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
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    // flex:1,
    backgroundColor: '#fff',
  },
});

function select (store) {
  return {
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (EnvironmentMachine);
