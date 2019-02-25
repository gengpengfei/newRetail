import React, {Component} from "react";
import {
    
    StyleSheet,
    View,
    Text,
    TextInput,
    
    TouchableHighlight,
    SafeAreaView
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
import {isNotEmptyString} from "../../utils/structureJudgment";
import {NavigationActions} from "react-navigation";
import Toast from 'react-native-simple-toast'
import {connect} from 'react-redux';
import { sendMobileCode, register } from "../../network/loginNetApi";
import { handleLoginData } from "./utils/accountHandle";
// import {} from '../../utils';

class Regist extends Component {
    constructor(props){
        super(props);
        this.state = {
            mobile: '',
            code_type: '',
            code: '',
            password: '',
            password_confirm: ''
        };

        //this._onPressButton = this._onPressButton.bind(this);
        this._clickLoginButton = this._clickLoginButton.bind(this);
        this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind(this);
        this._loginSuccess = this._loginSuccess.bind(this);
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="注册"/>,
    });

    _clickGetPhoneReqButton(){
        let mobile = this.state.mobile;
        let formData = {
            mobile: mobile,
            code_type: '0'
        }
        
        sendMobileCode(formData, ( responseData ) => {
            const {msg} = responseData;
            Toast.showWithGravity(msg,1,Toast.CENTER);
        })
    }

    _loginSuccess(){

        const resetAction = NavigationActions.reset({
            index: 0,
            actions: [
                NavigationActions.navigate({ routeName: 'RootTabNav',
                action:NavigationActions.navigate({
                    routeName: 'Home', // 这个是tabs 里面的任何一个tab
                })
              })
            ]
        })
        this.props.navigation.dispatch(resetAction)
        
        

        // this.props.navigation.goBack(this.props.navigation.state.params.gobackKey);
    }

    _clickLoginButton(){


        const {mobile="",code,password,password_confirm} = this.state;

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

        //新密码
        if(!isNotEmptyString(password)){
            Toast.showWithGravity("请输入密码",Toast.SHORT,Toast.CENTER)
            return ;
        }

        //确认新密码
        if(!isNotEmptyString(password_confirm)){
            Toast.showWithGravity("请输入确认密码",Toast.SHORT,Toast.CENTER)
            return ;
        }

        if(password != password_confirm){
            Toast.showWithGravity("密码输入不一致",Toast.SHORT,Toast.CENTER)
            return;
        }

        let isValid = RegExpTool.passwordByReg(password);
        if(!isValid['check']){
            Toast.showWithGravity("密码格式不正确，请重新输入密码",Toast.SHORT,Toast.CENTER)
            return ;
        }


        let formData = {
            mobile: mobile,
            code_type: 0,
            code: code,
            password: password,
            password_confirm: password_confirm,

        }

        register(formData,(responseData) => {
            const {code,data,msg} = responseData;
            
            handleLoginData(this.props.dispatch,responseData);
            if(code == 1){
                this._loginSuccess()
            }else{
                Toast.showWithGravity(msg,Toast.SHORT,Toast.CENTER)
            }
            
        })
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
                                onChangeText={(textValue) => this.setState({mobile: textValue})}
                                value={this.state.mobile}
                                placeholder="请输入手机号"
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                        </View>
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color  }}>
                                    验证码
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                onChangeText={(textValue) => this.setState({code: textValue})}
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
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color  }}>
                                    密码
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                onChangeText={(textValue) => this.setState({password: textValue})}
                                value={this.state.password}
                                placeholder="请输入密码"
                                secureTextEntry = {true}
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                        </View>
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{ fontSize: 14,color: defaultFontColor.main_Font_Color  }}>
                                    确认密码
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                onChangeText={(textValue) => this.setState({password_confirm: textValue})}
                                value={this.state.password_confirm}
                                placeholder="请输入确认密码"
                                secureTextEntry = {true}
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                        </View>
                        <TouchableHighlight
                            activeOpacity = { 0.5 }
                            underlayColor = {'transparent'}
                            style = {[styles.buttonCommit, {backgroundColor: (isNotEmptyString(this.state.password_confirm) ?  this.state.password === this.state.password_confirm : 0 ) ? defaultBackgroundColor.search_Background : defaultBackgroundColor.condition_Background,}]}
                            onPress={ this._clickLoginButton }
                        >
                            <Text style = {{ fontSize: 16,color: '#fff' }}>
                                注册
                            </Text>
                        </TouchableHighlight>
                        <View>
                            <Text style = {{ fontSize: 12, color: defaultFontColor.prompt_Font_Color, textAlign: 'center',paddingHorizontal: 15}}>
                                提示：长度在8位及以上，包含数字、大小写字母、特殊字符中的两种或以上。
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
export default connect(select)(Regist);















