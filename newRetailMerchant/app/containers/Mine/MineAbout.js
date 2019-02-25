import React, {Component} from "react";
import {
    StyleSheet,
    View,
    Text,
    Image
} from "react-native";
import Header from '../../components/Header/Header'
import {connect} from 'react-redux';
import {SetSection} from './components/Section'


class MineAbout extends Component {
    constructor(props){
        super(props);

        this._clickSectionItem = this._clickSectionItem.bind(this);
        
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="关于我们"/>,
    });


    _clickSectionItem(str){
        
        
    }


  
    render(){
        return(

            <View style = {styles.container}>
                <View style={{width:screenWidth,height:300,justifyContent:'center',alignItems:'center'}}>

                    <Image style={{width:150,height:150}} source={{uri:"http://a1.att.hudong.com/88/53/01300001309585136263538015274.jpg"}}/>
                    <Text style={{top:10,color:'#9b9b9b',fontSize:14,fontFamily:"PingFangSC-Regular"}}>新零售开店客户端</Text>                   
                    <Text style={{top:10,color:'#4a4a4a',fontSize:14,fontFamily:"PingFangSC-Regular"}}>当前版本：1.21.3</Text>
                </View>

               

                <SetSection 
                    clickItem={this._clickSectionItem}
                    rightImgPath = {require("./src/rightJ.png")}
                    titleStr="版本更新"
                      
                />
                
                
            </View>
        )
    }
}
const styles = StyleSheet.create({

    container: {
        flex:1,
    },
    
});


function select(store){
    return {
       
    }
}

export default connect(select)(MineAbout);

















