import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  TextInput,
  NativeModules,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {
  storeClose,
  storeCloseCancel,
  storeCloseInfo,
  getUploadImg,
} from '../../network/shopNetApi';
var ImagePicker = NativeModules.ImageCropPicker;
import Toast from 'react-native-simple-toast';
import {configServerImagePath} from '../../utils/commonMethod';
import {NavigationActions} from 'react-navigation';
import {isNotEmptyArray} from '../../utils/structureJudgment';
// import ActionSheet from 'react-native-actionsheet'
class ShopManager extends Component {
  constructor (props) {
    super (props);
    this.imgArr = [];
    this.state = {
      closeReason: '',
      isShow: false,
      auditState: '',
      closeImg: '',
      images: [],
      selectImage: null,
    };
    this._getUploadImg = this._getUploadImg.bind (this);
    // this.showActionSheet = this.showActionSheet.bind(this);
    this._pickMultiple = this._pickMultiple.bind (this);
    // this._handlePress=this._handlePress.bind(this);
    // this._openCamera=this._openCamera.bind(this);
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="申请关店"
        leftPress={() => {
          navigation.state.params.callBackState ();
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._storeCloseInfo ();
  }

  //提交关店申请
  _storeClose (str) {
    let formData = {
      admin_id: this.props.uuid,
      close_reason: this.state.closeReason,
      close_img: str,
    };

    if (str == '' || str == 'null' || str == null) {
      Toast.showWithGravity ('请上传关店资料', 1, Toast.CENTER);
    } else {
      storeClose (formData, response => {
        const {code = -1, msg = '', data = null} = response;

        if (code == -1) {
        } else {
          this.setState (
            {
              isShow: true,
            },
            () => {
              Toast.showWithGravity ('关店申请提交成功,请耐心等待审核', 1, Toast.CENTER);
              this._storeCloseInfo ();
            }
          );
        }
      });
    }
  }

  //店铺关闭审核进度
  _storeCloseInfo () {
    let formData = {
      admin_id: this.props.uuid,
    };
    storeCloseInfo (formData, response => {
      const {code = -1, msg = '', data = null} = response;
      if (code == -1) {
      } else {
        if (data == null) {
          this.setState ({
            isShow: false,
          });
        } else {
          if (data.close_state == 0) {
            this.setState (
              {
                auditState: '审核中,请耐心等待',
                isShow: true,
                closeReason: data.close_reason,
                closeImg: data.close_img,
              },
              () => {}
            );
          } else if (data.close_state == 1) {
            this.setState ({
              auditState: '已通过',
              isShow: true,
              closeReason: data.close_reason,
              closeImg: data.close_img,
            });
          } else if (data.close_state == 2) {
            this.setState ({
              auditState: '已拒绝',
              isShow: false,
              closeReason: '',
              closeImg: data.close_img,
            });
          } else {
            this.setState ({
              auditState: '已取消',
              isShow: false,
              closeReason: '',
              closeImg: [],
            });
          }
        }
      }
    });
  }

  //选择本地相册中的图片
  _pickMultiple = () => {
    ImagePicker.openPicker ({
      multiple: true,
      waitAnimationEnd: false,
      includeExif: true,
      includeBase64: true,
      compressImageQuality: 0.2,
      // maxFiles:3
    })
      .then (images => {
        this.setState ({
          image: null,
          images: images.map (
            (i, index) => {
              if (index <= 2) {
                return {uri: i.path, data: 'data:image/png;base64,' + i.data};
              } else {
                return null;
              }
            },
            () => {}
          ),
        });
        if (this.state.images) {
          this.state.images.map ((cont, index) => {
            if (cont) {
              this.imgArr[index] = cont['data'];
            }
          });
        }
      })
      .catch (e => alert (e));
  };

  //上传图片
  _getUploadImg () {
    let formData = {
      type: 'storeClose',
      img_base64: this.imgArr,
      store_id: this.props.navigation.state.params.store_id,
    };

    getUploadImg (formData, responseData => {
      if (responseData['code'] === 1) {
        this.setState (
          {
            closeImg: responseData.data,
          },
          () => {
            this._storeClose (encodeURI (this.state.closeImg));
          }
        );
      } else {
        Alert.alert (responseData['msg']);
      }
    });
  }

  //取消关店申请
  _storeCloseCancel () {
    let formData = {
      admin_id: this.props.uuid,
    };
    storeCloseCancel (formData, response => {
      const {code = -1, msg = '', data = null} = response;
      this.setState ({
        isShow: false,
        closeImg: [],
        closeReason: '',
        images: [],
      });
    });
  }

  //渲染照片
  _renderImage (image) {
    return image == null
      ? null
      : <View
          style={{
            marginLeft: 10,
            justifyContent: 'center',
            width: 68,
            height: 68,
            backgroundColor: '#e6e6e6',
            alignItems: 'center',
          }}
        >
          <Image style={{width: 40, height: 50}} source={image} />
        </View>;
  }

  //渲染照片
  _renderImage1 (image) {
    return (
      <View
        style={{
          marginLeft: 10,
          justifyContent: 'center',
          width: 68,
          height: 68,
          backgroundColor: '#e6e6e6',
          alignItems: 'center',
        }}
      >
        <Image
          style={{width: 40, height: 50}}
          source={{uri: configServerImagePath (image)}}
        />
      </View>
    );
  }
  render () {
    return (
      <View style={styles.container}>
        {this.state.isShow == true
          ? <View style={{marginTop: 20}}>
              <Text
                style={{
                  color: '#F63300',
                  fontSize: 12,
                  fontFamily: 'PingFangSC-Regular',
                }}
              >
                {this.state.auditState}
              </Text>
            </View>
          : null}

        <View style={{marginTop: 28}}>
          <Text
            style={{
              color: '#4A4A4A',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            店铺名称：{this.props.navigation.state.params.store_name}
          </Text>
        </View>

        <View
          style={{marginTop: 18, flexDirection: 'row', alignItems: 'center'}}
        >
          <Text
            style={{
              color: '#4A4A4A',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            上传关店资料：
          </Text>
          {isNotEmptyArray (this.state.images)
            ? this.state.images.map (i => <View>{this._renderImage (i)}</View>)
            : isNotEmptyArray (this.state.closeImg)
                ? this.state.closeImg.map (i => (
                    <View>{this._renderImage1 (i)}</View>
                  ))
                : <TouchableWithoutFeedback
                    onPress={() => {
                      this._pickMultiple ();
                    }}
                  >
                    <View
                      style={{
                        justifyContent: 'center',
                        width: 68,
                        height: 68,
                        backgroundColor: '#e6e6e6',
                        alignItems: 'center',
                      }}
                    >
                      <Image
                        style={{width: 24, height: 24}}
                        source={require ('./src/Plus.png')}
                      />
                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 10,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        上传照片
                      </Text>
                      <Text
                        style={{
                          color: '#4a4a4a',
                          fontSize: 10,
                          fontFamily: 'PingFangSC-Regular',
                        }}
                      >
                        (最多三张)
                      </Text>
                    </View>
                  </TouchableWithoutFeedback>}
        </View>
        <View
          style={{flexDirection: 'row', alignItems: 'center', marginTop: 18}}
        >

          <Text
            style={{
              color: '#4a4a4a',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            关店原因:{' '}
          </Text>
          <TextInput
            style={{
              width: 224,
              height: 94,
              borderWidth: 1,
              borderColor: '#EAEAEA',
              marginLeft: 6,
            }}
            underlineColorAndroid="transparent"
            value={this.state.closeReason}
            onChangeText={text => {
              this.setState ({
                closeReason: text,
              });
            }}
          />
        </View>

        {this.state.isShow == true
          ? this.state.auditState === '已通过'
              ? <TouchableWithoutFeedback
                  onPress={() => {
                    this._storeCloseCancel ();
                  }}
                >
                  <View
                    style={{
                      backgroundColor: '#F63300',
                      borderRadius: 3,
                      marginRight: 15,
                      flexDirection: 'row',
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginTop: 30,
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
                      关店成功
                    </Text>
                  </View>
                </TouchableWithoutFeedback>
              : <TouchableWithoutFeedback
                  onPress={() => {
                    this._storeCloseCancel ();
                  }}
                >
                  <View
                    style={{
                      backgroundColor: '#FF6633',
                      borderRadius: 3,
                      marginRight: 15,
                      flexDirection: 'row',
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginTop: 30,
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
                      取消申请
                    </Text>
                  </View>
                </TouchableWithoutFeedback>
          : <TouchableWithoutFeedback
              onPress={() => {
                isNotEmptyArray (this.state.images)
                  ? this._getUploadImg ()
                  : this._storeClose ('');
              }}
            >
              <View
                style={{
                  backgroundColor: '#F63300',
                  borderRadius: 3,
                  marginRight: 15,
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginTop: 30,
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
                  提交审核
                </Text>
              </View>
            </TouchableWithoutFeedback>}

        {/* <ActionSheet
                        ref={o => this.ActionSheet = o}
                        // title={title}
                        options={['拍照', '从相册选择', '取消' ]}
                        cancelButtonIndex={2}
                        destructiveButtonIndex={1}
                        onPress={this._handlePress}
                    />     */}
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    paddingLeft: 15,
    backgroundColor: 'white',
  },
});

function select (store) {
  return {
    uuid: store.userInfo.admin_id,
  };
}
export default connect (select) (ShopManager);
