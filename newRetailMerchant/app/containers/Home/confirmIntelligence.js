import React, { Component } from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  FlatList,
  ScrollView,
  TextInput
} from 'react-native';
import {connect} from 'react-redux';
import {tabBarIconStyle, defaultBackgroundColor} from "../../utils/appTheme";
import TopBackHeader from '../../components/Header/Header';
import Toast from 'react-native-simple-toast'
import {information} from '../../network/shopNetApi';
import {handleUserInfomation} from '../LoginPage/utils/accountHandle';
class ConfirmIntelligence extends Component{

    constructor(props){
        super(props)
        this.state={

            loading:false
        }
        this._PressNextPage = this._PressNextPage.bind(this);
        this._getData = this._getData.bind(this);
        this._goToScreen = this._goToScreen.bind(this);
        this._renderCell = this._renderCell.bind(this);
    }

    static navigationOptions = ({navigation}) => ({
       
        header: <TopBackHeader 
        centerTitle="认证资质"

        leftPress={()=>{
            navigation.state.params.callBackData();
            navigation.goBack(); 
        }}

        />,
       
    });

    _getData=()=>{
        let formData={
            admin_id:this.props.admin_id,
        }
        information(formData,(response)=>{
            handleUserInfomation(this.props.dispatch,response);
        });
    }

    _PressNextPage(){

        if (this.props.storeProgress == 1){

            pageName="ConfirmShop";
            pageParams = {callBackData:this._getData};
            this._goToScreen(pageName,pageParams);

        }
        else if (this.props.storeProgress == 2){

            pageName="HomeShopIntelligence";
            pageParams = {
                store_id:this.props.storeInfo_storeID,
                store_name:this.props.store_name,
                store_address:this.props.store_address,
                callBackData:this._getData
            };
            this._goToScreen(pageName,pageParams);
        }
        else if (this.props.storeProgress == 3){
            Toast.showWithGravity("您的门店正在审核中，请耐心等候", 1, Toast.CENTER);
        }
        else{
          this.props.navigation.goBack();
        }

        
    }

    _goToScreen=(pageName="", pageParams={})=>{
        const {navigate}=this.props.navigation;
        navigate(pageName, pageParams)
    }

    _renderCell(isComplete,title){
        let imgPath = isComplete?require('../Mine/src/o1.png'):require('../Mine/src/o2.png');
        let stateStr=null;
        title == "资质审核" ?  stateStr = isComplete?"审核中":"未完成":
        stateStr = isComplete?"已完成":"未完成"; 
        let stateColor = isComplete?"#F63300":"#9b9b9b";

        return(

            <View style={{backgroundColor:'white',height:40,flexDirection:'row',justifyContent:'flex-start',alignItems:'center',width:screenWidth,paddingLeft:14,borderBottomColor:defaultBackgroundColor.page_Background_Color,borderBottomWidth:1}}>
                    <Image style={{width:18,height:18}} source={imgPath}/>
                    <Text  style={{color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:10}}>{title}</Text>
                    <Text  style={{color:stateColor,fontSize:14,fontFamily:'PingFangSC-Regular',marginLeft:21}}>{stateStr}</Text>
            </View>
        )
        
    }


    _getBtnStr(progress){

        // 1 已经注册 2已经认领 3已经提交资质  4 审核成功
        if(progress == 1){
            return "继续认领门店";
        }
        else if(progress == 2){
            return "继续提交资质";
        }
        else if(progress == 3){
            return "等待审核";
        }
        else{
            return "已完成";
        }
    }

    _getAraleImg(progress){

        if(progress == 1){

            return {
                imgP:require('./src/delete.png'),
                notiStr:"提交入驻申请后，我们会在3个工作日内给出审核结果；如有疑问请联系客服,我们将竭诚为您服务",
                title:"认领门店",
                titleState:"未完成"
            };

        }
        else if(progress == 2){

            return {
                imgP:require('./src/delete.png'),
                notiStr:"提交入驻申请后，我们会在3个工作日内给出审核结果；如有疑问请联系客服,我们将竭诚为您服务",
                title:"提交资质",
                titleState:"未完成"
            };

        }
        else if(progress == 3){

            return {
                imgP:require('./src/finshed.png'),
                notiStr:"提交入驻申请后，我们会在3个工作日内给出审核结果；如有疑问请联系客服,我们将竭诚为您服务",
                title:"等待审核",
                titleState:"未完成"
            };

        }
        else{

            return {
                imgP:require('./src/finshed.png'),
                notiStr:"",
                title:"等待审核",
                titleState:"已完成"
            };

        }
    }




    render(){

        const stateObj = this._getAraleImg(this.props.storeProgress);

        return (

            <View style={styles.container}>
            
                
                <View style={{width: screenWidth, alignItems: 'center',justifyContent:'center',paddingTop:50,paddingBottom:27}}>
                    <Image source={stateObj.imgP} style={{width: 48, height: 48,}}/>
                    <View style={{width:screenWidth,height:10}}/>

                    <Text style={{color: '#4a4a4a', fontSize: 17, fontFamily: 'PingFangSC-Regular'}}>{stateObj.title}-
                        <Text style={{color: '#f63300', fontSize: 17, fontFamily: 'PingFangSC-Regular'}}>{stateObj.titleState}</Text>
                    </Text>

                    <View style={{width:screenWidth,height:5}}/>

                    <View style={{width: screenWidth - 28, marginHorizontal: 14}}>
                        <Text style={{color: '#9b9b9b', fontSize: 12, fontFamily: 'PingFangSC-Regular'}}
                              numberOfLines={2}>{stateObj.notiStr}</Text>
                    </View>
                </View>
                
                {this._renderCell(this.props.storeProgress >= 1,"商户注册")}
                {this._renderCell(this.props.storeProgress >= 2,"认领门店")}
                {this._renderCell(this.props.storeProgress >= 3,"资质审核")}

                <TouchableOpacity
                    onPress={()=>{
                        this._PressNextPage();
                    }}
                >
                    <View style={{marginTop:17,marginBottom:19,backgroundColor:'#f63300',marginHorizontal:16,borderRadius:3,flexDirection:'row',alignItems:'center',justifyContent:'center',width:screenWidth-32,paddingVertical:13}}>
                            <Text  style={{color:'#fff',fontSize:16,fontFamily:'PingFangSC-Regular'}}>{this._getBtnStr(this.props.storeProgress)}</Text>
                    </View>

                </TouchableOpacity>
        
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
        orderIndex: store.orderInfo.orderState,
        store_id: store.userInfo.store_id,
        admin_id:store.userInfo.admin_id,
        storeProgress:store.storeInfo.progress,
        storeInfo_storeID:store.storeInfo.store_id,
        store_name:store.storeInfo.store_name,
        store_address:store.storeInfo.store_address,

    }
}
export default connect(select)(ConfirmIntelligence);