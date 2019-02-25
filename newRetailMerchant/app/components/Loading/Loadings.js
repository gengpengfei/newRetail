import {
    View,
    Text,
    Dimensions,
    Image,
} from 'react-native';
const { width, height } = Dimensions.get('window');
import React, { Component } from 'react';

export const Loading = ({ loading }) => (
    loading === true ?
        <View style={{ width: width, height: height, position: 'absolute', backgroundColor: '#fff', justifyContent: 'center', alignItems: "center" }}>
            <View style={{ width: 200, height: 100, alignItems: 'center', justifyContent: 'space-around' }}>
                <Image style={{ width: 45, height: 45, }} source={require('./img/002.gif')} />
                <Text style={{ color: '#909090' }}>正在加载中...</Text>
            </View>
        </View>
        :
        <View></View>
);
