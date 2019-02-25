/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */

import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from '../../components/Header/Header'
import VerifyCode from '../../components/VerifyCode'
import {
    StyleSheet,
    Text,
    View,
    TouchableOpacity,
} from 'react-native';

import Button from "../../components/Button";
// import Toast from 'react-native-simple-toast';
const CryptoJS = require('crypto-js');

class PayPassword extends Component<{}> {
    static navigationOptions = ({navigation}) => ({
        header: <Header
            centerTitle='支付密码'
            leftPress={() => {
                navigation.goBack();
            }}/>,
    });



    constructor(props){
        super(props);

        this._getButtonIsDisabled = this._getButtonIsDisabled.bind(this);
        this._onInputCompleted = this._onInputCompleted.bind(this);
        this._commit = this._commit.bind(this);
        this._modifPayPassword = this._modifPayPassword.bind(this);
        
        this.state = {
            payStep:this.props.pay_password?0:1,
            origin_PW:"",//原密码
            new_PW:"",//新密码
            new_again_PW:"",//重新输入密码
        }
    }

    _getSetPayPasswordStatus(idx){
        if(idx === 0){
            return "请输入原支付密码,以验证身份";
        }
        else if(idx === 1){
            return "请输入支付密码";
        }
        else{
            return "请重新输入支付密码";
        }
    }

    _getButtonStatus(idx){
        if(idx === 2){
            return "完成";
        }else{
            return "下一步";
        }
    }

    _getButtonIsDisabled(){
        const { payStep,origin_PW,new_PW,new_again_PW } = this.state;
        if(payStep === 0){
            return origin_PW.length !== 6;
        }
        else if(payStep === 1){
            return new_PW.length !== 6;
        }else{
            return new_again_PW.length !== 6;
        }

    }

    _onInputCompleted(text){
        const { payStep } = this.state;
        if(payStep === 0){
            this.setState({
                origin_PW:text,
            })
        }
        else if(payStep === 1){
            this.setState({
                new_PW:text,
            })
        }else{
            this.setState({
                new_again_PW:text,
            })
        }

    }


    _commit(){

        const { payStep,origin_PW,new_PW,new_again_PW } = this.state;

        if(payStep === 0){

            let origin_PW_md5 = CryptoJS.MD5(origin_PW).toString();
            if(origin_PW_md5 === this.props.pay_password){
                
                this.verifyCode.reset();
                this.setState({
                    payStep:1,
                })

            }else{
                Toast.show('支付密码输入错误，请重新尝试');
                
            }
            

            
        }
        else if(payStep === 1){

            this.verifyCode.reset();
            this.setState({
                payStep:2,
            })

        }else{
            if(new_PW !== new_again_PW){
                Toast.show("支付密码输入不一致");
                
            }else{
                this._modifPayPassword();
            }
            
        }

    }

    // 修改支付密码
    _modifPayPassword(){

        // let PW_md5 = CryptoJS.MD5(this.state.new_again_PW).toString();
        // let formData = {
        //     pay_password: PW_md5,
        // };
        
        // saveInformation(formData,(response)=>{

        //     const {data=null,code=1,msg=''}=response;
        //     if(code === 1){
        //         this.props.dispatch(userInfoAction('pay_password',data['pay_password']));
        //         storageLoginStatus(data['mobile'], data['token'],true);
        //         this.props.navigation.goBack();
        //     }else{
        //         Toast.show(msg);
                
        //     }
        // });

    }


    render() {

     
        return (
           

            <View style={styles.container}>

                <View style={{width:20,height:0.1*screenHeight}}/>

                <Text style={{color:'#4a4a4a'}}>
                {this._getSetPayPasswordStatus(this.state.payStep)}
                </Text>

                <VerifyCode
                    ref={(verifyCode)=>this.verifyCode = verifyCode}
                    verifyCodeLength={6}
                    containerPaddingVertical={20}
                    containerPaddingHorizontal={30}
                    containerBackgroundColor={'#fff'}
                    codeItemColor={'#fff'}
                    codeBorderColor={'#5b5b5b'}
                    codeBorderWidth={1}
                    onInputCompleted={this._onInputCompleted}
                    codeFocusedBorderColor={'red'}
                    secureTextEntry = {true}
                />
                
                <View style={{width:screenWidth,height:50,paddingHorizontal:5,paddingVertical:5}}>

                    <Button
                        isDisabled={this._getButtonIsDisabled()}
                        onPress={this._commit}
                        style={{flex:1,backgroundColor:'#F63300',borderRadius:5}}
                    >
                        <Text style={{fontSize:17,color:'#fff'}}>{this._getButtonStatus(this.state.payStep)}</Text>

                    </Button>
                </View>



            </View>
        );
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor:'#fff',
        alignItems:'center',
    },
});



function select(store){
    return {
       
    }
}

export default connect(select)(PayPassword);

