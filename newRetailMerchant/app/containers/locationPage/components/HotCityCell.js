
import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  Image,
  
} from 'react-native';
import { defaultSeparateLineColor,defaultFontColor, defaultBackgroundColor } from '../../../utils/appTheme';


const HotDefaultCity = (props)=>{

  const {
      onSelect = ()=>{},
      hotCityList=[],
    } = props;

  return (
    <View style={styles.HotDefaultCity}>
            {
                hotCityList.map((item,idx)=>{
                    return(
                        <TouchableOpacity
                            key={idx}
                            onPress = {()=>{
                                onSelect(item);
                            }}
                        >
                            <View  style={styles.hotCityList}>
                                <Text style={{fontSize: 14,color: defaultFontColor.main_Font_Color}}>
                                    {item.name}
                                </Text>
                            </View>
                        </TouchableOpacity>
                    )
                })
            }
        </View>
  );
};

const CurrentPosition = (props)=>{

    const {
        onSelect = ()=>{},
        hotCityList={},
      } = props;
  
    return (
      <View style={styles.HotDefaultCity}>
              <TouchableOpacity
                    onPress = {()=>{
                        onSelect(hotCityList);
                    }}
                >
                    <View  style={[styles.hotCityList,{flexDirection:'row'}]}>
                    <Image style={{width:20,height:15}} resizeMode = "contain" source={require("../resource/positionIcon.png")}/>
                    {/* <View style={{width:5,height:5}}/>   */}
                        <Text style={{fontSize: 14,color: defaultFontColor.main_Font_Color}}>
                            {hotCityList.display_name}
                        </Text>
                    </View>
                </TouchableOpacity>
          </View>
    );
  };


const styles = StyleSheet.create({

    HotDefaultCity:{
        flex:1,
        flexDirection: 'row',
        flexWrap: 'wrap',
        backgroundColor: defaultBackgroundColor.page_Background_Color,
        paddingHorizontal: 10,
        borderBottomWidth: 1,
        borderBottomColor: '#c0c0c0',
    },
    hotCityList:{
        width: screenWidth/3 - 20,
        height: 30,
        marginHorizontal: 5,
        marginVertical: 4,
        borderRadius: 5,
        borderWidth: 1,
        borderColor: defaultFontColor.prompt_Font_Color,
        alignItems: 'center',
        justifyContent:'center',
        backgroundColor:'#fff',
    }
});




module.exports = {
    HotDefaultCity,
    CurrentPosition,
}