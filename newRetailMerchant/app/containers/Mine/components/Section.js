



import React from 'react';
import {
    View,
    Text,
    Image,
    StyleSheet,
    TouchableWithoutFeedback,
    TextInput
    
} from 'react-native';


function MineSection({style,iconImage=null,titleStr,rightImgPath=null,clickItem}){
    
    return(

        <TouchableWithoutFeedback 
            onPress={()=>{
                clickItem?clickItem(titleStr):null;
            }}
        >
            <View style={[styles.contain,style]}>

                <View style={styles.leftCell}>
                    {iconImage?<Image resizeMode="contain" style={{width:20,height:20}} source={iconImage}/>:null}
                    {iconImage?<View style={{width:10,height:7}}/>:null}
                    <Text style={{fontSize:14,color:'#4a4a4a',fontFamily:'PingFangSC-Regular'}}>
                        {titleStr}
                    </Text>
                </View>
                
                <Image style={{width:20,height:20}} source={rightImgPath}/>

            </View>

        </TouchableWithoutFeedback>
    )
}


function SetSection({style,iconImage=null,titleStr,rightStr,rightImgPath=null,clickItem,rightStrColor="#9b9b9b"}){
    
    return(

        <TouchableWithoutFeedback 
            onPress={()=>{
                clickItem?clickItem(titleStr):null;
            }}
        >
            <View style={[styles.contain,style]}>

                <View style={styles.leftCell}>
                    {iconImage?<Image resizeMode="contain" style={{width:20,height:20}} source={iconImage}/>:null}
                    {iconImage?<View style={{width:10,height:7}}/>:null}
                    <Text style={{fontSize:14,color:'#4b4b4b',fontFamily:"PingFangSC-Regular"}}>
                        {titleStr}
                    </Text>
                </View>

                <View style={styles.leftCell}>
                    
                    <Text style={{fontSize:14,color:rightStrColor,fontFamily:"PingFangSC-Regular"}}>
                        {rightStr}
                    </Text>

                    <Image style={{width:20,height:20}} source={rightImgPath}/>
                </View>
                

                

            </View>

        </TouchableWithoutFeedback>
    )
}

function ShopAppointmentInputext({style,titleStr,placeholder,value,textChange,KBT="default",isSecure=false}){
    
    return(
            <View style={[styles.contain2,style]}>

                <View style={styles.leftCell}>
                    <Text style={{fontSize:14,color:'#4a4a4a',fontFamily:"PingFangSC-Regular"}}>
                        {titleStr}
                    </Text>
                </View>
                <View style={{width:10,height:4}}/>

                <View style={styles.leftCell}>
                    <TextInput
                        placeholder={placeholder}
                        keyboardType={KBT}
                        underlineColorAndroid='transparent'
                        secureTextEntry={isSecure}
                        style={{height: 27,padding:0,width:screenWidth-100,fontSize:12,color: '#4a4a4a'}}
                        onChangeText={textChange}
                        value={value}
                    />
                </View>
            </View>
    )
}




var styles = StyleSheet.create({
   
    contain: {
        backgroundColor:'white',
        width:screenWidth,
        height:40,
        flexDirection:'row',
        justifyContent:"space-between",
        alignItems:'center',
        borderBottomColor:'#efefef',
        borderBottomWidth:1,
        paddingLeft:16,
        paddingRight:10,
    },
    contain2: {
        backgroundColor:'white',
        width:screenWidth,
        height:40,
        flexDirection:'row',
        
        alignItems:'center',
        borderBottomColor:'#efefef',
        borderBottomWidth:1,
        paddingLeft:16,
        paddingRight:10,
    },
    leftCell:{
        height:40,
        flexDirection:'row',
        alignItems:'center',
    },
    
});



export {
    MineSection,
    SetSection,
    ShopAppointmentInputext,
}