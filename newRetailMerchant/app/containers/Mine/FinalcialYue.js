import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  FlatList,
  Modal,
  TouchableOpacity
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from "../../utils/appTheme";
import TopBackHeader from '../../components/Header/Header';

export default class FinalcialYue extends Component{

    constructor(props){
        super(props)

        this.titleArr=['今日','最近7日','自定义时间',]
        this.state={

            loading:false,
            text:'今日',
            index:0,
            modalVisible:false
        }
    }

    static navigationOptions = ({navigation}) => ({
       
        header: <TopBackHeader 
        centerTitle="余额流水"

        leftPress={()=>{
            navigation.goBack(); 
        }}

        />,
       
    });


    setModalVisible(visible) {
        this.setState({modalVisible: visible});
      }

    _renderItem=(item,index)=>{
        return(
            <View style={{height:49,flexDirection:'row',justifyContent:'space-between',paddingHorizontal:14,alignItems:'center',backgroundColor:'white',borderBottomColor:defaultBackgroundColor.page_Background_Color,borderBottomWidth:1}}>
                <View>
                    <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>账单入余额</Text>
                    <Text  style={{color:'#4a4a4a',fontSize:12,fontFamily:'PingFangSC-Regular',marginLeft:6}}>2018-05-12 06:12:00</Text>
                </View>

                  <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>+41.76元</Text>
            </View>
        );
    }


    _createEmptyView(){
        return(
            <View style={{flex:1,justifyContent:'center',alignItems:'center'}}>
               <Text  style={{color:'#4a4a4a',fontSize:16,fontFamily:'PingFangSC-Regular'}}>暂无信息！！</Text>
            </View>
        );
    }
    render(){
        return (

            <View style={styles.container}>

             <TouchableOpacity
                // style={{position:'absolute',top:20,left:20}}
                onPress={()=>{ this.setModalVisible(!this.state.modalVisible)}}>
                <View style={{height:40,flexDirection:'row',justifyContent:'flex-start',paddingLeft:14,alignItems:'center',borderBottomColor:'#e6e6e6',borderBottomWidth:1,backgroundColor:'white'}}>
                    <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>{this.state.text}</Text>
                    <Image source={require('./src/xiala.png')} style={{width:13,height:8}}/>
                </View>
            </TouchableOpacity>
               <FlatList
                            data={[1,2,3,4,5,6]}
                            renderItem={({item,index}) => this._renderItem(item, index)}
                            ListEmptyComponent={this._createEmptyView()}
                            numColumns={1} // 设置列数
                            keyExtractor={(item, index) => index.toString()}
                            refreshing={this.state.loading}
                            onEndReachedThreshold={-0.05}
                            onEndReached={(info) => {
                            }}
                        />


               <Modal
              animationType={"slide"}
              transparent={true}
              visible={this.state.modalVisible}
              onRequestClose={() => {alert("Modal has been closed.")}}
             
              >
              <TouchableOpacity onPress={() => {
                this.setModalVisible(!this.state.modalVisible)
                }}
                style={{height:30,marginTop:70}}>
              </TouchableOpacity>

              <TouchableOpacity  
                onPress={() => {
                  this.setModalVisible(!this.state.modalVisible)
                }}
                style={{width:'100%',height:'100%',position:'relative',backgroundColor:'rgba(225,225,225,0.4)',}}>
                {this.titleArr.map((cont,index)=>{
                  return(
                    <View key={index} 
                        style={{width:'100%',paddingLeft:20,backgroundColor:"#fff",height:40,borderBottomColor:this.state.index===index?'#F63300':'#F6F6F6',
                                borderBottomWidth:1,justifyContent:"center"}}>
                        <TouchableOpacity onPress={() => {
                          this.setState({
                            index:index,
                            text:cont
                          })
                          if(index==2){
                            const {navigate}=this.props.navigation;
                            navigate('TimeSelection')
                          }
                          this.setModalVisible(!this.state.modalVisible)
                        }}>
                          <Text style={{width:screenWidth-50}}>{cont}</Text>
                        </TouchableOpacity>   
                    </View>
                  )
                })}
               
              </TouchableOpacity>
           </Modal>
            </View>
        );
    }
}


const styles=StyleSheet.create({
    container:{
        flex:1,
        backgroundColor:defaultBackgroundColor.page_Background_Color

    }
})