import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  TextInput
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from "../../utils/appTheme";
import TopBackHeader from '../../components/Header/Header';
import TimeButton from '../../components/TimerButton';
import { sendMobileCode, quickLogin } from "../../network/loginNetApi";
import RegExpTool from "../../utils/RegExpTool";
import Toast from 'react-native-simple-toast'
export default class FinalcialWithdraw extends Component{

    constructor(props){
        super(props)
        this.state={

            phoneNum:''
        }
    }

    static navigationOptions = ({navigation}) => ({
       
        header: <TopBackHeader 
        centerTitle="提现"

        leftPress={()=>{
            navigation.goBack(); 
        }}

        />,
       
    });


    //请求验证码
    _clickGetPhoneReqButton(){
        
        let formData = {
            mobile: this.state.phoneNum,
            code_type: '1'
        }

        sendMobileCode(formData, ( responseData ) => {
            
            const {code,msg}= responseData;
            if(code === 1){
                let codeData = responseData['data'];
                this.setState({
                    receiveCode: codeData['code']
                });
            }

            Toast.showWithGravity(msg,1,Toast.CENTER);
        })
    }

    render(){
        return (

            <View style={styles.container}>
               <View style={{marginLeft:14,marginTop:13}}>
                   <Text style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',fontWeight:'bold'}}>银行卡绑定</Text>
               </View>
               <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   marginTop:13,
                   height:40
                   }}>

            <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>姓名:</Text>
            </View>
                  <TextInput 
                            multiline={true}
                            placeholder="请输入姓名"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:40,margin:0,paddingVertical:0,paddingRight:0}}
                            onChangeText={(text)=>{
                                this.setState({
                                    name:text
                                })
                            }}
                            />
                  
               </View>

                 <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   height:40
                   }}>

 <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>身份证号:</Text>
</View>
                  <TextInput 
                            multiline={true}
                            placeholder="请输入身份证号"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:12,paddingLeft:12,margin:0,paddingVertical:0,paddingRight:0}}
                            onChangeText={(text)=>{
                                this.setState({
                                    creditCardNum:text
                                })
                            }}
                            />
                  
               </View>

                 <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   height:40
                   }}>
 <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>银行卡号:</Text>
 </View>
                  <TextInput 
                            multiline={true}
                            placeholder="请输入银行卡号"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:12,paddingLeft:12,margin:0,paddingVertical:0,paddingRight:0}}
                            onChangeText={(text)=>{
                                this.setState({
                                    moneyCard:text
                                })
                            }}
                            />
                  
               </View>

                 <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   height:40
                   }}>
                    <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                       <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>开户银行:</Text>
                    </View>
                  <TextInput 
                            multiline={true}
                            placeholder="经营者银行卡(仅限储蓄卡)"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:12,margin:0,paddingVertical:0,paddingRight:0}}
                            onChangeText={(text)=>{
                                this.setState({
                                    chuxCard:text
                                })
                            }}
                            />
                  
               </View>

                 <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   height:40
                   }}>

                <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>预留手机:</Text>
                </View>
                  <TextInput 
                            multiline={true}
                            placeholder="请输入手机号"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:12,paddingLeft:12,margin:0,paddingVertical:0,paddingRight:0}}
                            onChangeText={(text)=>{
                                this.setState({
                                    phoneNum:text
                                })
                            }}
                            />
                   <TimeButton style={{justifyContent:'center',flex:2,backgroundColor:'#f63300',alignItems:'center',height:40,padding: 0, margin: 0}} 
                     textStyle = {{ color: '#fff' }}
                     timerCount = { 60 }

                     disableColor= '#D2D2D2'
                     enable = {true}    //按钮可点击？


                     onClick = {(shouldStartCountting) => {

                        let isTelpNum = RegExpTool.phoneByReg(this.state.phoneNum);
                        if(!isTelpNum['check']){  //如果校验失败，不能开始倒计时
                            shouldStartCountting(false);
                            Toast.showWithGravity(isTelpNum['error'],1,Toast.CENTER)
                        }else{
                            shouldStartCountting(true);
                            //请求接口，获取验证码
                            this._clickGetPhoneReqButton()





                            
                        }
                    }}
                   >
                         <Text  style={{color:'#fff',fontSize:14,fontFamily:'PingFangSC-Regular'}}>验证码</Text>
                 </TimeButton>
               </View>


 


              
















                 <View style={{
                   borderBottomColor:defaultBackgroundColor.page_Background_Color,
                   borderBottomWidth:1,
                   alignItems:'center',
                   width:screenWidth,
                   justifyContent:'flex-start',
                   paddingLeft:14,
                   flexDirection:'row',
                   backgroundColor:'#ffffff',
                   height:40
                   }}>
 <View stylew={{flex:1,alignItems:'center',justifyContent:'center'}}>
                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>验证码:</Text>
</View>
                  <TextInput 
                            multiline={true}
                            placeholder="请输入验证码"
                            underlineColorAndroid='transparent'
                          
                            style={{fontSize:14,color:'#9b9b9b',flex:4,paddingLeft:28}}
                            onChangeText={(text)=>{
                                this.setState({
                                    verifyCode:text
                                })
                            }}
                            />
                  
               </View>

               <View style={{marginTop:29,backgroundColor:'#f63300',marginHorizontal:16,borderRadius:3,flexDirection:'row',alignItems:'center',justifyContent:'center',paddingVertical:13}}>
                   <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>下一步</Text>
               </View>
          
            </View>
        );
    }
}


const styles=StyleSheet.create({
    container:{
        flex:1,
        backgroundColor:defaultBackgroundColor.page_Background_Color
    }
})