import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  Platform,
  takeSnapshot,
  UIManager,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import QrCode from 'react-native-qrcode';
import {getStoreSignCode, storeQrcodeImg} from '../../network/shopNetApi';
import RNFS from 'react-native-fs';
import Toast from 'react-native-simple-toast';

class MineQRCode extends Component {
  constructor (props) {
    super (props);
    this.state = {
      downLoadBtn: '保存二维码',
      btnActive: true,
    };
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="基本信息"
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._getStoreSignCode ();
  }

  _getData = () => {};
  _goToScreen = (str, para) => {
    const {navigate} = this.props.navigation;
    navigate (str, para);
  };

  _getStoreSignCode () {
    let formData = {
      store_id: this.props.store_id,
    };

    getStoreSignCode (formData, response => {
      const {code = -1, msg = '', data = null} = response;

      if (code == -1) {
      } else {
        this.setState (
          {
            codeInfo: JSON.stringify (data),
          },
          () => {}
        );
      }
    });
  }

  _storeQrcodeImg () {
    this.setState ({
      downLoadBtn: '下载中',
      btnActive: false,
    });
    let formData = {
      store_id: this.props.store_id,
      data: encodeURI (this.state.codeInfo),
    };

    storeQrcodeImg (formData, response => {
      const {data = null, code = -1, msg = ''} = response;

      if (code == -1) {
      } else {
        this.setState (
          {
            uri: data,
          },
          () => {
            this._downLoadFile ();
          }
        );
      }
    });
  }

  _downLoadFile = () => {
    const formUrl = this.state.uri;
    let downloadDest = null;
    if (Platform.OS === 'android') {
      downloadDest = `${RNFS.ExternalStorageDirectoryPath + '/DCIM/Camera'}/${(Math.random () * 1000) | 0}.jpg`;
    } else {
      downloadDest = `${RNFS.MainBundlePath}/${(Math.random () * 1000) | 0}.jpg`;
    }

    const options = {
      fromUrl: formUrl,
      toFile: downloadDest,
      background: true,
      begin: res => {},
      progress: res => {
        let pro = res.bytesWritten / res.contentLength;

        this.setState ({
          progressNum: pro,
        });
      },
    };
    try {
      const ret = RNFS.downloadFile (options);
      ret.promise
        .then (res => {
          this.setState ({
            downLoadBtn: '保存二维码',
            btnActive: true,
          });
          Toast.showWithGravity ('图片路径为:' + downloadDest, 2, Toast.CENTER);
        })
        .catch (err => {});
    } catch (e) {}
  };
  render () {
    // let cont=this.props.navigation.state.params.cont;

    return (
      <View style={styles.container}>

        <Text>新零售扫一扫，向我付钱</Text>

        <View
          style={{
            flexDirection: 'row',
            justifyContent: 'center',
            marginTop: 20,
          }}
        >
          <QrCode
            size={200}
            value={this.state.codeInfo}
            bgColor="black"
            fgColor="#fff"
          />
        </View>

        <View style={{width: screenWidth, height: 10}} />

        {this.state.btnActive == true
          ? <TouchableWithoutFeedback
              onPress={() => {
                this._storeQrcodeImg ();
              }}
            >
              <Text
                style={{
                  color: '#f63300',
                  fontSize: 14,
                  fontFamily: 'PingFangSC-Regular',
                  textDecorationLine: 'underline',
                }}
              >
                {this.state.downLoadBtn}
              </Text>
            </TouchableWithoutFeedback>
          : <Text
              style={{
                color: defaultBackgroundColor.condition_Background,
                fontSize: 14,
                fontFamily: 'PingFangSC-Regular',
                textDecorationLine: 'underline',
              }}
            >
              {this.state.downLoadBtn}
            </Text>}

        <View style={{width: screenWidth, height: 150}} />
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
});

function select (store) {
  return {
    store_id: store.userInfo.store_id,
  };
}
export default connect (select) (MineQRCode);
