/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */

import React, { Component } from 'react';
import {
    StyleSheet,
    Text,
    View,
    Image,
    TouchableOpacity
    
} from 'react-native';

export default class Header extends Component {
    constructor(props){
        super(props);
    }
    static defaultProps = {

        leftImg:require("./src/back_white.png"),
        leftPress:()=>{},
        rightImg:null,
        rightPress:()=>{},
        centerTitle:"   ",
        rightText:null
    }
    
    render() {
        return (
            <View style={styles.container}>
                <View style={{width:screenWidth,height:statusbarHeight}}/>
                <View style={{width:screenWidth,height:appBar_Height,paddingHorizontal:15,flexDirection:'row',justifyContent:'space-between',alignItems:'center'}}>
                    <TouchableOpacity 
                        onPress={this.props.leftPress}
                        style={{width:40,height:40,justifyContent:'center'}}>
                        {this.props.leftImg?<Image style={{width:20,height:20}} source={this.props.leftImg}/>:null}
                    </TouchableOpacity>

                    <Text style={{color:'#fff',fontSize:17,fontFamily:'PingFangSC-Regular'}}>{this.props.centerTitle}</Text>

                    <TouchableOpacity 
                        onPress={this.props.rightPress}
                        style={{width:40,height:40,flexDirection:'row-reverse',alignItems:'center'}}>
                        {this.props.rightImg?<Image style={{width:20,height:20}} source={this.props.rightImg}/>:
                        
                            this.props.rightText?<Text style={{fontSize:14,color:'#fff'}}>{this.props.rightText}</Text>:null
                        
                    }
                    </TouchableOpacity>

                </View>
            </View>

        );
    }
}

const styles = StyleSheet.create({
    container: {
        width:screenWidth,
        height:header_Height,
        backgroundColor: '#F55D00',
    }
});
