import React from 'react';
import {
    View,
    Text
} from 'react-native';
import { defaultSeparateLineColor,defaultBackgroundColor } from '../utils/appTheme';

const SeparatorSpace = ({aHeight=10,seperatorStr}) => (
    <View style={{width:screenWidth,height:aHeight,backgroundColor:defaultBackgroundColor.page_Background_Color,justifyContent:'center'}}>
        {seperatorStr?<Text style={{left:10,fontFamily:"PingFangSC-Regular",fontSize:14,color:'#4a4a4a'}}>{seperatorStr}</Text>:null}
    </View>
);


const SeparatorSpaceLight = ({aHeight=10}) => (
    <View style={{width:screenWidth,height:aHeight,backgroundColor:defaultSeparateLineColor.light_SeparateLine_Color}}/>
);

export {
    SeparatorSpace,
    SeparatorSpaceLight,
}