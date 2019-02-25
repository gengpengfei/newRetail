/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */

import React, {Component} from 'react';
import Header from '../../components/Header/Header';
import {Loading} from '../../components/Loading/Loadings';
import {StyleSheet, WebView, View, Platform} from 'react-native';
import {getHelpInfo} from '../../network/OtherNetApi';
class MineHelpDetail extends Component {
  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        leftPress={() => {
          navigation.goBack ();
        }}
        centerTitle={navigation.state.params.info.title}
      />
    ),
  });
  constructor (props) {
    super (props);
    this.state = {
      height: 0,
      loading: true,
    };
  }
  onMessage (event) {
    try {
      const action = JSON.parse (event.nativeEvent.data);
      if (action.type === 'setHeight' && action.height > 0) {
        this.setState ({height: action.height, loading: false});
      }
    } catch (error) {
      this.setState ({loading: false});
    }
  }
  render () {
    return (
      <View style={styles.container}>
        {!Platform.OS == 'android'
          ? <WebView
              style={{height: this.state.height}}
              javaScriptEnabled={true}
              scalesPageToFit={true}
              source={{
                uri: global_BASEURL +
                  '/shopapi/article/articleContent?article_id=' +
                  this.props.navigation.state.params.info.article_id,
              }}
              automaticallyAdjustContentInsets={true}
              onMessage={this.onMessage.bind (this)}
            />
          : <WebView
              javaScriptEnabled={true}
              scalesPageToFit={true}
              source={{
                uri: global_BASEURL +
                  '/shopapi/article/articleContent?article_id=' +
                  this.props.navigation.state.params.info.article_id,
              }}
              automaticallyAdjustContentInsets={true}
              onMessage={this.onMessage.bind (this)}
            />}

        {Platform.OS == 'android'
          ? null
          : <Loading loading={this.state.loading} />}
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: '#f6f6f6',
  },
});

export default MineHelpDetail;
