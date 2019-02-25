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
import {checkUsedVoucher} from '../../network/shopNetApi';
const width=Dimensions.get('window').width;
class VerCode extends Component {
  constructor(props) {
    super(props);
    this.state={
        text: '',
        msg:''
    }
    this._checkUsedVoucher=this._checkUsedVoucher.bind(this)
  }

  static navigationOptions = ({navigation}) => ({
        header: <Header centerTitle={'验证券码'} leftPress={() => {
               navigation.goBack()}}/>,
       
    });
  _checkUsedVoucher(){
    let fromData={
      store_id:this.props.user.store_id,
      voucher_sn:this.state.text,
      sign:'',
    }
    checkUsedVoucher(fromData,(respoentData)=>{
        
        if(respoentData['code']==1){
          this.setState({
            msg:respoentData['msg']
          })
        }else{
          this.setState({
            msg:respoentData['msg']
          })
        }
    })
  }
  render() {
    return (
      <View style={styles.container}>
          <View style={{width:width-28,marginLeft:14}}>
                <Text style={{fontSize:14,fontWeight:'bold',color:'#4A4A4A',marginBottom:14,marginTop:14}}>请输入券码</Text>
                <TextInput
                    style={{height: 40, borderColor: '#DBDBDB', borderWidth: 1,padding: 0}}
                    placeholder="请输入券码"
                    onChangeText={(text) => this.setState({text})}
                    underlineColorAndroid="transparent"
                    value={this.state.text}
                    keyboardType={'numeric'}
                />
                <Text style={{fontSize:14,color:'#FF0000',marginTop:10}}>{this.state.msg}</Text>
                <TouchableOpacity
                    style={{backgroundColor:'#F63300',height:46,width:width-28,borderRadius:3,
                            alignItems:'center',justifyContent:'center',marginTop:15}}
                    onPress={()=>{
                      this._checkUsedVoucher()
                    }}>
                        <Text style={{fontSize:15,color:'#fff'}}>提 交</Text>
                </TouchableOpacity>
          </View>
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
        user:store.userInfo
    }
}
export default connect(select)(VerCode);