

import React, { Component } from "react";
import {
    StyleSheet,
    View,
    Text,
    TextInput,
    TouchableHighlight,
    
    
} from "react-native";

import {
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor
} from "../../utils/appTheme";

import TimerButton from '../../components/TimerButton';
import Header from '../../components/Header/Header'
import RegExpTool from "../../utils/RegExpTool";
import {connect} from 'react-redux';
import {isNumber} from "../../utils/structureJudgment";
import Toast from 'react-native-simple-toast'
import { sendMobileCode, bindNewMobile } from "../../network/loginNetApi";



class NewMobile extends Component {
    constructor(props){
        super(props);
        this.state = {
            mobile: '',
            code: '',
            receiveCode: null
        };
        //this._onPressButton = this._onPressButton.bind(this);
        this._clickLoginButton = this._clickLoginButton.bind(this);
        this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind(this);

    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="换绑手机"/>,
        
    });

    //请求验证码
    _clickGetPhoneReqButton(){
        let formData = {
            mobile: this.state.mobile,
            code_type: '5'
        }
        
        sendMobileCode(formData, ( responseData ) => {
            
            const {code,msg}=responseData;

            if(code == 1){
                let codeData = responseData['data'];
                this.setState({
                    receiveCode: codeData['code']
                })
            }

            Toast.showWithGravity(msg,1,Toast.CENTER);
        
        })
    }
    //绑定新手机
    _clickLoginButton(){

        
        let formData = {
            mobile: this.state.mobile,
            admin_id: this.props.admin_id,
            msg_code: this.state.code,
        }
        bindNewMobile(formData,(responseData)=>{

            const {code,msg} = responseData;
            if(code == 1){
                storageLoginStatus("  ", "  ",false);
                const {navigate} = this.props.navigation;
                navigate("loginAccount");

                Toast.showWithGravity("绑定手机号成功,请重新登录",2,Toast.CENTER);
            }else{
                Toast.showWithGravity(msg,1,Toast.CENTER);
            }

        })
        
    }

  

    render(){
        return(
            
                <View style={{flex:1}}>
                    <View style = {{height: 20}} />
                    <View style = { styles.loginContent }>
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color }}>
                                    手机号
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                keyboardType = "numeric"
                                underlineColorAndroid="transparent"
                                onChangeText={
                                    (textValue) => {
                                        this.setState({mobile: textValue});
                                    }
                                }
                                onBlur = {
                                    () => {

                                        if(this.state.mobile){  //如果校验失败，按钮不可点击
                                            let isTelpNum = RegExpTool.phoneByReg(this.state.mobile);
                                            if(!isTelpNum['check']){  //如果校验失败，提示密码格式问题
                                                Toast.showWithGravity(isTelpNum['phoneError'],1,Toast.CENTER);
                                                
                                            }
                                            
                                        }
                                    }
                                }
                                value={this.state.mobile}
                                placeholder="请输入手机号"
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                        </View>
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color }}>
                                    验证码
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                onChangeText={
                                    (textValue) => this.setState({code: textValue})
                                }
                                value={this.state.code}
                                placeholder= {"请输入验证码"}
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                            <TimerButton
                                style = {{width: 120, height: 40, padding: 0, margin: 0,backgroundColor: '#F63300'}}
                                textStyle = {{ color: '#fff' }}
                                timerCount = { 60 }
                                onClick = {(shouldStartCountting) => {
                                    let isTelpNum = RegExpTool.phoneByReg(this.props.mobile);
                                    if(!isTelpNum['check']){  //如果校验失败，不能开始倒计时
                                        shouldStartCountting(false);
                                        Toast.showWithGravity(isTelpNum['error'],1,Toast.CENTER);
                                        
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
                        <TouchableHighlight
                            activeOpacity = { 0.5 }
                            underlayColor = {'transparent'}
                            style = {[styles.buttonCommit, {backgroundColor: isNumber(this.state.receiveCode) ? (this.state.receiveCode + '' === this.state.code ? defaultBackgroundColor.search_Background : defaultBackgroundColor.condition_Background) : defaultBackgroundColor.condition_Background,}]}
                            onPress={ this._clickLoginButton }
                        >
                            <Text style = {{ fontSize: 16,color: '#fff' }}>
                                确认绑定
                            </Text>
                        </TouchableHighlight>
                    </View>
                </View>
            
        )
    }
}
const styles = StyleSheet.create({
    loginContent: {
        // height: 40,
        alignItems: 'center'
    },
    userContent: {
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        height: 40,
        backgroundColor: 'white',
    },
    labelName: {
        width: 120,
        height: 40,
        paddingHorizontal: 20,
        justifyContent:'center',
        alignItems:'flex-start',
        // textAlign: 'center',
        // textAlignVertical: 'center',
        // includeFontPadding: false,
        // fontSize: 16,
    },
    inputFile: {
        flex: 1,
        padding: 0,
        margin: 0,
        borderWidth: 0,
        fontSize: 14,
        color: defaultFontColor.main_Font_Color,
    },
    buttonCommit: {
        width: screenWidth - 30,
        height: 46,
        marginLeft: 10,
        marginRight: 10,
        justifyContent:'center',
        alignItems:'center',
        marginTop: 20,
        marginBottom: 10,
        borderRadius: 6,
    },
})



function select(store){
    return {
        mobile:store.userInfo.mobile,
        admin_id:store.userInfo.admin_id,
        
    }
}
export default connect(select)(NewMobile);

