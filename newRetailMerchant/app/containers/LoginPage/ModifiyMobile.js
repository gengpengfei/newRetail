import React, { Component } from "react";
import {
    StyleSheet,
    View,
    Text,
    TextInput,
    TouchableOpacity,
} from "react-native";
import TimerButton from '../../components/TimerButton';
import Header from '../../components/Header/Header'
import Toast from 'react-native-simple-toast'
import RegExpTool from "../../utils/RegExpTool";

import {
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor
} from "../../utils/appTheme";

import {connect} from 'react-redux';
import { sendMobileCode, checkUnbindCode } from "../../network/loginNetApi";


class ModifiyMobile extends Component {
    constructor(props){
        super(props);
        this.state = {
            mobile: '',
            code: '',
            receiveCode: ''
        };
        

        this._clickGetPhoneReqButton = this._clickGetPhoneReqButton.bind(this);
        this._clickLoginButton = this._clickLoginButton.bind(this);
        this._isDisableForCommit = this._isDisableForCommit.bind(this);


    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="换绑手机"/>,
      
    });

    //请求验证码
    _clickGetPhoneReqButton(){
        let formData = {
            mobile: this.props.mobile,
            code_type: '4'
        }
        
        sendMobileCode(formData, ( responseData ) => {
            const {msg } = responseData;
            Toast.showWithGravity(msg,1,Toast.CENTER);
            
        })
    }
    //jie绑手机
    _clickLoginButton(){
        let formData = {
            mobile: this.props.mobile,
            msg_code: this.state.code,
        }

        
        
        checkUnbindCode(formData,(responseData) => {
            
            const {code,msg} = responseData;
    
            if(code === 1){
                const {navigate} = this.props.navigation;
                navigate("NewMobile");
            }else{
                Toast.showWithGravity(msg,1,Toast.CENTER);
            }
        })
    }

    _isDisableForCommit(){
        
        let asd = RegExpTool.numberRequire(this.state.code);
        let isable = this.state.code.length === 6 && asd.check;
        return !isable;
    }


    render(){
        var str = this.props.mobile;
        var placeholderStr = str.substr(0,3)+"****"+str.substr(7);

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
                                underlineColorAndroid="transparent"
                                keyboardType = "numeric"
                                onChangeText={
                                    (textValue) => {
                                        this.setState({mobile: textValue});
                                    }
                                }
                                editable = {false}
                                value={placeholderStr}
                                placeholder= {placeholderStr}     //此处应该是从状态里取出手机号。上一界面路由跳过来的参数this.props.navigation.state.params.newMobile
                                placeholderTextColor = "#ccc"
                                maxLength = { 20 }
                            />
                        </View>
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{fontSize: 14,color: defaultFontColor.main_Font_Color }}>
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
                                placeholderTextColor = { "#ccc" }
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
                        <TouchableOpacity
                            disabled={this._isDisableForCommit()}
                            style={[styles.buttonCommit,{backgroundColor:this._isDisableForCommit()?defaultBackgroundColor.condition_Background:defaultBackgroundColor.search_Background}]}
                            onPress={ this._clickLoginButton }
                        >
                            <Text style = {{ fontSize: 18,color: '#fff' }}>
                                验证后绑定新手机号
                            </Text>
                        </TouchableOpacity>
                    </View>
                </View>
            
        )
    }
}
const styles = StyleSheet.create({
    loginContent: {
        // height: 40,
    },
    userContent: {
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        height: 40,
        backgroundColor: 'white',
    },
    labelName: {
        paddingLeft:15,
        height: 40,
        justifyContent:'center',
        // alignItems:'center',
    },
    inputFile: {
        flex: 1,
        paddingLeft: 10,
        margin: 0,
        borderWidth: 0,
        fontSize: 14,
        color: defaultFontColor.main_Font_Color,
    },
    buttonCommit: {
        
        width: screenWidth - 20,
        height: 46,
        marginLeft: 10,
        marginRight: 10,
        justifyContent:'center',
        alignItems:'center',
        marginTop: 20,
        marginBottom: 10,
        borderRadius: 12,
    },
})



function select(store){
    return {
        mobile: store.userInfo.mobile,
    }
}
export default connect(select)(ModifiyMobile);












