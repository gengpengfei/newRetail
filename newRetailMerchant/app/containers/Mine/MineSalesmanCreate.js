import React, {Component} from "react";
import {
    StyleSheet,
    View,
    Text,
    TextInput,
    TouchableOpacity
} from "react-native";
import Header from '../../components/Header/Header'
import {connect} from 'react-redux';
import {SetSection, ShopAppointmentInputext} from './components/Section'
import { defaultFontColor,defaultBackgroundColor } from "../../utils/appTheme";
import Button from '../../components/Button';
import TimerButton from '../../components/TimerButton';
import RegExpTool from "../../utils/RegExpTool";
import { addMember } from "../../network/mineNetApi";
import { sendMobileCode, } from "../../network/loginNetApi";
import { isNotEmptyString } from "../../utils/structureJudgment";
import Toast from 'react-native-simple-toast';

class MineSalesmanCreate extends Component {
    constructor(props){
        super(props);

        this._commitButton = this._commitButton.bind(this);
        this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind(this);

        this.state = {
            name:"",
            mobile:'',
            pw:'',
            isLoading:false,
            code: '',
        }
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="创建业务员"/>,
    });

    //请求验证码
    _clickGetPhoneReqButton(){
        let formData = {
            mobile: this.state.mobile,
            code_type: '11'
        }
        sendMobileCode(formData, ( responseData ) => {
            const {code,msg}= responseData;
            if(code === 1){
                let codeData = responseData['data'];
            }else{
                Toast.showWithGravity(msg,1,Toast.CENTER);
            }
        })
    }

    _commitButton(){
       this.setState({
        isLoading:true,
       })

       if(!isNotEmptyString(this.state.name)){
            Toast.showWithGravity('请输入姓名',1,Toast.CENTER);
            return ;
       }

       
       let isTelpNum = RegExpTool.phoneByReg(this.state.mobile);
       if(!isTelpNum['check']){  
            Toast.showWithGravity(isTelpNum['error'],1,Toast.CENTER);
            return ;
        }

    //    if(!isNotEmptyString(this.state.pw)){
    //         Toast.showWithGravity('请输入密码',1,Toast.CENTER);
    //         return ;
    //     }
        if(!isNotEmptyString(this.state.code)){
            Toast.showWithGravity('请输入验证码',1,Toast.CENTER);
            return ;
        }

       let fromData = {
            store_id:this.props.store_id,
            mobile:this.state.mobile,
            password:this.state.pw,
            user_name:this.state.name,
            code: this.state.code,
            code_type: '11'
       };

       addMember(fromData,(responseData)=>{
            this.setState({
                isLoading:false,
            })
            const {code ,data ,msg} = responseData;
            if(code == 1){

                this.props.navigation.state.params.callbackRefresh();
                this.props.navigation.goBack();
            }else{

                // this.setState({
                //     isLoading:true,
                // })
               
            }

            Toast.showWithGravity(msg,1,Toast.CENTER);
            
       });
       
    }

    _renderItem(item){
        return (
            <View style = {{width:screenWidth,height:40,paddingHorizontal:15,flexDirection:'row',justifyContent:'space-between',alignItems:'center',borderBottomColor:'#efefef',borderBottomWidth:1}}>

                <Text style = {{color:'#5a5a5a'}}>{item.item.name}:     {item.item.mobile}</Text>
                <TouchableOpacity>
                    <View style={{borderColor:'#cfcfcf',borderWidth:1,borderRadius:3,paddingHorizontal:10,paddingVertical:3}}>
                        <Text style = {{color:'#5a5a5a'}}>移除</Text>
                    </View>
                </TouchableOpacity>
                
            </View>
        )
    }
  
    render(){
        return(

            <View style = {styles.container}>

           
                
                <ShopAppointmentInputext 
                    titleStr="姓   名"
                    placeholder="请输入业务员姓名"
                    textChange={(text)=>{
                        this.setState({name:text})
                    }}
                    value={this.state.name}
                />

                <ShopAppointmentInputext 
                    titleStr="手机号"
                    KBT="numeric"
                    placeholder="请输入手机号"
                    textChange={(text)=>{
                        this.setState({mobile:text})
                    }}
                    value={this.state.mobile}
                    
                />

               


                <View style={styles.contain2}>

                    <View style={styles.leftCell}>
                        <Text style={{fontSize:14,color:'#4a4a4a',fontFamily:"PingFangSC-Regular"}}>
                            验证码
                        </Text>
                    </View>
                    <View style={{width:10,height:4}}/>

                    <View style={styles.leftCell}>
                        <TextInput
                            style={{height: 27,padding:0,width:screenWidth - 188,fontSize:12,color: '#4a4a4a'}}
                            placeholder={"请输入验证码"}
                            keyboardType={'default'}
                            underlineColorAndroid='transparent'
                            onChangeText={
                                (textValue) => this.setState({code: textValue})
                            }
                            value={this.state.code}
                            placeholderTextColor = {defaultFontColor.prompt_Font_Color}
                            maxLength = { 8 }
                        />

                        <TimerButton
                            style = {{width: 120, height: 40, padding: 0, margin: 0,backgroundColor: '#F63300'}}
                            textStyle = {{ color: '#fff' }}
                            timerCount = { 60 }
                            onClick = {(shouldStartCountting) => {

                                let isTelpNum = RegExpTool.phoneByReg(this.state.mobile);
                                if(!isTelpNum['check']){  //如果校验失败，不能开始倒计时
                                    shouldStartCountting(false);
                                    Toast.showWithGravity(isTelpNum['error'],1,Toast.CENTER)
                                }else{
                                    shouldStartCountting(true);
                                    //请求接口，获取验证码
                                    this._clickGetPhoneReqButton()
                                }
                            }}
                            disableColor= '#D2D2D2'
                            enable = {true}    //按钮可点击？
                        />
                    </View>
                </View>

                 <ShopAppointmentInputext 
                    isSecure={true}
                    titleStr="密   码"
                    placeholder="请输入业务员密码"
                    textChange={(text)=>{
                        this.setState({pw:text})
                    }}
                    value={this.state.pw}
                    
                />

                <View style={{width:screenWidth,height:20}}/>


                <Button
                    isLoading={this.state.isLoading}
                    isLoadStr="正在提交..."
                    style = {[styles.buttonCommit, {backgroundColor:defaultBackgroundColor.search_Background}]}
                    onPress={this._commitButton}
                >
                    <Text style = {{ fontSize: 18,color: defaultFontColor.background_Font_Color }}>
                        提交
                    </Text>
                </Button>
                <View style={{width:screenWidth,height:10}}/>

                
                
                
            </View>
        )
    }
}
const styles = StyleSheet.create({

    container: {
        flex:1,
    },
    buttonCommit: {
        left:15,
        width: screenWidth - 30,
        height: 46,
        justifyContent:'center',
        alignItems:'center',
        borderRadius: 6,
    },
    contain2: {
        backgroundColor:'white',
        width:screenWidth,
        height:40,
        flexDirection:'row',

        alignItems:'center',
        borderBottomColor:'#efefef',
        borderBottomWidth:1,
        paddingLeft:16,
        paddingRight:10,
    },
    leftCell:{
        height:40,
        flexDirection:'row',
        alignItems:'center',
    },
    
});


function select(store){
    return {
        store_id:store.userInfo.store_id,
    }
}

export default connect(select)(MineSalesmanCreate);

















