import React, {PureComponent} from 'react';
import {
    Platform,
    StyleSheet,
    ImageBackground,
    Text,
    View,
    Image,
    TouchableOpacity,
} from 'react-native';


import {isNotEmptyString, isString} from "../utils/structureJudgment";

export default class Header extends PureComponent {

    constructor(props) {
        super(props);

        this.state = {

        };
        //this.goToScreen = this.goToScreen.bind(this);

    }
    static defaultProps = {
        headerBackgroundColor: null,
        backgroundImg:require('./src/blank.png'),
        // backgroundImg: null,
        leftTitle: null,
        leftImg: require('./src/backed.png'),
        zoomName:'',
        onPressLeftBtn:()=>{
            
        },
        onPressRightBtn:()=>{
            
        },
        rightContent: null,

      
    };
    _handleZoonName(zoonName){
        if(isNotEmptyString(zoonName)){
            if(zoonName.length>4){
                return zoonName.substr(0,3)+'...';
            }else{
             return zoonName;
            }
        }
        else{
            return '';
        }
    }

    goToScreen(gotoScreen){
        this.props.backBtnOnPress(gotoScreen)
    }
    goToRightScreen(){
        this.props.onPressRightBtn()
    }

    render(){
        return(
            <View
                style={[styles.container,{backgroundColor:this.props.headerBackgroundColor}]}>
                

                <View style={styles.statusBar}/>
                <View style={[styles.appBar, {justifyContent:'center', alignItems:'center', backgroundColor:'white', borderBottomWidth:1, borderBottomColor: '#efefef'}]}>
                    <TouchableOpacity
                        onPress={
                            //this.goToScreen    //此种写法时注意将此方法用bind绑定到组件上改变方法里面的this
                            //this.props.backBtnOnPress   //此种用法this指向调用的组件
                            () => {
                                this.goToScreen()
                            }
                        }
                    >
                        {
                            this.props.leftTitle ? (
                                <View style={{flexDirection:'row',justifyContent:'center', alignItems:'center',}}>
                                    <Image style={{width: 20, height: 20, marginLeft: 10, marginRight: 0}}
                                           source={this.props.leftImg}/>
                                    <Text style={{width: 40, height: 20, fontSize: 16, color: '#F63300'}}>返回</Text>
                                </View>
                            ) : <Image style={{width: 20, height: 20, marginLeft: 15, marginRight: 25}}
                                       source={this.props.leftImg}/>
                        }
                        {/*this.props.（<Image style={{width:20,height:20, marginLeft:15, marginRight:25 }} source={this.props.leftImg}/>）*/}
                        {/*(<Image style={{width:20,height:20, marginLeft:15, marginRight:25 }} source={this.props.leftImg}/>*/}
                        {/*<Text style={{width:20,height:20 }}> 返回</Text>)*/}
                    </TouchableOpacity>
                    {/*, textAlign:'center'*/}
                    <View style={{flex:1,marginVertical:5,borderRadius:5}}>
                        <Text style={{fontSize: 18, color: '#030303',alignSelf: 'center'}}>{this.props.zoomName} </Text>
                    </View>

                    {this.props.rightContent ? <TouchableOpacity
                        // onPress={() => {
                        //     this.goToRightScreen()
                        // }}
                        onPress = {this.props.onPressRightBtn}
                    >
                        <View style={{width:60,height:appBar_Height,flexDirection:'row',alignItems:"center",justifyContent:'flex-end',marginRight:10}}>
                            {
                                isNotEmptyString(this.props.rightContent) ?
                                    <View style={{width:60,height:20, alignItems:"center",justifyContent:'center'}}>{this.props.rightContent}</View> :
                                    <Image style={{width:24,height:24, marginRight: 10}} source={this.props.rightContent}/>
                            }
                        </View>
                    </TouchableOpacity> : <View style={{width:60}}/>}
                </View>
            </View>
        );
    }
};
const styles = StyleSheet.create({
    container: {
        height:header_Height,
        width:screenWidth,
    },
    statusBar:{
        height:statusbarHeight,
        backgroundColor:"#fff"
    },
    appBar:{
        height:appBar_Height,
        flexDirection:'row',
    },
    appBarCenter:{
        flex:2,
        alignItems:'center',
        justifyContent:'center',
    }
});
