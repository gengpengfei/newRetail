/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */

import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from '../../components/Header/Header'
import {
    Platform,
    StyleSheet,
    Text,
    View,
    Image,
    Dimensions,
    TouchableOpacity,
    ScrollView,
    Alert
} from 'react-native';
import {getAddressLast} from '../../network/shopNetApi'
const width=Dimensions.get("window").width;
class cityPage extends Component<{}> {
    static navigationOptions = ({navigation}) => ({
        header: <Header
            centerTitle='地址选择'
           
            leftBack={() => {
                navigation.goBack();
            }}/>,
    });

    constructor(props){
        super(props);
        this.arr=[];
        this.addressArr=[];
        this.state={
            data:null,
            Id:null,
            province:null,
            city:null,
            CountyTown:null,
            address:null,
            IN1:null,
            IN2:null,
            IN3:null,
            msg:null
        };
        this._getAddressLast=this._getAddressLast.bind(this)
    }
    _getAddressLast(regionId){
        let fromData={
            region_id:regionId,
            sign:''
        }
        getAddressLast(fromData,(responseData)=>{
                
                if(responseData['code']===1){
                    if(responseData['data'].length>=1){
                        this.setState({
                            data:responseData['data']
                        })
                    }else{
                        this.OCallback()
                    }
                  
                }else{
                    this.setState({
                        msg:responseData['msg']
                    })
                }
        })
    }
    OCallback(){


        this.props.navigation.state.params.callBackData(this.addressArr[0],this.addressArr[1],this.addressArr[2],this.arr)
        this.props.navigation.goBack();
        // this.props.navigation.state.params.uPAddress([this.addressArr[0]+this.addressArr[1]+this.addressArr[2],this.arr])
    }
    currentIdx(){
        this._getAddressLast('');
        this.setState({
            province:null,
            city:null,
            CountyTown:null,
        });

    }
    currentIdx2(){
        this._getAddressLast(this.arr[0]);
        this.setState({
            city:null,
            CountyTown:null,
        })

    }
    currentIdx3(){
        this._getAddressLast(this.arr[1]);
        this.setState({
            CountyTown:null,
        })
    }
    upData(iD){
        this._getAddressLast(iD);
    }
    componentDidMount(){
        this._getAddressLast('');
    }
    render() {
        if(this.state.data){
            var getData=this.state.data;
            return (
                <View style={{}}>
                        <View style={{width:width,height:30,backgroundColor:"#ececec",flexDirection:"row",alignItems:"center",position:"relative",top:0}}>
                            <TouchableOpacity
                                onPress={()=>{
                                    this.currentIdx()
                                }}
                                style={{marginLeft:14}}><Text style={{fontSize:15,color:'black'}}>{this.state.province?this.state.province:"请选择"}</Text></TouchableOpacity>
                            {this.state.province? <TouchableOpacity
                                onPress={()=>{
                                    this.currentIdx2()
                                }}
                                style={{marginLeft:14}}><Text style={{fontSize:15,color:'black'}}>{this.state.city?this.state.city:"请选择"}</Text></TouchableOpacity>:null}
                            {this.state.city? <TouchableOpacity
                                onPress={()=>{
                                    this.currentIdx3()
                                }}
                                style={{marginLeft:14}}><Text style={{fontSize:15,color:'black'}}>{this.state.area?this.state.area:"请选择"}</Text></TouchableOpacity>:null}
                        </View>
                        <View style={{width:width,height:5,backgroundColor:'#fff'}}></View>
                     <ScrollView style={{width:width,height:"100%",backgroundColor:"#fff"  }}>
                    {getData.map((cont,index)=>{
                        return(
                            <TouchableOpacity
                                key={index}
                                onPress={()=>{
                                    this.upData(cont['region_id']);
                                    if(cont['p_id']===0){
                                        this.setState({
                                            province:cont["name"],
                                            Id:cont['region_id'],
                                            IN1:cont['region_id']
                                        });
                                        this.arr=[this.state.IN1];
                                        this.addressArr=[this.state.province]

                                    }
                                    if(this.state.province){
                                        this.setState({
                                            city:cont["name"],
                                            Id:cont['region_id'],
                                            IN2:cont['region_id']
                                        });
                                        this.arr=[this.state.IN1,cont['region_id']];
                                        this.addressArr=[this.state.province,cont["name"]]

                                    }
                                    if(this.state.city){
                                        this.setState({
                                            city:this.state.city,
                                            area:cont["name"],
                                            Id:cont['region_id'],
                                            IN2:this.state.IN2,
                                            IN3:cont['region_id']
                                        });
                                        this.arr=[this.state.IN1,this.state.IN2,cont['region_id']];
                                        this.addressArr=[this.state.province,this.state.city,cont["name"]]

                                    }
                                }}
                                style={{width:width,height:30,paddingLeft:14,borderBottomColor:'#ececec',justifyContent:'center',borderBottomWidth:1}}><Text style={{fontSize:15,color:'black'}}>{cont["name"]}</Text></TouchableOpacity>
                        )
                    })}
                </ScrollView>
                </View>
            )
        }else{
            return(
                <View style={{width:"100%",height:"100%",alignItems:'center',marginTop:"50%"}}>
                    <Text style={{fontSize:15,color:'black'}}>{this.state.msg?this.state.msg:"正在加载中......"}</Text>  
                </View>
            )
        }
      
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor:'#fff'

    }
});

function select(store) {
    return {
        // uuid:store.userInfo.user_id
    }
}

export default connect(select)(cityPage);