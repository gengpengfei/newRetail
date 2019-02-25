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


import {
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor
} from "../utils/appTheme";

import {isNotEmptyString} from "../utils/structureJudgment";

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
        leftImg: require('./src/backed.png'),
        zoomName:'',
        onPressLeftBtn:()=>{
            
        },
        onPressRightBtn:()=>{
            
        },
        rightContent: null
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
            <ImageBackground
                source={this.props.backgroundImg}
                style={[styles.container,{backgroundColor:this.props.headerBackgroundColor}]}>
                <View style={styles.statusBar}/>
                <View style={[styles.appBar, {flexDirection:'row',justifyContent:'center', alignItems:'center', backgroundColor:'white'}]}>
                    <TouchableOpacity
                        onPress={
                            //this.goToScreen    //此种写法时注意将此方法用bind绑定到组件上改变方法里面的this
                            //this.props.backBtnOnPress   //此种用法this指向调用的组件
                            () => {
                                this.goToScreen()
                            }
                        }
                    >
                        <Image style={{width:20,height:20, marginLeft:15, marginRight:25 }} source={this.props.leftImg}/>
                        {/*this.props.（<Image style={{width:20,height:20, marginLeft:15, marginRight:25 }} source={this.props.leftImg}/>）*/}
                        {/*(<Image style={{width:20,height:20, marginLeft:15, marginRight:25 }} source={this.props.leftImg}/>*/}
                        {/*<Text style={{width:20,height:20 }}> 返回</Text>)*/}
                    </TouchableOpacity>
                    {/*, textAlign:'center'*/}
                    <View style={{flex:1,marginVertical:5,borderRadius:5}}>
                        <Text style={{fontSize: 17, color: defaultFontColor.main_Font_Color,alignSelf: 'center'}}>{this.props.zoomName} </Text>
                    </View>

                    {this.props.rightContent ? <TouchableOpacity
                        // onPress={() => {
                        //     this.goToRightScreen()
                        // }}
                        onPress = {this.props.onPressRightBtn}
                    >
                        <View style={{width:60,height:appBar_Height,flexDirection:'row',alignItems:"center",justifyContent:'center',marginRight:10}}>
                            {/*<Image style={{width:20,height:20}} source={require('./src/RichScan.png')}/>*/}
                            <View style={{width:60,height:20, alignItems:"center",justifyContent:'center'}}>{this.props.rightContent}</View>
                        </View>
                    </TouchableOpacity> : <View style={{width:60}}/>}
                </View>
            </ImageBackground>
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
        backgroundColor:"#f63300"
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
