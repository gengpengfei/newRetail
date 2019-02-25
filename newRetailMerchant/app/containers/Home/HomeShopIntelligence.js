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
  NativeModules,
  Keyboard,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {sendMobileCode, quickLogin} from '../../network/loginNetApi';
import RegExpTool from '../../utils/RegExpTool';
var ImagePicker = NativeModules.ImageCropPicker;
import Toast from 'react-native-simple-toast';
import TimerButton from '../../components/TimerButton';
import {
  setStoreAudit,
  getUploadImg,
  updateStoreAudit,
} from '../../network/shopNetApi';
import {isNotEmptyArray} from '../../utils/structureJudgment';
class HomeShopIntelligence extends Component {
  constructor (props) {
    super (props);

    this.imgArr = [];
    this.imgArr1 = [];
    this.imgArr2 = [];
    this.state = {
      loading: false,
      phoneNum: this.props.user_mobile,
      moneyCard: '',
      beginBank: '',
      verifyCode: '',
      index: 0,
      audit_identity_face: '',
      audit_identity_coin: '',
      audit_license: '',
      pushBtnText: '提交审核',
      btnActive: true,
      ID_name: '',
      ID_number: '',
      business_license_name: '',
      business_license_number: '',
      real_name: '',
      business_license_time: '',
      images: null,
      images2: null,
      images3: null,
    };
    this._getData = this._getData.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="提交资质"
        leftPress={() => {
          navigation.state.params.callBackData ();
          navigation.goBack ();
        }}
      />
    ),
  });

  //请求验证码
  _clickGetPhoneReqButton () {
    let formData = {
      mobile: this.state.phoneNum,
      code_type: '10',
    };

    sendMobileCode (formData, responseData => {
      const {code, msg} = responseData;
      if (code === 1) {
        let codeData = responseData['data'];
        this.setState ({
          receiveCode: codeData['code'],
        });
      }
      Toast.showWithGravity (msg, 1, Toast.CENTER);
    });
  }

  _setStoreAudit () {
    this.state.images && this.state.images2 && this.state.images3
      ? this._getUploadImg ()
      : Toast.showWithGravity ('图片上传不完整,请重新上传', 1, Toast.CENTER);
  }

  //选择本地相册中的图片
  pickMultiple = str => {
    ImagePicker.openPicker ({
      multiple: true,
      waitAnimationEnd: false,
      includeExif: true,
      includeBase64: true,
      compressImageQuality: 0.2,
      // maxFiles:3
    })
      .then (images => {
        if (str == 1) {
          this.setState ({
            image: null,
            images: images.map ((i, index) => {
              if (index <= 0) {
                return {uri: i.path, data: 'data:image/png;base64,' + i.data};
              } else {
                return null;
              }
            }),
          });

          if (this.state.images) {
            this.state.images.map ((cont, index) => {
              if (cont) {
                this.imgArr[index] = cont['data'];
              }
            });
          }
        } else if (str == 2) {
          this.setState ({
            image: null,
            images2: images.map ((i, index) => {
              if (index <= 0) {
                return {uri: i.path, data: 'data:image/png;base64,' + i.data};
              } else {
                return null;
              }
            }),
          });

          if (this.state.images2) {
            this.state.images2.map ((cont, index) => {
              if (cont) {
                this.imgArr1[index] = cont['data'];
              }
            });
          }
        } else {
          this.setState ({
            image: null,
            images3: images.map ((i, index) => {
              if (index <= 0) {
                return {uri: i.path, data: 'data:image/png;base64,' + i.data};
              } else {
                return null;
              }
            }),
          });

          if (this.state.images3) {
            this.state.images3.map ((cont, index) => {
              if (cont) {
                this.imgArr2[index] = cont['data'];
              }
            });
          }
        }
      })
      .catch ();
  };

  //上传图片
  _getUploadImg () {
    this.setState ({pushBtnText: '正在提交', btnActive: false});
    let formData = {
      type: 'storeAudit',
      img_base64: this.imgArr,
      store_id: this.props.navigation.state.params.store_id,
    };

    getUploadImg (formData, responseData => {
      if (responseData['code'] === 1) {
        this.setState (
          {
            audit_identity_face: responseData.data,
          },
          () => {
            this._getUploadImg1 ();
          }
        );
      } else {
        this.setState ({pushBtnText: '提交审核', btnActive: true});
        Toast.showWithGravity (responseData['msg'], 1, Toast.CENTER);
      }
    });
  }

  //上传图片
  _getUploadImg1 () {
    let formData = {
      type: 'storeAudit',
      img_base64: this.imgArr1,
      store_id: this.props.navigation.state.params.store_id,
    };
    getUploadImg (formData, responseData => {
      if (responseData['code'] === 1) {
        this.setState (
          {
            audit_identity_coin: responseData.data,
          },
          () => {
            this._getUploadImg2 ();
          }
        );
      } else {
        this.setState ({pushBtnText: '提交审核', btnActive: true});
        Toast.showWithGravity (responseData['msg'], 1, Toast.CENTER);
      }
    });
  }
  _editStoreAudit = () => {
    let formData = null;
    if (this.state.index == 1) {
      formData = {
        store_id: this.props.navigation.state.params.store_id,
        admin_id: this.props.uuid,
        audit_mobile: this.state.phoneNum,
        audit_identity_face: encodeURI (this.state.audit_identity_face),
        audit_identity_coin: encodeURI (this.state.audit_identity_coin),
        audit_bank: this.state.beginBank,
        audit_bank_card: this.state.moneyCard,
        code: this.state.verifyCode,
        code_type: '10',
        ID_name: this.state.ID_name,
        ID_number: this.state.ID_number,
        is_license: 0,
        temp_license: encodeURI (this.state.audit_license),
      };
    } else {
      formData = {
        store_id: this.props.navigation.state.params.store_id,
        admin_id: this.props.uuid,
        audit_mobile: this.state.phoneNum,
        audit_identity_face: encodeURI (this.state.audit_identity_face),
        audit_identity_coin: encodeURI (this.state.audit_identity_coin),
        audit_license: encodeURI (this.state.audit_license),
        audit_bank: this.state.beginBank,
        audit_bank_card: this.state.moneyCard,
        code: this.state.verifyCode,
        code_type: '10',
        ID_name: this.state.ID_name,
        ID_number: this.state.ID_number,
        business_license_name: this.state.business_license_name,
        business_license_number: this.state.business_license_number,
        real_name: this.state.real_name,
        business_license_time: this.state.business_license_time,
        is_license: 1, //0五营业执照 1是有营业执照
      };
    }
    this._pushExamine (formData);
  };

  _pushExamine = obj => {
    let isPhoneNum = RegExpTool.phoneByReg (this.state.phoneNum);
    let isBankCardNum = RegExpTool.bankCardNumRequire (this.state.moneyCard);
    let isCardNum = RegExpTool.idCardNoRequire (this.state.ID_number);
    if (this.state.index == 1) {
      if (!isPhoneNum['check']) {
        this.setState ({pushBtnText: '提交审核', btnActive: true});
        Toast.showWithGravity (isPhoneNum['error'], 1, Toast.CENTER);
      } else if (
        this.state.audit_identity_face == '' ||
        this.state.audit_identity_coin == '' ||
        this.state.audit_license == '' ||
        this.state.verifyCode == '' ||
        this.state.audit_bank_card == '' ||
        this.state.ID_name == '' ||
        this.state.beginBank == ''
      ) {
        Toast.showWithGravity ('数据为空,请重新输入', 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else if (!isBankCardNum['check']) {
        Toast.showWithGravity (isBankCardNum['error'], 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else if (!isCardNum['check']) {
        Toast.showWithGravity (isCardNum['error'], 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else {
        setStoreAudit (obj, response => {
          const {code = -1, msg = '', data = []} = response;
          if (code == -1) {
            Toast.showWithGravity (msg, 1, Toast.CENTER);
          } else {
            Toast.showWithGravity (msg, 1, Toast.CENTER);
            this.props.navigation.state.params.callBackData ();
            if (this.props.navigation.state.params.goBackKey) {
              this.props.navigation.goBack (
                this.props.navigation.state.params.goBackKey
              );
            } else {
              this.props.navigation.goBack ();
            }
          }
        });
      }
    } else {
      if (!isPhoneNum['check']) {
        this.setState ({pushBtnText: '提交审核', btnActive: true});
        Toast.showWithGravity (isPhoneNum['error'], 1, Toast.CENTER);
      } else if (
        this.state.audit_identity_face == '' ||
        this.state.audit_identity_coin == '' ||
        this.state.audit_license == '' ||
        this.state.verifyCode == '' ||
        this.state.audit_bank_card == '' ||
        this.state.real_name == '' ||
        this.state.ID_name == '' ||
        this.state.business_license_name == '' ||
        this.state.business_license_number == '' ||
        this.state.business_license_time == '' ||
        this.state.beginBank == ''
      ) {
        Toast.showWithGravity ('数据为空,请重新输入', 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else if (!isBankCardNum['check']) {
        Toast.showWithGravity (isBankCardNum['error'], 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else if (!isCardNum['check']) {
        Toast.showWithGravity (isCardNum['error'], 1, Toast.CENTER);
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      } else {
        setStoreAudit (obj, response => {
          const {code = -1, msg = '', data = []} = response;
          if (code == -1) {
            Toast.showWithGravity (msg, 1, Toast.CENTER);
          } else {
            Toast.showWithGravity (msg, 1, Toast.CENTER);
            this.props.navigation.state.params.callBackData ();
            if (this.props.navigation.state.params.goBackKey) {
              this.props.navigation.goBack (
                this.props.navigation.state.params.goBackKey
              );
            } else {
              this.props.navigation.goBack ();
            }
          }
        });
      }
    }
  };
  //上传图片
  _getUploadImg2 () {
    let formData = {
      type: 'storeAudit',
      img_base64: this.imgArr2,
      store_id: this.props.navigation.state.params.store_id,
    };
    getUploadImg (formData, responseData => {
      if (responseData['code'] === 1) {
        this.setState (
          {
            audit_license: responseData['data'],
          },
          () => {
            this._editStoreAudit ();
          }
        );
      } else {
        this.setState ({pushBtnText: '提交审核', btnActive: true});
      }
    });
  }

  _updateStoreAudit () {
    this._getUploadImg ();
  }

  _getData () {}
  render () {
    return (
      <View style={{flex: 1, backgroundColor: 'white'}}>
        <ScrollView style={styles.container}>
          <View
            style={{
              marginTop: 16,
              marginHorizontal: 15,
              width: screenWidth - 30,
              height: 74,
              backgroundColor: 'white',
              flexDirection: 'row',
              justifyContent: 'space-between',
              paddingHorizontal: 11,
              alignItems: 'center',
            }}
          >
            <View style={{flex: 5}}>
              <Text
                style={{
                  color: '#4a4a4a',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  fontWeight: 'bold',
                }}
                numberOfLines={1}
              >
                {this.props.navigation.state.params.store_name}
              </Text>
              <Text
                style={{
                  color: '#9b9b9b',
                  fontSize: 12,
                  fontFamily: 'PingFangSC-Regular',
                  marginTop: 5,
                }}
                numberOfLines={1}
              >
                {this.props.navigation.state.params.store_address}
              </Text>
            </View>

            <TouchableOpacity
              onPress={() => {
                const {navigate} = this.props.navigation;
                navigate ('ConfirmShop', {callBackData: this._getData});
              }}
              style={{
                flex: 1.5,
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'flex-end',
              }}
            >
              <Text
                style={{
                  color: '#f63300',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  marginTop: 2,
                }}
              >
                重新认领
              </Text>
            </TouchableOpacity>
          </View>

          <View
            style={{
              marginTop: 11,
              marginHorizontal: 15,
              width: screenWidth - 30,
              backgroundColor: 'white',
              paddingHorizontal: 11,
              paddingVertical: 12,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
              }}
            >
              身份证绑定
            </Text>
            <Text
              style={{
                color: '#9b9b9b',
                fontSize: 12,
                fontFamily: 'PingFangSC-Regular',
                marginTop: 3,
              }}
            >
              请上传经营者身份证
            </Text>
            <View
              style={{
                flexDirection: 'row',
                marginTop: 13,
                justifyContent: 'space-between',
                height: 90,
                width: screenWidth - 30 - 22,
              }}
            >
              {this.state.images
                ? <View
                    style={{
                      backgroundColor: defaultBackgroundColor.page_Background_Color,
                      width: (screenWidth - 30 - 22 - 13) / 2,
                      justifyContent: 'center',
                      alignItems: 'center',
                    }}
                  >
                    <Image
                      source={this.state.images[0]}
                      style={{
                        width: (screenWidth - 30 - 22 - 13) / 2 - 20,
                        height: 70,
                      }}
                    />
                  </View>
                : <TouchableOpacity
                    onPress={() => {
                      this.pickMultiple (1);
                    }}
                    style={{
                      backgroundColor: defaultBackgroundColor.page_Background_Color,
                      width: (screenWidth - 30 - 22 - 13) / 2,
                      justifyContent: 'center',
                      alignItems: 'center',
                    }}
                  >
                    <View style={{alignItems: 'center'}}>
                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 12,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        +添加
                      </Text>
                      <Text
                        style={{
                          color: '#9b9b9b',
                          fontSize: 10,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        身份证正面照
                      </Text>
                    </View>
                  </TouchableOpacity>}
              {this.state.images2
                ? <View
                    style={{
                      backgroundColor: defaultBackgroundColor.page_Background_Color,
                      width: (screenWidth - 30 - 22 - 13) / 2,
                      justifyContent: 'center',
                      alignItems: 'center',
                    }}
                  >
                    <Image
                      source={this.state.images2[0]}
                      style={{
                        width: (screenWidth - 30 - 22 - 13) / 2 - 20,
                        height: 70,
                      }}
                    />
                  </View>
                : <TouchableOpacity
                    onPress={() => {
                      this.pickMultiple (2);
                    }}
                    style={{
                      backgroundColor: defaultBackgroundColor.page_Background_Color,
                      width: (screenWidth - 30 - 22 - 13) / 2,
                      justifyContent: 'center',
                      alignItems: 'center',
                    }}
                  >
                    <View style={{alignItems: 'center'}}>
                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 12,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        +添加
                      </Text>
                      <Text
                        style={{
                          color: '#9b9b9b',
                          fontSize: 10,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        身份证反面照
                      </Text>
                    </View>
                  </TouchableOpacity>}
            </View>

            {this.state.images || this.state.images2
              ? <View
                  style={{
                    borderBottomColor: defaultBackgroundColor.page_Background_Color,
                    borderBottomWidth: 1,
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                    marginTop: 10,
                  }}
                >
                  <View stylew={{flex: 1, justifyContent: 'center'}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                      }}
                    >
                      姓名:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入姓名"
                    underlineColorAndroid="transparent"
                    // keyboardType="numeric"
                    style={{
                      fontSize: 14,
                      color: '#9b9b9b',
                      flex: 4,
                      paddingLeft: 40,
                    }}
                    onChangeText={text => {
                      this.setState ({
                        ID_name: text,
                      });
                    }}
                  />
                </View>
              : null}

            {this.state.images || this.state.images2
              ? <View
                  style={{
                    borderBottomColor: defaultBackgroundColor.page_Background_Color,
                    borderBottomWidth: 1,
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                  }}
                >
                  <View stylew={{flex: 1, justifyContent: 'center'}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                      }}
                    >
                      身份证号:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入身份证号"
                    underlineColorAndroid="transparent"
                    style={{
                      fontSize: 14,
                      color: '#9b9b9b',
                      flex: 4,
                      paddingLeft: 12,
                    }}
                    onChangeText={text => {
                      this.setState ({
                        ID_number: text,
                      });
                    }}
                  />
                </View>
              : null}
          </View>

          <View
            style={{
              marginTop: 11,
              marginHorizontal: 15,
              width: screenWidth - 30,
              height: 193,
              backgroundColor: 'white',
              paddingTop: 12,
            }}
          >
            <Text
              style={{
                color: '#4a4a4a',
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                fontWeight: 'bold',
                marginLeft: 11,
              }}
            >
              银行卡绑定
            </Text>
            <View
              style={{
                borderBottomColor: defaultBackgroundColor.page_Background_Color,
                borderBottomWidth: 1,
                alignItems: 'center',
                width: screenWidth - 30,
                flexDirection: 'row',
                backgroundColor: '#ffffff',
                height: 40,
              }}
            >
              <View stylew={{flex: 1, justifyContent: 'center'}}>
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                    paddingLeft: 11,
                  }}
                >
                  银行卡号:
                </Text>
              </View>
              <TextInput
                multiline={true}
                placeholder="经营者银行卡（仅储蓄卡）"
                underlineColorAndroid="transparent"
                keyboardType="numeric"
                style={{
                  fontSize: 14,
                  color: '#9b9b9b',
                  flex: 4,
                  paddingLeft: 12,
                }}
                onChangeText={text => {
                  this.setState ({
                    moneyCard: text,
                  });
                }}
              />
            </View>
            <View
              style={{
                borderBottomColor: defaultBackgroundColor.page_Background_Color,
                borderBottomWidth: 1,
                alignItems: 'center',
                width: screenWidth - 30,
                flexDirection: 'row',
                backgroundColor: '#ffffff',
                height: 40,
              }}
            >
              <View stylew={{flex: 1, justifyContent: 'center'}}>
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                    paddingLeft: 11,
                  }}
                >
                  开户银行:
                </Text>
              </View>
              <TextInput
                multiline={true}
                placeholder="经营者开户银行（仅储蓄卡））"
                underlineColorAndroid="transparent"
                style={{
                  fontSize: 14,
                  color: '#9b9b9b',
                  flex: 4,
                  paddingLeft: 12,
                }}
                onChangeText={text => {
                  this.setState ({
                    beginBank: text,
                  });
                }}
              />

            </View>

            <View
              style={{
                borderBottomColor: defaultBackgroundColor.page_Background_Color,
                borderBottomWidth: 1,
                alignItems: 'center',
                width: screenWidth - 30,
                flexDirection: 'row',
                backgroundColor: '#ffffff',
                height: 40,
              }}
            >
              <View stylew={{justifyContent: 'center', flex: 1}}>
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                    paddingLeft: 11,
                  }}
                >
                  手机号码:
                </Text>
              </View>
              <TextInput
                multiline={true}
                placeholder="手机号码"
                underlineColorAndroid="transparent"
                keyboardType="numeric"
                value={this.props.user_mobile}
                style={{
                  fontSize: 14,
                  color: '#9b9b9b',
                  paddingLeft: 11,
                  flex: 1,
                }}
                // onChangeText={(text)=>{
                //     this.setState({
                //         phoneNum:text
                //     })
                // }}
              />

              <TimerButton
                style={{
                  justifyContent: 'center',
                  flex: 1,
                  backgroundColor: '#f63300',
                  alignItems: 'center',
                  height: 40,
                }}
                textStyle={{color: '#fff'}}
                timerCount={60}
                disableColor="#D2D2D2"
                enable={true} //按钮可点击？
                onClick={shouldStartCountting => {
                  let isTelpNum = RegExpTool.phoneByReg (this.state.phoneNum);
                  if (!isTelpNum['check']) {
                    //如果校验失败，不能开始倒计时
                    shouldStartCountting (false);
                    Toast.showWithGravity (isTelpNum['error'], 1, Toast.CENTER);
                  } else {
                    shouldStartCountting (true);
                    //请求接口，获取验证码
                    this._clickGetPhoneReqButton ();
                  }
                }}
              >
                <Text
                  style={{
                    color: '#fff',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  验证码
                </Text>
              </TimerButton>

            </View>

            <View
              style={{
                alignItems: 'center',
                width: screenWidth - 30,
                flexDirection: 'row',
                backgroundColor: '#ffffff',
                height: 40,
              }}
            >
              <View stylew={{flex: 1, justifyContent: 'center'}}>
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                    paddingLeft: 11,
                  }}
                >
                  验证码:
                </Text>
              </View>
              <TextInput
                multiline={true}
                placeholder="请输入验证码"
                underlineColorAndroid="transparent"
                keyboardType="numeric"
                style={{
                  fontSize: 14,
                  color: '#9b9b9b',
                  flex: 4,
                  paddingLeft: 26,
                }}
                onChangeText={text => {
                  this.setState ({
                    verifyCode: text,
                  });
                }}
              />
            </View>

          </View>

          <View
            style={{
              marginTop: 11,
              marginHorizontal: 15,
              width: screenWidth - 30,
              height: 281,
              backgroundColor: 'white',
              paddingHorizontal: 11,
              paddingTop: 14,
            }}
          >
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
              {['上传执照', '正在办理'].map ((cont, index) => {
                return (
                  <TouchableOpacity
                    key={index}
                    onPress={() => {
                      this.setState ({
                        index: index,
                      });
                    }}
                  >
                    <View
                      style={{
                        flexDirection: 'row',
                        alignItems: 'center',
                        marginLeft: 10,
                      }}
                    >
                      <Image
                        style={{width: 12, height: 12}}
                        source={
                          this.state.index == index
                            ? require ('../Mine/src/o1.png')
                            : require ('../Mine/src/o2.png')
                        }
                      />
                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 14,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        {cont}
                      </Text>
                    </View>
                  </TouchableOpacity>
                );
              })}
            </View>

            {this.state.images3
              ? <View
                  style={{
                    backgroundColor: defaultBackgroundColor.page_Background_Color,
                    width: screenWidth - 30 - 22,
                    height: 208,
                    marginTop: 12,
                    justifyContent: 'center',
                    alignItems: 'center',
                  }}
                >
                  <Image
                    source={this.state.images3[0]}
                    style={{width: 200, height: 180}}
                  />
                </View>
              : <TouchableOpacity
                  onPress={() => {
                    this.pickMultiple (3);
                  }}
                >
                  <View
                    style={{
                      backgroundColor: defaultBackgroundColor.page_Background_Color,
                      width: screenWidth - 30 - 22,
                      height: 208,
                      marginTop: 12,
                      justifyContent: 'center',
                      alignItems: 'center',
                    }}
                  >
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 12,
                        fontFamily: 'PingFangSC-Regular',
                      }}
                    >
                      +添加
                    </Text>
                    {this.state.index == 0
                      ? <Text
                          style={{
                            color: '#9b9b9b',
                            fontSize: 10,
                            fontFamily: 'PingFangSC-Regular',
                            marginTop: 5,
                          }}
                        >
                          营业执照正面照
                        </Text>
                      : <Text
                          style={{
                            color: '#9b9b9b',
                            fontSize: 10,
                            fontFamily: 'PingFangSC-Regular',
                            marginTop: 5,
                          }}
                        >
                          经营场所正面照
                        </Text>}
                  </View>
                </TouchableOpacity>}
          </View>

          {this.state.images3 && this.state.index == 0
            ? <View
                style={{
                  marginTop: 11,
                  marginHorizontal: 15,
                  width: screenWidth - 30,
                  height: 193,
                  backgroundColor: 'white',
                  paddingTop: 12,
                }}
              >
                <Text
                  style={{
                    color: '#4a4a4a',
                    fontSize: 14,
                    fontFamily: 'PingFangSC-Regular',
                    fontWeight: 'bold',
                    marginLeft: 11,
                  }}
                >
                  营业执照
                </Text>

                <View
                  style={{
                    borderBottomColor: defaultBackgroundColor.page_Background_Color,
                    borderBottomWidth: 1,
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                  }}
                >
                  <View stylew={{flex: 1, justifyContent: 'center'}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                        paddingLeft: 11,
                      }}
                    >
                      执照名称:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入营业执照名称"
                    underlineColorAndroid="transparent"
                    // keyboardType="numeric"
                    style={{
                      fontSize: 14,
                      color: '#9b9b9b',
                      flex: 4,
                      paddingLeft: 12,
                    }}
                    onChangeText={text => {
                      this.setState ({
                        business_license_name: text,
                      });
                    }}
                  />
                </View>

                <View
                  style={{
                    borderBottomColor: defaultBackgroundColor.page_Background_Color,
                    borderBottomWidth: 1,
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                  }}
                >
                  <View stylew={{flex: 1, justifyContent: 'center'}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                        paddingLeft: 11,
                      }}
                    >
                      营业执照号:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入营业执照号"
                    underlineColorAndroid="transparent"
                    keyboardType="numeric"
                    style={{fontSize: 14, color: '#9b9b9b', flex: 4}}
                    onChangeText={text => {
                      this.setState ({
                        business_license_number: text,
                      });
                    }}
                  />

                </View>

                <View
                  style={{
                    borderBottomColor: defaultBackgroundColor.page_Background_Color,
                    borderBottomWidth: 1,
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                  }}
                >
                  <View stylew={{justifyContent: 'center', flex: 1}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                        paddingLeft: 11,
                      }}
                    >
                      执照有效期:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入执照有效期(如:2035-12-31)"
                    underlineColorAndroid="transparent"
                    keyboardType="numeric"
                    style={{fontSize: 14, color: '#9b9b9b', flex: 1}}
                    onChangeText={text => {
                      this.setState ({
                        business_license_time: text,
                      });
                    }}
                  />
                </View>

                <View
                  style={{
                    alignItems: 'center',
                    width: screenWidth - 30,
                    flexDirection: 'row',
                    backgroundColor: '#ffffff',
                    height: 40,
                  }}
                >
                  <View stylew={{flex: 1, justifyContent: 'center'}}>
                    <Text
                      style={{
                        color: '#4a4a4a',
                        fontSize: 14,
                        fontFamily: 'PingFangSC-Regular',
                        paddingLeft: 11,
                      }}
                    >
                      法人姓名:
                    </Text>
                  </View>
                  <TextInput
                    multiline={true}
                    placeholder="请输入法人姓名"
                    underlineColorAndroid="transparent"
                    style={{
                      fontSize: 14,
                      color: '#9b9b9b',
                      flex: 4,
                      paddingLeft: 12,
                    }}
                    onChangeText={text => {
                      this.setState ({
                        real_name: text,
                      });
                    }}
                  />
                </View>
              </View>
            : null}
          <View
            style={{
              width: screenWidth,
              height: 50,
              marginTop: 11,
              backgroundColor: 'white',
              paddingHorizontal: 31,
              flexDirection: 'row',
              justifyContent: 'space-between',
              alignItems: 'center',
            }}
          >
            <TouchableOpacity onPress={() => {}}>
              <View
                style={{
                  backgroundColor: '#f63300',
                  borderRadius: 3,
                  width: (screenWidth - 62 - 34) / 2,
                  flexDirection: 'row',
                  justifyContent: 'center',
                  alignItems: 'center',
                  paddingVertical: 10,
                }}
              >
                <Text
                  style={{
                    color: '#fff',
                    fontSize: 16,
                    fontFamily: 'PingFangSC-Regular',
                  }}
                >
                  保存草稿
                </Text>
              </View>
            </TouchableOpacity>

            {this.state.btnActive === true
              ? <TouchableOpacity
                  onPress={() => {
                    Keyboard.dismiss ();
                    this._setStoreAudit ();
                  }}
                >
                  <View
                    style={{
                      backgroundColor: '#f63300',
                      borderRadius: 3,
                      width: (screenWidth - 62 - 34) / 2,
                      flexDirection: 'row',
                      justifyContent: 'center',
                      alignItems: 'center',
                      paddingVertical: 10,
                    }}
                  >
                    <Text
                      style={{
                        color: '#fff',
                        fontSize: 16,
                        fontFamily: 'PingFangSC-Regular',
                      }}
                    >
                      {this.state.pushBtnText}
                    </Text>
                  </View>
                </TouchableOpacity>
              : <View
                  style={{
                    backgroundColor: defaultBackgroundColor.condition_Background,
                    borderRadius: 3,
                    width: (screenWidth - 62 - 34) / 2,
                    flexDirection: 'row',
                    justifyContent: 'center',
                    alignItems: 'center',
                    paddingVertical: 10,
                  }}
                >
                  <Text
                    style={{
                      color: '#fff',
                      fontSize: 16,
                      fontFamily: 'PingFangSC-Regular',
                    }}
                  >
                    {this.state.pushBtnText}
                  </Text>
                </View>}

          </View>
        </ScrollView>
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    // flex:1,
    backgroundColor: defaultBackgroundColor.page_Background_Color,
  },
});

function select (store) {
  return {
    uuid: store.userInfo.admin_id,
    // store_id:store.userInfo.store_id,
    user_mobile: store.userInfo.mobile,
  };
}
export default connect (select) (HomeShopIntelligence);
