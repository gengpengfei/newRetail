
import React, { Component } from 'react';
import {
    StyleSheet,
    Text,
    View,
    Image,
    TouchableOpacity
} from 'react-native';
import {
    tabBarIconStyle,
    defaultContainerStyles,
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor,
} from "../../../utils/appTheme";

export default class TopContent extends Component {
    constructor(props) {
        super(props);

    }

    static defaultProps = {
        imgUrl: null,
        topName: null,
        number: null,
        contentName: null,
    }

    render() {
        let str = this.props.topName;
        let user_name = str.substr(0, 3) + "****" + str.substr(7);
        return (
            <View
                style={{ width: screenWidth, height: 160, paddingHorizontal: 15, paddingVertical: 20, flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}
            >
                <View style={{ flexDirection: 'row', width: screenWidth - 30, height: 48, paddingVertical: 10, alignItems: 'center', justifyContent: 'center', }}>
                    <Image
                        resizeMode='cover'
                        style={{ width: 30, height: 30, borderRadius: 15 }}
                        source={this.props.imgUrl ? { uri: this.props.imgUrl } : require('../src/defaultHead.png')}
                    />
                    <View style={{ flexDirection: 'column', height: 48, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 10 }}>
                        <Text style={{ fontFamily: 'PingFangSC-Regular', fontSize: 16, color: defaultFontColor.main_Font_Color, paddingVertical: 10 }}>{user_name}</Text>
                    </View>
                </View>
                {
                    this.props.contentName ? <View style={{ flexDirection: 'column', width: screenWidth - 30, height: 38, alignItems: 'center', justifyContent: 'center', }}>
                        <Text style={{ fontFamily: 'PingFangSC-Regular', fontSize: 32, color: defaultFontColor.main_Font_Color, paddingVertical: 10 }}>+ {this.props.number}</Text>
                    </View> : null
                }
                {
                    this.props.contentName ? <View style={{ flexDirection: 'column', width: screenWidth - 30, height: 38, alignItems: 'center', justifyContent: 'center', }}>
                        <Text style={{ fontFamily: 'PingFangSC-Regular', fontSize: 14, color: defaultFontColor.prompt_Font_Color, paddingVertical: 10 }}>{this.props.contentName}</Text>
                    </View> : null
                }

            </View>
        )
    }
}





