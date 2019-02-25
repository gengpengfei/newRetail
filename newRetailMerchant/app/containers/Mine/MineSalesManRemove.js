import React, {Component} from "react";
import {
    StyleSheet,
    View,
    Text,
    ScrollView,
    FlatList,
    TouchableOpacity,
    TextInput
} from "react-native";
import Header from '../../components/Header/Header'
import {connect} from 'react-redux';
import {SetSection} from './components/Section'
import { defaultFontColor,defaultBackgroundColor } from "../../utils/appTheme";
import Button from '../../components/Button';
import {memberList, delMember} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
import TimerButton from '../../components/TimerButton';
import { sendMobileCode } from "../../network/loginNetApi";

class MineSalesmanRemove extends Component {
    constructor(props){
        super(props);

        this._clickDeleteButton = this._clickDeleteButton.bind(this);

        this.state = {
            code:null,
            
        }

    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="移除业务员"/>,
    });


    _clickDeleteButton(){

        let item = this.props.navigation.state.params;
        let {admin_id,callBack} = item;
        
        let formData = {
            admin_id: admin_id,
            code_type:'12',
            code:this.state.code,

        };

        delMember(formData, (responseData) => {
            
            
            let {msg, code, data} = responseData;
            if(code === 1){
                callBack();
                this.props.navigation.goBack();

            }else{
                Toast.showWithGravity(msg,1,Toast.CENTER);
            }
        })
    }

    _renderItem(item){
        return (
            <View style = {{width:screenWidth,height:40,paddingHorizontal:15,flexDirection:'row',justifyContent:'space-between',alignItems:'center',borderBottomColor:'#efefef',borderBottomWidth:1}}>

                <Text style = {{color:'#5a5a5a'}}>{item.user_name}:     {item.mobile}</Text>
                <TouchableOpacity
                    onPress={() => {
                        
                    }}
                >
                    <Text style = {{color:'#5a5a5a'}}></Text>
                </TouchableOpacity>

            </View>
        )
    }

    _clickGetPhoneReqButton=()=>{

        let formData = {
            mobile: this.props.mobile,
            code_type: '12'
        }

        
        sendMobileCode(formData, ( responseData ) => {      
            const {code,msg}= responseData;
            Toast.showWithGravity(msg,1,Toast.CENTER);
        })

    }


    render(){

        let item = this.props.navigation.state.params;
        var str = this.props.mobile;
        var placeholderStr = str.substr(0,3)+"****"+str.substr(7);

        return(

            
                <ScrollView style={{flex:1,backgroundColor:'white'}}>

                    <Text style={{fontSize:12,color:'gray',paddingHorizontal:15,paddingVertical:10}}>为了您的账号安全,请发送手机号为{placeholderStr}的验证码</Text>

                    <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />
                    {this._renderItem(item)}
                    <View style = { styles.userContent }>
                            <View style = { styles.labelName }>
                                <Text style = {{fontSize: 14,color: defaultFontColor.main_Font_Color }}>
                                    验证码
                                </Text>
                            </View>
                            <TextInput
                                style = { styles.inputFile }
                                underlineColorAndroid="transparent"
                                keyboardType = "numeric"
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


                    <View style = {{borderBottomWidth:1, borderBottomColor: '#eee'}} />

                    

                    
                    <View style={{width:screenWidth,height:15}}/>


                    <Button
                        style = {[styles.buttonCommit, {backgroundColor:defaultBackgroundColor.search_Background}]}
                        onPress={this._clickDeleteButton}
                    >
                        <Text style = {{ fontSize: 18,color: defaultFontColor.background_Font_Color }}>
                            移除业务员
                        </Text>
                    </Button>
                    <View style={{width:screenWidth,height:10}}/>

                </ScrollView>

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
    
    
});

function select(store){
    return {

        mobile: store.userInfo.mobile,

    }
}

export default connect(select)(MineSalesmanRemove);

















