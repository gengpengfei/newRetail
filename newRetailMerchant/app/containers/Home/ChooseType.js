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
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from '../../utils/appTheme';
import TopBackHeader from '../../components/Header/Header';
import {storeCategoryAll} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
export default class ChooseType extends Component {
  constructor (props) {
    super (props);
    this.state = {
      loading: false,
      navList: [],
      categoryList: [],
      nav_id: '',
    };
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <TopBackHeader
        centerTitle="选择品类"
        leftPress={() => {
          navigation.goBack ();
        }}
      />
    ),
  });

  componentDidMount () {
    this._storeCategoryAll ();
  }

  _storeCategoryAll () {
    let formData = {};
    storeCategoryAll (formData, response => {
      const {code = -1, msg = '', data = []} = response;
      if (code == -1) {
        Toast.showWithGravity (msg, 1, Toast.CENTER);
      } else {
        this.setState ({
          navList: data,
          categoryList: data[0].category_list,
          nav_id: data[0].nav_id,
        });
      }
    });
  }

  _configData (item) {
    let arr = item.category_list;
    this.setState ({
      categoryList: arr,
      nav_id: item.nav_id,
    });
  }

  _renderItem1 = (item, index) => {
    return (
      <TouchableOpacity
        onPress={() => {
          this.props.navigation.state.params.callBackFunc (
            item.category_name,
            item.category_id,
            this.state.nav_id
          );
          this.props.navigation.goBack ();
        }}
      >
        <View
          style={{
            height: 40,
            flexDirection: 'row',
            justifyContent: 'center',
            alignItems: 'center',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
          }}
        >
          <Text
            style={{
              color: '#9b9b9b',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            {item.category_name}
          </Text>
        </View>
      </TouchableOpacity>
    );
  };

  _renderItem = (item, index) => {
    return (
      <TouchableOpacity
        onPress={() => {
          this._configData (item);
        }}
      >
        <View
          style={{
            height: 40,
            flexDirection: 'row',
            justifyContent: 'center',
            alignItems: 'center',
            borderBottomColor: defaultBackgroundColor.page_Background_Color,
            borderBottomWidth: 1,
          }}
        >
          <Text
            style={{
              color: '#f63300',
              fontSize: 14,
              fontFamily: 'PingFangSC-Regular',
            }}
          >
            {item.nav_name}
          </Text>
        </View>
      </TouchableOpacity>
    );
  };

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

        <View style={{flex: 2}}>

          <FlatList
            data={this.state.navList}
            renderItem={({item, index}) => this._renderItem (item, index)}
            ListEmptyComponent={this._createEmptyView ()}
            numColumns={1} // 设置列数
            keyExtractor={(item, index) => index.toString ()}
            refreshing={this.state.loading}
            onEndReachedThreshold={-0.05}
            onEndReached={info => {}}
          />
        </View>

        <View style={{flex: 4, backgroundColor: 'white'}}>
          <FlatList
            data={this.state.categoryList}
            renderItem={({item, index}) => this._renderItem1 (item, index)}
            ListEmptyComponent={this._createEmptyView ()}
            numColumns={1} // 设置列数
            keyExtractor={(item, index) => index.toString ()}
            refreshing={this.state.loading}
            onEndReachedThreshold={-0.05}
            onEndReached={info => {}}
          />
        </View>

      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    backgroundColor: defaultBackgroundColor.page_Background_Color,
    flexDirection: 'row',
  },
});
