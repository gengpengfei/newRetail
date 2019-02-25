import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  Alert,
  TouchableWithoutFeedback,
  BackHandler,
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import {MineSection} from './components/Section';
import ParallaxScrollView from '../../components/parallaxView';
import Toast from 'react-native-simple-toast';
import {information} from '../../network/shopNetApi';
import {handleUserInfomation} from '../LoginPage/utils/accountHandle';
const PARALLAX_HEADER_HEIGHT = statusbarHeight + 120;

class Mine extends Component {
  constructor (props) {
    super (props);

    this._clickSectionItem = this._clickSectionItem.bind (this);
    this._PressNextPage = this._PressNextPage.bind (this);
    this._goToScreen = this._goToScreen.bind (this);
    this._noStroe = this._noStroe.bind (this);
  }

  static navigationOptions = ({navigation}) => ({
    tabBarLabel: '我的',
    tabBarIcon: ({tintColor}) => (
      <Image
        resizeMode="contain"
        source={require ('./src/mine.png')}
        style={{
          width: tabBarIconStyle.width - 8,
          height: tabBarIconStyle.height - 8,
          tintColor: tintColor,
        }}
      />
    ),
    header: null,
  });

  _clickSectionItem (str) {
    if (str === '我的合同') {
      this._PressNextPage ('MineContract');
    } else if (str === '点击头像设置') {
      this._goToScreen ('MineSet');
    } else if (str === '诚信等级') {
      this._PressNextPage ('MineIntegrity');
    } else if (str === '通知设置') {
      this._PressNextPage ('MineNotifications');
    } else if (str === '帮助中心') {
      this._goToScreen ('MineHelp');
    } else if (str === '关于我们') {
      this._goToScreen ('MineAbout');
    } else if (str === '门店管理') {
      this._PressNextPage ('OutOfTime');
    } else if (str === '意见反馈') {
      this._goToScreen ('MineOpinion');
    } else if (str === '财务结算') {
      this._PressNextPage ('ReadyPay');
    } else if (str === '门店资质') {
      this._PressNextPage ('ShopIntelligence');
    } else if (str === '我的业务员') {
      this._PressNextPage ('MineSalesman');
    }
  }

  shouldComponentUpdate (nextProps, nextState) {
    if (nextProps.storeProgress != this.props.storeProgress) {
      return false;
    } else if (nextProps.storeInfo_storeID != this.props.storeInfo_storeID) {
      return false;
    } else if (nextProps.store_name != this.props.store_name) {
      return false;
    } else if (nextProps.store_address != this.props.store_address) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * 跳转函数
   * @param {* 要跳转的页面} pageName 
   * @param {* 要跳转的参数} pageParams 
   * @param {* 如果没有店铺，是否提示} shouldGravity 
   */
  _PressNextPage (pageName = '', pageParams = {}, shouldGravity = true) {
    if (this.props.store_id) {
      this._goToScreen (pageName, pageParams);
    } else {
      this._noStroe (shouldGravity);
    }
  }

  _noStoreShowGravity = (goodId, price) => {
    const showMsgList = [
      '您还未登录，请先登录',
      '请先认领门店！谢谢',
      '请先提交门店资质认证',
      '您的门店正在审核中，请耐心等候',
    ];

    Alert.alert ('温馨提示', showMsgList[this.props.storeProgress], [
      {
        text: '确定',
        onPress: () => {
          if (this.props.storeProgress == 1 || this.props.storeProgress == 2) {
            this._PressNextPage ('', '', false);
          }
        },
      },
      {text: '取消', onPress: () => {}},
    ]);
  };

  _noStroe (shouldGravity) {
    if (shouldGravity) {
      this._noStoreShowGravity (); // 没有店铺的情况
      return;
    }

    /***  ************** **/
    let pageName, pageParams;
    if (this.props.storeProgress == 0) {
    } else if (this.props.storeProgress == 1) {
      pageName = 'ConfirmShop';
      pageParams = {callBackData: this._getData};
    } else if (this.props.storeProgress == 2) {
      pageName = 'HomeShopIntelligence';
      pageParams = {
        store_id: this.props.storeInfo_storeID,
        store_name: this.props.store_name,
        store_address: this.props.store_address,
        callBackData: this._getData,
      };
    } else {
      Toast.showWithGravity ('您的门店正在审核中，请耐心等候', 1, Toast.CENTER);
    }

    this._goToScreen (pageName, pageParams);
  }

  _goToScreen = (pageName = '', pageParams = {}) => {
    const {navigate} = this.props.navigation;
    navigate (pageName, pageParams);
  };

  _getData = () => {
    let formData = {
      admin_id: this.props.admin_id,
    };

    information (formData, response => {
      handleUserInfomation (this.props.dispatch, response);
    });
  };

  render () {
    return (
      <ParallaxScrollView
        outputScaleValue={10}
        // renderFixedHeader={()=>(
        //     <View style={{flex:1,backgroundColor:'transparent'}}>
        //         <View style={{width:screenWidth,height:statusbarHeight}}/>
        //         <View style={{width:screenWidth,height:appBar_Height,justifyContent:'center',alignItems:'center'}}>
        //           <Text style={{color:'white',fontSize:17}}>我的</Text>
        //         </View>
        //     </View>
        // )}
        parallaxHeaderHeight={PARALLAX_HEADER_HEIGHT}
        renderBackground={() => (
          <View key="background">
            <Image
              style={{width: screenWidth, height: PARALLAX_HEADER_HEIGHT}}
              source={require ('./src/headerBackground.png')}
            />
          </View>
        )}
        renderForeground={() => (
          <View style={{height: PARALLAX_HEADER_HEIGHT, flex: 1}}>
            <View style={{width: screenWidth, height: statusbarHeight}} />
            <TouchableWithoutFeedback
              onPress={() => {
                this._clickSectionItem ('点击头像设置');
              }}
            >
              <View
                style={{
                  width: screenWidth,
                  height: 80,
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingHorizontal: 15,
                }}
              >
                <View style={{width: 20, height: 20}} />
                <Image
                  style={{width: 70, height: 70, borderRadius: 35}}
                  source={require ('./src/defaultHead.png')}
                />
                <Image
                  style={{width: 15, height: 15}}
                  source={require ('./src/rightJ.png')}
                />
              </View>
            </TouchableWithoutFeedback>

            <View
              style={{
                width: screenWidth,
                height: 30,
                justifyContent: 'center',
                alignItems: 'center',
              }}
            >
              <Text style={{color: 'white', fontSize: 17}}>
                {this.props.mobile}
              </Text>
            </View>

          </View>
        )}
      >
        <View style={styles.container}>
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/caiwujiesuan.png')}
            titleStr={'财务结算'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/mendianguanli.png')}
            titleStr={'门店管理'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/mendianzizhi.png')}
            titleStr={'门店资质'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <View style={{width: screenWidth, height: 15}} />

          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/wodeyewuyuan.png')}
            titleStr={'我的业务员'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/wodehetong.png')}
            titleStr={'我的合同'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          {/*<MineSection clickItem={this._clickSectionItem} iconImage={require('./src/pingjia.png')} titleStr={'诚信等级'} rightImgPath={require('./src/rightJ.png')}/>*/}
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/tongzhishezhi.png')}
            titleStr={'通知设置'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <View style={{width: screenWidth, height: 15}} />

          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/bangzhuzhongxin.png')}
            titleStr={'帮助中心'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/yijianfankui.png')}
            titleStr={'意见反馈'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          <MineSection
            clickItem={this._clickSectionItem}
            iconImage={require ('./src/guanyuwomen.png')}
            titleStr={'关于我们'}
            rightImgPath={require ('./src/rightJ.png')}
          />
          {/* <MineSection clickItem={this._clickSectionItem} iconImage={require('./src/pingjia.png')} titleStr={'新意见反馈'} rightImgPath={require('./src/rightJ.png')}/> */}

        </View>

      </ParallaxScrollView>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: defaultBackgroundColor.page_Background_Color,
  },
});

function select (store) {
  return {
    admin_id: store.userInfo.admin_id,
    store_id: store.userInfo.store_id,
    mobile: store.userInfo.mobile,
    storeProgress: store.storeInfo.progress,
    storeInfo_storeID: store.storeInfo.store_id,
    store_name: store.storeInfo.store_name,
    store_address: store.storeInfo.store_address,
  };
}
export default connect (select) (Mine);
