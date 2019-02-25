import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  ScrollView,
  TextInput
} from 'react-native';
import {connect} from 'react-redux';
import Header from '../../components/Header/Header'
import Toast from 'react-native-simple-toast';
const width=Dimensions.get('window').width;
class VerCode extends Component {
  constructor(props) {
    super(props);
    this.state={
        text: ''
    }
  }

  static navigationOptions = ({navigation}) => ({
        header: <Header centerTitle={'例子'} leftPress={() => {
               navigation.goBack()}}/>,
       
    });

  render() {
    return (
      <View style={styles.container}>
      </View>
    );
  }
}


const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor:"#F6F6F6",

  },
 

});

function select(store){
    return {
        username:store.userInfo.user_name,
    }
}
export default connect(select)(VerCode);