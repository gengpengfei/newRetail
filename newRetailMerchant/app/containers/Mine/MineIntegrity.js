import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity
} from 'react-native';
import {connect} from 'react-redux';

import {MineSection} from './components/Section'
import ParallaxScrollView from '../../components/parallaxView';
import { defaultBackgroundColor} from "../../utils/appTheme";
const PARALLAX_HEADER_HEIGHT = header_Height + 90;


// storeDefaultConfigList
class MineIntegrity extends Component {
  constructor(props) {
    super(props);

    
  }

  static navigationOptions = ({navigation}) => ({
        header:null,
    });

  

    _renderSection(secStr){

        return (
            <View style={{width:screenWidth,height:50,paddingHorizontal:15,flexDirection:'row',alignItems:'center'}}>
                <Image style={{width:20,height:20}} source={require('./src/xinyufen.png')}/>
                <Text>{"  "+secStr}</Text>
            </View>
        )
    }

    _xinyongLevelScore(score){
        return(
            <View style={{height:25,flexDirection:'row',alignItems:'center',paddingLeft:5}}>
                <Image style={{width:20,height:20}} source={require('./src/xinyufen.png')}/>
                <Text style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>  信用分：{score}</Text>
            </View>
        )
    }

    _myScoreLog(timeStr,desStr,score){
        return(
            <View style={{width:screenWidth-30,height:25,flexDirection:'row',justifyContent:'space-between',alignItems:'center',paddingLeft:5}}>
                
                <Text style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>{timeStr}  {desStr}</Text>
                <Text style={{color:'#F63300',fontSize:14,fontFamily:'PingFangSC-Regular'}}>{score}  </Text>
            </View>
        )
    }


    render() {

        return (

            <ParallaxScrollView
                outputScaleValue={10}
                renderFixedHeader={()=>(
                    <View style={{flex:1,backgroundColor:'transparent'}}>
                        <View style={{width:screenWidth,height:statusbarHeight}}/>
                        <View style={{width:screenWidth,height:appBar_Height,paddingHorizontal:15,flexDirection:'row',justifyContent:'space-between',alignItems:'center'}}>
                            <TouchableOpacity 
                                onPress={()=>{
                                    this.props.navigation.goBack();
                                }}
                                style={{width:40,height:40,justifyContent:'center'}}>
                                <Image style={{width:20,height:20}} source={require("./src/back_white.png")}/>
                            </TouchableOpacity>

                            <Text style={{color:'white',fontSize:17}}>诚信等级</Text>
                            <View style={{width:40,height:40,justifyContent:'center'}}/>
                    
                            
                        </View>
                    </View>
                )}
                parallaxHeaderHeight={ PARALLAX_HEADER_HEIGHT }
                renderBackground={() => (
                <View key="background">
                    <Image style={{width:screenWidth,height:PARALLAX_HEADER_HEIGHT}} source={require('./src/headerBackground.png')}/>

                </View>
                )}
                renderForeground={() => (
                <View style={{ height: PARALLAX_HEADER_HEIGHT, flex: 1}}>
                        <View style={{width:screenWidth,height:header_Height}}/>

                        <View style={{width:screenWidth,height:40,paddingLeft:30,paddingRight:15,flexDirection:'row'}}>
                            <View style={{flex:1,flexDirection:'row',alignItems:'center'}}>
                                <Text style={{color:'#fff',fontSize:17,fontFamily:'PingFangSC-Medium'}}>筱熙</Text>
                                <View style={{width:10,height:5}}/>
                                <Image style={{width:20,height:20}}  source={require('./src/xinyufen.png')}/>
                            </View>
                            <View style={{flex:1,flexDirection:'row-reverse',alignItems:'center'}}>
                                <Text style={{color:'#fff',fontSize:12,fontFamily:'PingFangSC-Regular',textDecorationLine:"underline"}}>信用分规则</Text>
                            </View>

                        </View>

                        <View style={{width:screenWidth,height:30,paddingLeft:30,paddingRight:15,flexDirection:'row',alignItems:'center'}}>
                            
                            <Text style={{color:'#fff',fontSize:14,fontFamily:'PingFangSC-Medium'}}>信用分</Text>
                            <Text style={{color:'#fff',fontSize:17,fontFamily:'PingFangSC-Medium'}}>{"  "}52</Text>
                            <Text style={{color:'#fff',fontSize:12,fontFamily:'PingFangSC-Medium'}}>{"  "}距离下一个等级还差10分</Text>
                            
                        </View>

                </View>
                )}
                >
                <View style={styles.container}>
                
                    {this._renderSection("我的信用等级")}
                    <View style={{paddingHorizontal:15}}>

                        <View style={{flex:1,paddingVertical:10,backgroundColor:'white'}}>
                            {this._xinyongLevelScore("0~99")}
                            {this._xinyongLevelScore("100~199")}
                            {this._xinyongLevelScore("200~299")}
                            {this._xinyongLevelScore("300~400")}
                        </View>
                        
                    </View>
                    {this._renderSection("信用分变更记录")}


                    <View style={{paddingHorizontal:15}}>

                        <View style={{flex:1,paddingVertical:10,backgroundColor:'white'}}>

                            {this._myScoreLog("2018-06-03 13:23","达到升级标准","+20")}
                            {this._myScoreLog("2018-06-03 13:23","达到升级标准","+10")}
                            {this._myScoreLog("2018-06-03 13:23","达到升级标准","+50")}
                            {this._myScoreLog("2018-06-03 13:23","达到升级标准","-20")}
                            

                        </View>

                    </View>

                </View>
                
                

                

            </ParallaxScrollView>

        );
    }
}


const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor:defaultBackgroundColor.page_Background_Color,
  },
 

});

function select(store){
    return {
       
    }
}
export default connect(select)(MineIntegrity);