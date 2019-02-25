import React, {Component} from 'react';
import {
  StyleSheet,
  View,
  Text,
  ScrollView,
  FlatList,
  TouchableOpacity,
} from 'react-native';
import Header from '../../components/Header/Header';
import {connect} from 'react-redux';
import {SetSection} from './components/Section';
import {defaultFontColor, defaultBackgroundColor} from '../../utils/appTheme';
import Button from '../../components/Button';
import {memberList, delMember} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';

class MineSalesman extends Component {
  constructor (props) {
    super (props);

    this._commitButton = this._commitButton.bind (this);
    this._getSalesManList = this._getSalesManList.bind (this);
    this._clickDeleteButton = this._clickDeleteButton.bind (this);
    this._callbackRefresh = this._callbackRefresh.bind (this);
    this._createEmptyView = this._createEmptyView.bind (this);

    this.state = {
      salesmanList: [],
    };
  }
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle="我的业务员"
      />
    ),
  });

  componentDidMount () {
    this._getSalesManList ();
  }

  _getSalesManList () {
    let formData = {
      store_id: this.props.storeInfo_storeID,
    };
    memberList (formData, responseData => {
      let {msg, code, data} = responseData;
      if (code === 1) {
        this.setState ({
          salesmanList: data,
        });
      } else {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      }
    });
  }

  _clickDeleteButton (item) {
    let params = {
      ...item,
      callBack: this._callbackRefresh,
    };
    const {navigate} = this.props.navigation;
    navigate ('MineSalesmanRemove', params);
  }

  _callbackRefresh () {
    this._getSalesManList ();
  }

  _commitButton () {
    const {navigate} = this.props.navigation;
    navigate ('MineSalesmanCreate', {callbackRefresh: this._callbackRefresh});
  }

  _renderItem (item, index) {
    return (
      <View
        style={{
          width: screenWidth,
          height: 40,
          paddingHorizontal: 15,
          flexDirection: 'row',
          justifyContent: 'space-between',
          alignItems: 'center',
          borderBottomColor: '#efefef',
          borderBottomWidth: 1,
        }}
      >

        <Text style={{color: '#5a5a5a'}}>{item.user_name}: {item.mobile}</Text>
        <TouchableOpacity
          onPress={() => {
            this._clickDeleteButton (item);
          }}
        >
          <View
            style={{
              borderColor: '#cfcfcf',
              borderWidth: 1,
              borderRadius: 3,
              paddingHorizontal: 10,
              paddingVertical: 3,
            }}
          >
            <Text style={{color: '#5a5a5a'}}>移除</Text>
          </View>
        </TouchableOpacity>

      </View>
    );
  }
  _createEmptyView = () => {
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
          暂无数据
        </Text>
      </View>
    );
  };

  render () {
    return (
      <View style={styles.container}>
        <ScrollView style={{flex: 1, backgroundColor: 'white'}}>

          <View style={{padding: 15}}>
            <Text style={{fontSize: 16, color: '#4a4a4a'}}>
              {this.props.store_name}
            </Text>
            <Text style={{fontSize: 14, color: '#9b9b9b', top: 10}}>
              {this.props.store_address}
            </Text>
          </View>

          <View style={{height: 15, width: screenWidth}} />

          <FlatList
            data={this.state.salesmanList}
            keyExtractor={(item, index) => index.toString ()}
            renderItem={({item, index}) => this._renderItem (item, index)}
            ListEmptyComponent={this._createEmptyView ()}
            //renderItem={this._renderItem}
          />

        </ScrollView>

        <Button
          // isLoading={this.state.isLoading}
          // isLoadStr="正在提交..."
          style={[
            styles.buttonCommit,
            {backgroundColor: defaultBackgroundColor.search_Background},
          ]}
          onPress={this._commitButton}
        >
          <Text
            style={{
              fontSize: 18,
              color: defaultFontColor.background_Font_Color,
            }}
          >
            创建业务员
          </Text>
        </Button>
        <View style={{width: screenWidth, height: 10}} />

      </View>
    );
  }
}
const styles = StyleSheet.create ({
  container: {
    flex: 1,
  },
  buttonCommit: {
    left: 15,
    width: screenWidth - 30,
    height: 46,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 6,
  },
});

function select (store) {
  return {
    storeInfo_storeID: store.storeInfo.store_id,
    store_name: store.storeInfo.store_name,
    store_address: store.storeInfo.store_address,
  };
}

export default connect (select) (MineSalesman);
