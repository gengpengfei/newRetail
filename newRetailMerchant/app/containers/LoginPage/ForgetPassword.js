import React, { Component } from "react";
import {

    StyleSheet,
    View,
    Text,
    TextInput,
    TouchableHighlight,
    Dimensions,
    SafeAreaView
} from "react-native";

import {
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor
} from "../../utils/appTheme";
import Toast from 'react-native-simple-toast'
import TimerButton from '../../components/TimerButton';
import Header from '../../components/Header/Header'
import RegExpTool from "../../utils/RegExpTool";
import {isNotEmptyString, isNumber} from "../../utils/structureJudgment";
import {connect} from 'react-redux';
import { sendMobileCode } from "../../network/loginNetApi";

class ForgetPassword extends Component {
    constructor(props){
        super(props);
        this.state = {
            username: null,
            password: null,
            mobile: '',
            code: '',
            receiveCode: ''

        };
        
        this._clickLoginButton = this._clickLoginButton.bind(this);
        this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind(this);

    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="修改密码"/>,
        
    });

    _clickGetPhoneReqButton(){
        let formData = {
            mobile: this.state.mobile,
            code_type: '7'
        }
        
        sendMobileCode(formData, ( responseData ) => {
        
            const {code,msg} = responseData;
            if(code === 1){
            
                let codeData = responseData['data'];
                this.setState({
                    receiveCode: codeData['code']
                });
            }

            Toast.showWithGravity(msg,1,Toast.CENTER);
        })

    }
    _clickLoginButton(){


        const {mobile="",code,receiveCode} = this.state;

        // 手机号
        let isTelpNum = RegExpTool.phoneByReg(mobile);
        if(!isTelpNum['check']){
            Toast.showWithGravity("请输入正确的手机号",Toast.SHORT,Toast.CENTER)
            return ;
        }

        //验证码
        if(!isNotEmptyString(code)){
            Toast.showWithGravity("请输入验证码",Toast.SHORT,Toast.CENTER)
            return ;
        }

        let strRecCode = receiveCode + '';


        if(strRecCode == code){
            const {navigate} = this.props.navigation;
            let paras = {
                newMobile: mobile,
                lastCode: code,
                gobackKey:this.props.navigation.state.key,
                isMineSet:this.props.navigation.state.params.isMineSet,
            }
            navigate('ChangePassword',paras);
        }else{
            
            Toast.showWithGravity('请输入正确手机号或验证码',1,Toast.CENTER);
        }
       
    }

  

    render(){
        return(
            <SafeAreaView>
                <View>
                    <View style = {{height: 20}} />
                    <View style = { styles.loginContent }>
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color}}>
                                    手机号
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                keyboardType = "numeric"
                                onChangeText={
                                    (textValue) => {
                                        this.setState({mobile: textValue});
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
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color}}>
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
                                    let isTelpNum = RegExpTool.phoneByReg(this.state.mobile);
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
                            style = {[styles.buttonCommit, {backgroundColor:defaultBackgroundColor.search_Background}]}
                            onPress={ this._clickLoginButton }
                        >
                            <Text style = {{ fontSize: 16,color: '#fff' }}>
                                提交
                            </Text>
                        </TouchableHighlight>
                        <View>
                            <Text style = {{ fontSize: 12, color: '#898989',textAlign: 'center' }}>
                                手机号码无法接收短信，请联系客服！
                            </Text>
                        </View>
                    </View>
                </View>
            </SafeAreaView>
        )
    }
}
const styles = StyleSheet.create({
    loginContent: {
        
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
        
    }
}
export default connect(select)(ForgetPassword);











