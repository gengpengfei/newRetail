import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableWithoutFeedback,
  Alert
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from "../../utils/appTheme";
import TopBackHeader from '../../components/Header/Header';
import {storeDetail,editStore} from '../../network/shopNetApi';
import {configServerImagePath} from '../../utils/commonMethod';
class OutOfTime extends Component{

    constructor(props){
        super(props)
        this.state={
           storeInfo:null,
           btn1:'营业',  
        }
    }

    static navigationOptions = ({navigation}) => ({
       
        header: <TopBackHeader 
        centerTitle='门店管理'
        leftPress={()=>{
            navigation.goBack(); 
        }}
        />,
       
    });


    _goToScreen=(str,dataParams)=>{
        const {navigate}=this.props.navigation
        navigate(str,dataParams)
    }


    componentDidMount(){
        this._storeDetail();   
    }

    _storeDetail(){
        let formData={
            store_id:this.props.store_id
        }
        storeDetail(formData,(response)=>{
            const {code =-1,msg='',data=null}=response;
            if(code == -1){

            }else{

                if(data.disabled == 0){
                    this.setState({
                        storeInfo:data,
                        btn1:'歇业', 
                        hours:data.store_hours,
                        storeGood:data.store_info
                    })
                }else  if(data.disabled == 1){

                    this.setState({
                        storeInfo:data,
                        btn1:'营业',
                        hours:data.store_hours,
                        storeGood:data.store_info
                    })
                }
               
            }
        });
    }

    _getData=()=>{
        this._storeDetail();
    }


    _editStore(str){
        let formData=null;
        if(str == 1){
          formData={
                store_id:this.props.store_id,
                disabled:0
            }

            Alert.alert('提示','是否确定歇业',[

                {text: '确定', onPress: () => {
                     
                    editStore(formData,(response)=>{ 
                        const {code =-1,msg=''.data=null}=response;
                        if(code == -1){
                        }else{ 
                            this._storeDetail();
                        }
                    });
                }},
                {text: '取消', onPress: () => {}},
    
            ])
        }else{
           formData={
                store_id:this.props.store_id,
                disabled:1
            }

            Alert.alert('提示','是否确定营业',[

                {text: '确定', onPress: () => {
                     
                    editStore(formData,(response)=>{ 
                        const {code =-1,msg=''.data=null}=response;
                        if(code == -1){
                        }else{ 
                            this._storeDetail();
                        }
                    });
                }},
                {text: '取消', onPress: () => {}},
    
            ])
        } 
           
    }

    render(){
        return (
            this.state.storeInfo==null?<View style={{flex:1,alignItems:'center',justifyContent:'center'}}><Text style={{color:'#9b9b9b',fontSize:16}}>正在加载中......</Text></View>:
            <View style={styles.container}>
               <View style={{height:120,flexDirection:'row',alignItems:'center',paddingLeft:14,paddingVertical:15,backgroundColor:'#ffffff'}}>
                   <Image style={{width:104,height:89}} source={{uri:configServerImagePath(this.state.storeInfo.store_img,'/small')}}/>
                   <View style={{marginLeft:17,}}>
                        <View style={{}}>
                             <Text  style={{color:'#4A4A4A',fontSize:16,fontFamily:'PingFangSC-Regular',fontWeight:'bold'}}>{this.state.storeInfo.store_name}
                             </Text>
                            <Text  style={{color:'#4A4A4A',fontSize:14,fontFamily:'PingFangSC-Regular',marginTop:3}}>好评率:                                   
                                    <Text  style={{color:'#f63300',fontSize:14,fontFamily:'PingFangSC-Regular'}}>{this.state.storeInfo.comment_ok_rate}</Text>
                            </Text>
                        </View>
                        <Text  style={{color:'#f5a623',fontSize:14,fontFamily:'PingFangSC-Regular',marginTop:20}}>{this.state.btn1}中</Text>    
                   </View>
               </View>

           
               <View style={{marginLeft:14,marginTop:11,flexDirection:'row',alignItems:'center'}}>
                  <Image style={{width:14,height:14}} source={require('./src/info.png')}/>
                  <Text  style={{color:'#f63300',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>基础信息</Text>
               </View>
           

                <TouchableWithoutFeedback onPress={()=>{
                    this._goToScreen('ShopInfo',{cont:this.state.storeInfo})
                }}>
                    <View style={{marginTop:7,borderBottomColor:defaultBackgroundColor.page_Background_Color,borderBottomWidth:1,flexDirection:'row',alignItems:'center',backgroundColor:'#ffffff',height:62}}>
                        <View style={{flex:1,flexDirection:'row',justifyContent:'center',alignItems:'center'}}>
                            <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>基础信息</Text>
                        </View>
                        <View style={{flex:2,flexDirection:'row',justifyContent:'space-between',paddingRight:14,alignItems:'center'}}>
                            <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',flex:9}} numberOfLines={1}>{this.state.storeInfo.store_name} {this.state.storeInfo.store_address}</Text>
                            <Image style={{width:8,height:13}} source={require('./src/lnto.png')}/>
                        </View>                           
                    </View>
               </TouchableWithoutFeedback>
              

              <TouchableWithoutFeedback onPress={()=>{
                    this._goToScreen('MineWorkTime',{
                        cont:this.state.storeInfo,
                        callBackData:this._getData
                    })
                }}>
                    <View style={{flexDirection:'row',alignItems:'center',backgroundColor:'#ffffff',height:62}}>
                            <View style={{flex:1,flexDirection:'row',justifyContent:'center',alignItems:'center'}}>
                                <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>营业时间</Text>
                            </View>
                            <View style={{flex:2,flexDirection:'row',justifyContent:'space-between',paddingRight:14,alignItems:'center'}}>
                                <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',flex:9}} numberOfLines={1}>{this.state.hours}</Text>
                                <Image style={{width:8,height:13}} source={require('./src/lnto.png')}/>
                            </View>                   
                    </View>
               </TouchableWithoutFeedback>

               <View style={{marginLeft:14,marginTop:11,flexDirection:'row',alignItems:'center'}}>
                  <Image style={{width:14,height:14}} source={require('./src/extre.png')}/>
                  <Text  style={{color:'#f63300',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>附加信息</Text>
               </View>
                

                <TouchableWithoutFeedback onPress={()=>{
                    this._goToScreen('EnvironmentMachine',{
                        callBackData:this._getData,
                        store_info:this.state.storeInfo.store_info
                    });
                }}>
                    <View style={{flexDirection:'row',alignItems:'center',backgroundColor:'#ffffff',height:62,marginTop:7}}>
                        <View style={{flex:1,flexDirection:'row',justifyContent:'center',alignItems:'center'}}>
                            <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:6}}>环境配套设施</Text>
                        </View>

                        <View style={{flex:2,flexDirection:'row',justifyContent:'space-between',paddingRight:14,alignItems:'center'}}>
                            <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',flex:9}} numberOfLines={1}>{this.state.storeGood}</Text>
                            <Image style={{width:8,height:13}} source={require('./src/lnto.png')}/>
                        </View>
                        
                    </View>
               </TouchableWithoutFeedback>
            
               {
                this.state.btn1 == '营业'?
               <View style={{marginHorizontal:30,flexDirection:'row',justifyContent:'space-between',position:'absolute',bottom:20,width:screenWidth-60,paddingVertical:13}}>
                        <TouchableWithoutFeedback onPress={()=>{
                            this._editStore(1);
                        }}>
                            <View style={{backgroundColor:'#FF6633',borderRadius:6,width:(screenWidth-60)/2-17,flexDirection:'row',alignItems:'center',justifyContent:'center'}}>
                                <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>歇业</Text>
                            </View>
                        </TouchableWithoutFeedback>
                        <TouchableWithoutFeedback onPress={()=>{
                            this._goToScreen('ShopManger',{
                                store_name:this.state.storeInfo.store_name,
                                callBackState:this._getData,    
                            });
                        }}>                      
                            <View style={{backgroundColor:'#f63300',borderRadius:6,width:(screenWidth-60)/2-17,flexDirection:'row',alignItems:'center',justifyContent:'center',paddingVertical:13}}>
                                <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>申请关店</Text>
                            </View>
                        </TouchableWithoutFeedback>
               </View>
                 :
               <View style={{marginHorizontal:30,flexDirection:'row',justifyContent:'space-between',position:'absolute',bottom:20,width:screenWidth-60,paddingVertical:13}}>

                    <TouchableWithoutFeedback onPress={()=>{
                         this._editStore(2);
                     }}>
                         <View style={{backgroundColor:'#f63300',borderRadius:6,width:(screenWidth-60)/2-17,flexDirection:'row',alignItems:'center',justifyContent:'center'}}>
                             <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>营业</Text>
                         </View>
                    </TouchableWithoutFeedback>
                    <TouchableWithoutFeedback onPress={()=>{
                     this._goToScreen('ShopManger',{
                         store_name:this.state.storeInfo.store_name,
                         callBackState:this._getData,    
                       });
                     }}>                      
                         <View style={{backgroundColor:'#f63300',borderRadius:6,width:(screenWidth-60)/2-17,flexDirection:'row',alignItems:'center',justifyContent:'center',paddingVertical:13}}>
                             <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>申请关店</Text>
                         </View>
                 </TouchableWithoutFeedback>
               </View>
            }
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


function select(store){
    return {   
        store_id: store.userInfo.store_id,    
    }
}
export default connect(select)(OutOfTime);