import React, { Component } from "react";
import {
    Platform,
    StyleSheet,
    View,
    Text,
    TextInput,
    TouchableHighlight,
    
} from "react-native";

import{NavigationActions} from 'react-navigation';
import {
    tabBarIconStyle,
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor
} from "../../utils/appTheme";
import Toast from 'react-native-simple-toast'
import {connect} from 'react-redux';
import Header from '../../components/Header/Header'
import RegExpTool from "../../utils/RegExpTool";
import {isNotEmptyString} from "../../utils/structureJudgment";
import { updatePassword } from "../../network/loginNetApi";
import { handleLoginData } from "./utils/accountHandle";

class ChangePassword extends Component {
    constructor(props){
        super(props);
        this.state = {
            newPassword: '',
            confirmPassword: '',
            buttonActive: false,

        };
        this._clickLoginButton = this._clickLoginButton.bind(this);
        this._Success = this._Success.bind(this);
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="找回密码"/>,
        
    });

    _Success(){
        const {isMineSet,gobackKey} = this.props.navigation.state.params;
        if(isMineSet){
            this.props.navigation.goBack(gobackKey);
        }else{
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
        }
    }

    

    _clickLoginButton(){

        const { newPassword:password,confirmPassword:password_confirm } = this.state;

        //新密码
        if(!isNotEmptyString(password)){
            Toast.showWithGravity("请输入新密码",Toast.SHORT,Toast.CENTER)
            return ;
        }

        //确认新密码
        if(!isNotEmptyString(password_confirm)){
            Toast.showWithGravity("请再次输入新密码",Toast.SHORT,Toast.CENTER)
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
            mobile: this.props.navigation.state.params.newMobile,
            code: this.props.navigation.state.params.lastCode,
            code_type: '3',
            password: this.state.newPassword,
            password_confirm: this.state.confirmPassword,
            ignore_code: true,
        };

        updatePassword(formData,(responseData) => {
            
            
            const {data,code,msg}= responseData;
            if(code == 1){
                this._Success();
            }

            handleLoginData(this.props.dispatch,responseData);
            Toast.showWithGravity(msg,Toast.SHORT,Toast.CENTER);
        })
    }

   
    

    render(){

        return(
            <View style={{flex: 1}}>
                <View
                    style = {{width: screenWidth,height: Platform.OS === 'ios' ? (screenHeight - 64) : (screenHeight - 56), backgroundColor: '#F6F6F6' }}
                >
                    <View style = {{width: screenWidth,alignItems: 'flex-start', paddingHorizontal: 20,paddingVertical: 20,}}>
                        <Text style = {{ fontSize: 14,color: defaultFontColor.element_Font_Color }} >{this.props.navigation.state.params.newMobile}，请重设您的密码！</Text>
                    </View>

                    <View style = { styles.loginContent }>
                        <View style = { styles.userContent }>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                // keyboardType = "numeric"
                                secureTextEntry = {true}
                                onChangeText={
                                    (textValueNew) => {
                                        this.setState({newPassword: textValueNew});

                                        if(this.state.confirmPassword){
                                            let isnewPassword = RegExpTool.passwordByReg(textValueNew);
                                            let isconfirmPassword = RegExpTool.passwordByReg(this.state.confirmPassword);
                                            if(isconfirmPassword['check'] && isnewPassword['check'] ){  //如果校验失败，按钮不可点击
                                                this.setState({buttonActive: true});
                                            }
                                        }
                                    }
                                }
                                onBlur = {
                                    () => {
                                        if(!this.state.newPassword){  //如果校验失败，按钮不可点击
                                            Toast.showWithGravity("请填写新密码",1,Toast.CENTER);
                                            
                                        }else{
                                            let isnewPassword = RegExpTool.passwordByReg(this.state.newPassword);
                                            if(!isnewPassword['check']){  //如果校验失败，按钮不可点击
                                                Toast.showWithGravity(isnewPassword['error'],1,Toast.CENTER);
                                            }
                                        }
                                    }
                                }
                                value={this.state.newPassword}
                                placeholder="请输入新密码"
                                placeholderTextColor = {defaultFontColor.prompt_Font_Color}
                                maxLength = { 20 }
                            />
                        </View>
                        <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                        <View style = { styles.userContent }>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                secureTextEntry = {true}
                                onChangeText={
                                    (textValue) => {
                                        this.setState({confirmPassword: textValue});

                                       
                                    }
                                }
                                value={this.state.confirmPassword}
                                placeholder= {"请再次输入新密码"}
                                placeholderTextColor = { defaultFontColor.prompt_Font_Color }
                                maxLength = { 20 }
                            />
                        </View>
                    </View>
                    <View style= {{height: 20}}/>
                    <TouchableHighlight
                        activeOpacity = { 0.5 }
                        underlayColor = {'transparent'}
                        style = {[styles.buttonCommit, {backgroundColor: isNotEmptyString(this.state.newPassword) ? (this.state.newPassword === this.state.confirmPassword ? defaultBackgroundColor.search_Background : defaultBackgroundColor.condition_Background ): defaultBackgroundColor.condition_Background, marginHorizontal: 15}]}
                        onPress={ this._clickLoginButton }
                    >
                        <Text style = {{ fontSize: 16,color: '#fff' }}>
                            提交
                        </Text>
                    </TouchableHighlight>
                    <View style = {{flexDirection: 'row', alignItems: 'center', justifyContent:'center', paddingVertical: 20}}>
                        <Text style = {{ fontSize: 12, color: defaultFontColor.prompt_Font_Color, textAlign: 'center',paddingHorizontal: 15}}>
                            提示：长度在8位及以上，包含数字、大小写字母、特殊字符中的两种或以上。
                        </Text>
                    </View>
                </View>
            </View>
        )
    }
}
const styles = StyleSheet.create({
    loginContent: {
        //height: 40,
        paddingHorizontal: 15,
        alignItems: 'center',
        backgroundColor: '#fff'
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
        // paddingLeft: 15,
        margin: 0,
        borderWidth: 0,
        fontSize: 14,
        color: defaultFontColor.main_Font_Color,
    },
    fastLoginCommit: {
        width: 160,
        height: 40,
        marginVertical: 10,
        alignItems: 'center',
        justifyContent: 'center',
        borderColor:'rgba(246,51,0,1)',
        borderWidth: 1
    },

    buttonCommit: {
        width: screenWidth - 30,
        height: 46,
        justifyContent:'center',
        alignItems:'center',
        borderRadius: 6,
    },
});

function select(store){
    return {  
        
    }
}
export default connect(select)(ChangePassword);
















