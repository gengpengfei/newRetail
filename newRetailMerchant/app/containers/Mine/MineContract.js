import React, {Component} from 'react';
import {StyleSheet, View, Text, Image} from 'react-native';
import Header from '../../components/Header/Header';
import {connect} from 'react-redux';
import {
  defaultBackgroundColor,
  defaultSeparateLineColor,
  defaultFontColor,
} from '../../utils/appTheme';

import {storeAuditInfo} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
import {isNotEmptyArray} from '../../utils/structureJudgment';
import {configServerImagePath} from '../../utils/commonMethod';

class MineContract extends Component {
  constructor (props) {
    super (props);
    this.state = {
      contractData: {},
    };

    this._storeAuditInfo = this._storeAuditInfo.bind (this);
    this._createEmptyView = this._createEmptyView.bind (this);
  }
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle="我的合同"
      />
    ),
  });

  _renderItem (leftStr, rightStr, click, imgContent) {
    let rightStrColor = click ? '#F63300' : '#4A4A4A';
    let underlineStr = click ? 'underline' : 'none';
    return (
      <View style={{width: screenWidth, height: 50, paddingHorizontal: 15}}>
        <View
          style={{
            flex: 1,
            borderBottomColor: '#dfdfdf',
            borderBottomWidth: 1,
            flexDirection: 'row',
            alignItems: 'center',
          }}
        >
          <Text
            style={{
              color: '#4A4A4A',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
              flex: 4,
            }}
          >
            {leftStr}:
          </Text>
          <Text
            onPress={click ? click : null}
            style={{
              color: rightStrColor,
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
              flex: 6,
              textDecorationLine: underlineStr,
            }}
          >
            {rightStr}
          </Text>
        </View>
        {click
          ? <View
              style={{
                width: screenWidth,
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Image
                style={{width: screenWidth - 60}}
                source={{uri: configServerImagePath (imgContent)}}
                resizeMode="cover"
              />
            </View>
          : null}
      </View>
    );
  }

  componentDidMount () {
    this._storeAuditInfo ();
  }

  _storeAuditInfo () {
    let formData = {
      admin_id: this.props.admin_id,
    };
    storeAuditInfo (formData, response => {
      const {code = -1, msg = '', data = []} = response;

      if (code == 1) {
        this.setState ({
          contractData: data,
        });
      } else {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
    // "contract_time": null,    //生效时间
    //     "contract_end_time": null,  //判断状态
    //     "contract_number": null,    //合同号
    //     "contract_image": null   //照片  查看合同
  }

  _lookContract () {}

  _createEmptyView () {
    return (
      <View
        style={{
          flex: 1,
          flexDirection: 'column',
          height: screenWidth,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Text style={{fontSize: 20, color: defaultFontColor.prompt_Font_Color}}>
          暂无合同
        </Text>
      </View>
    );
  }

  render () {
    // if(this.state.contractData.contract_number === null ){
    //     return this._createEmptyView();
    // }else{

    return (
      <View style={styles.container}>
        {this._renderItem ('电子合同版本号', this.state.contractData.contract_number)}
        {this.state.contractData.contract_end_time === null
          ? this.state.contractData.contract_number === null
              ? this._renderItem ('状态', '')
              : this._renderItem ('状态', '生效中')
          : this._renderItem ('状态', '已到期')}
        {this.state.contractData.contract_end_time === null
          ? this._renderItem ('生效时间', this.state.contractData.contract_time)
          : this._renderItem (
              '失效时间',
              this.state.contractData.contract_end_time
            )}
        {isNotEmptyArray (this.state.contractData.contract_image)
          ? this._renderItem (
              '查看',
              '合同内容如下',
              this._lookContract,
              this.state.contractData.contract_image
            )
          : this._renderItem ('查看', '', this._lookContract)}
      </View>
    );
    //}
  }
}
const styles = StyleSheet.create ({
  container: {
    flex: 1,
  },
});

function select (store) {
  return {
    admin_id: store.userInfo.admin_id,
  };
}

export default connect (select) (MineContract);
