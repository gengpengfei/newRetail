import React, {Component} from "react";
import {
    StyleSheet,
    View,
    Text,
    Image,
    TouchableOpacity,
    ScrollView,
    TextInput,
    NativeModules,

} from "react-native";
import Header from '../../components/Header/Header'
import {connect} from 'react-redux';
import {SetSection} from './components/Section'
import { defaultFontColor,defaultBackgroundColor } from "../../utils/appTheme";
import Button from '../../components/Button';
import {isNotEmptyArray} from "../../utils/structureJudgment";
import RegExpTool from "../../utils/RegExpTool";
import ActionSheet from 'react-native-actionsheet'
import {getUploadImg,storeOpinionSub} from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
var ImagePicker = NativeModules.ImageCropPicker;

class MineOpinionNew extends Component {
    constructor(props){
        super(props);

        this._renderStyleStr = this._renderStyleStr.bind(this);

        this.imgArr=[];
        this.state = {
            styleStr:"评价问题",
            opinionContext:'',
            phoneNum:'',
            isLoading:false,
            selectImage: {uri: 'https://tse4-mm.cn.bing.net/th?id=OIP.j1wEZFAUtrnCkiLF0_9YhQHaEK&p=0&o=5&pid=1.1', width: null, height: null},
            images: [],
            btnActive: true,
            btnContent: '发表'
        };


        this._handlePress = this._handlePress.bind(this);
        this._openCamera = this._openCamera.bind(this);
        // this._openImagePicker = this._openImagePicker.bind(this);
        this._pickMultiple = this._pickMultiple.bind(this);
        this.showActionSheet = this.showActionSheet.bind(this);
        this._renderImage = this._renderImage.bind(this);
        
        this._storeOpinionSub=this._storeOpinionSub.bind(this);
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="意见反馈"/>,
    });


    //操作弹窗
    showActionSheet() {
        this.ActionSheet.show();
    }
    //拍照/从相册中选头像
    _handlePress(i) {
        // this.setState({
        //     btnContent: '上传中',
        //     btnActive: false,
        // });
        if (i === 0) {
            // 拍照
            this._openCamera();
        } else if (i === 1) {
            // 从相册选择多张图片
            this._pickMultiple();
        } else {

            this.setState({
                btnContent: '提交反馈',
                btnActive: true,
            });
        }
    }

    _openCamera(){
        ImagePicker.openCamera({
            compressImageQuality: 0.2,
            includeBase64: true,
            cropping: true,
        }).then(
            (image) => {
                this.setState({
                    selectImage: {uri: `data:${image.mime};base64,`+ image.data,},
                });
                var imageArray = `data:${image.mime};base64,`+ image.data;

                this.imgArr.push(imageArray);
                this._getUploadImg()
            }
        ).catch(
            e => alert(e)
        )
    }

    _pickMultiple() {
        ImagePicker.openPicker({
            multiple: true,
            waitAnimationEnd: false,
            includeExif: true,
            includeBase64: true,  //上传数据给后台的必选条件
            compressImageQuality: 0.2,
            //maxFiles: 6 (ios only)
        }).then(images => {
            this.setState({
                image: null,
                images: images.map((i,index) => {
                    if(index<=9){
                        return {uri: i.path, data: `data:${i.mime};base64,`+ i.data};
                    }else{
                        return null
                    }
                })
            });


            if(this.state.images){
                this.state.images.map((item, index) => {
                    if(item){
                        this.imgArr[index]=item['data']
                    }
                })
            };
            // this._getUploadImg();

        }).catch(e => alert(e));
    }

   //上传照片
    _getUploadImg=()=>{
        if(this.imgArr){
            this.setState({
                btnContent: '图片上传中',
                btnActive: false,
            });
        }
        let formData = {
            type: "storeAudit",
            img_base64: this.imgArr,
            store_id:this.props.store_id
        };
        getUploadImg(formData,(response)=>{
            const {data=null,code=1,msg=''}=response;
            if(code == 1){
                this.setState({
                    getCommentImg: data,
                    btnActive: true,
                    btnContent: '发表'
                },()=>{
                    this._storeOpinionSub();
                });
            }else{
                Alert.alert(msg)
            }
        });
    }

    _getOrderComment(){
        if(isNotEmptyArray(this.state.images)){
            var formData={
                store_id:this.props.navigation.state.params.store_id,
                order_id:this.props.navigation.state.params.order_id,
                // user_id: 63,
                // store_id:1,
                // order_id:28,
                comment_num:this.state.commentStar,  //1，2，3，4对应级别
                comment_cont:this.state.commentContent,
                has_img:1,
                comment_img: encodeURI(this.state.getCommentImg),
                store_pro_list: this.state.subRecommendList,
                price: this.state.consumeLevel,
                is_hide: this.state.switchBool == true ? 1 : 0,
                //是否为匿名发表   this.state.switchBool   为boolen值
            }
        }else{
            var formData={
                store_id:this.props.navigation.state.params.store_id,
                order_id:this.props.navigation.state.params.order_id,
                // user_id: 63,
                // store_id:1,
                // order_id:28,
                comment_num:this.state.commentStar,
                comment_cont:this.state.commentContent,
                has_img:0,
                comment_img:'',
                store_pro_list: this.state.subRecommendList,
                price: this.state.consumeLevel,
                is_hide: this.state.switchBool == true ? 1 : 0,
                //是否为匿名发表   this.state.switchBool   为boolen值
            }
        };

        getOrderComment(formData,(response)=>{
            let {code, msg, data} = response;
            if(code == 1){
                Alert.alert(msg);
                if(this.props.navigation.state.params.flag==1){

                    this.props.navigation.state.params.CommentType();
                }else if(this.props.navigation.state.params.flag==2){
                    this.props.navigation.state.params.ToComment();
                }
                this.props.navigation.goBack();
            }else{
                Alert.alert(msg);
            }
        });
    }



    //渲染照片
    _renderImage(image) {

        return (
            <View style={{width: screenWidth / 6.5,marginRight: 6, marginBottom: 6}}>
                <Image style={{width: screenWidth / 6.5, height: screenWidth / 6.5, resizeMode: 'cover',}} source={image} />
            </View>
        )
    }

    _renderSection(str){
        return (
            <View style={{width:screenWidth,height:45,justifyContent:'center'}}>
                <Text style={{left:15,color:'#4a4a4a',fontSize:14,fontFamily:'PingFangSC-Regular'}}>{str}</Text>
            </View>
        )
    }

    _renderStyleStr(str){
        let r_color = this.state.styleStr === str?"#F63300":"#9b9b9b";

        return(
            <TouchableOpacity 
                onPress={()=>{
                    this.setState({
                        styleStr:str,
                    })
                }}
                style={{paddingHorizontal:15,margin:5,paddingVertical:5,borderColor:r_color,borderWidth:1,borderRadius:5}}
            >
                <Text style={{fontSize:12,color:r_color,fontFamily:'PingFangSC-Regular'}}>{str}</Text>
            </TouchableOpacity>
        )
    }

    _commitButton=()=>{ 
            this.state.images?
            this._getUploadImg():
            this._storeOpinionSub();
    }

    _storeOpinionSub(){
        let formData={
            opinion_type:this.state.styleStr,
            opinion_img:encodeURI(this.state.getCommentImg),
            opinion_info:this.state.opinionContext,
            opinion_mobile:this.state.phoneNum,
            store_id:this.props.store_id
        }

        

        let isPhoneNum=RegExpTool.phoneByReg(this.state.phoneNum);
        if(!isPhoneNum['check']){
            Toast.showWithGravity(isPhoneNum['error'],1,Toast.CENTER);
        }else if(this.state.styleStr == ''  || this.state.opinionContext == ''){
            Toast.showWithGravity('数据不能为空,请将数据填写完整',1,Toast.CENTER);
        }else{
            storeOpinionSub(formData,(response)=>{
                
                const {code =-1,msg='',data=null}=response;
                if(code == -1){

                }else{
                   // Alert.alert(msg)
                     Toast.showWithGravity('反馈发表成功',1,Toast.CENTER);
                    this.props.navigation.goBack();
                }
               
            })
        }
    }
  
    render(){
        return(

            <View style = {styles.container}>
                <ScrollView style={{flex:1}}>
                
                    {this._renderSection("反馈类型")}
                    
                    <View style={{paddingHorizontal:10,flexDirection:'row',flexWrap:'wrap',borderBottomColor:'#dfdfdf',borderBottomWidth:1}}>

                        {this._renderStyleStr("评价问题")}
                        {this._renderStyleStr("对账问题")}
                        {this._renderStyleStr("产品难用，不会用")}
                        {this._renderStyleStr("验券问题")}
                        {this._renderStyleStr("经营数据")}
                        {this._renderStyleStr("账号问题")}
                        {this._renderStyleStr("其他问题")}

                    </View>
                    {this._renderSection("反馈内容")}

                    <View style={{width:screenWidth,height:120,paddingHorizontal:15,borderBottomColor:'#dfdfdf',borderBottomWidth:1}}>
                        <TextInput
                            style = {{flex:1, fontSize: 12, color: '#898989', alignItems: 'flex-start' }}
                            underlineColorAndroid="transparent"
                            onChangeText={
                                (textValue) => {
                                    this.setState({opinionContext: textValue});
                                }
                            }
                            value={this.state.opinionContext}
                            textAlignVertical = 'top'
                            placeholder="请留下您的宝贵意见，我们将努力改进"
                            placeholderTextColor = {defaultFontColor.prompt_Font_Color}
                            multiline= {true}
                            maxLength = { 500 }
                        />
                    </View>

                    {this._renderSection("上传图片")}

                    <View style={{width: screenWidth,}}>
                        <View style={{width: screenWidth, paddingHorizontal: 15,paddingTop: 15, paddingBottom: 5,flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', justifyContent:'flex-start', backgroundColor: "#fff"}}>
                            {
                                isNotEmptyArray(this.state.images) ? this.state.images.map(i =>
                                    <View key={i.uri}>{this._renderImage(i)}</View>
                                ) : (<Image style={{width: screenWidth / 6.25, height: screenWidth / 6.25, resizeMode: 'contain',}} source={require("./src/Plus.png")} />)
                            }
                        </View>
                        <View style={{width: screenWidth, height: 20, paddingHorizontal: 15,flexDirection: 'row', alignItems: 'center', justifyContent:'flex-start', backgroundColor: "#fff"}}>
                            <TouchableOpacity
                                onPress={
                                    () => {
                                        this.showActionSheet();
                                    }
                                }
                                style={{width: screenWidth / 3, flexDirection: 'column', alignItems: 'flex-start', justifyContent:'center',}}
                            >
                                <Text style={{fontSize: 12, paddingBottom: 10, color: defaultFontColor.default_Font_Color}}>最多上传三张图片</Text>
                            </TouchableOpacity>
                        </View>
                    </View>

                    {this._renderSection("联系电话")}
                    <View style={{width:screenWidth / 2,height: 30, marginHorizontal:15, paddingHorizontal: 3, paddingVertical: 5, borderColor:'#dfdfdf',borderWidth:1}}>
                        <TextInput
                            style = {{flex:1, fontSize: 12, color: '#898989',alignItems: 'flex-start',padding: 1, margin: 0, borderWidth: 0,}}
                            underlineColorAndroid="transparent"
                            onChangeText={
                                (textValue) => {
                                    this.setState({phoneNum: textValue});
                                }
                            }
                            value={this.state.phoneNum}
                            textAlignVertical = 'top'
                            placeholder="请填写您的手机号码"
                            placeholderTextColor = {defaultFontColor.prompt_Font_Color}
                            maxLength = { 16 }
                        />
                    </View>

                    <ActionSheet
                        ref={o => this.ActionSheet = o}
                        // title={title}
                        options={['拍照', '从相册选择', '取消' ]}
                        cancelButtonIndex={2}
                        destructiveButtonIndex={1}
                        onPress={this._handlePress}
                    />

                </ScrollView>

                <Button
                    isLoading={this.state.isLoading}
                    isLoadStr="正在发表..."
                    style = {[styles.buttonCommit, {backgroundColor: this.state.btnActive ? defaultBackgroundColor.search_Background : defaultBackgroundColor.condition_Background}]}
                    onPress={this._commitButton}
                >
                    <Text style = {{ fontSize: 18,color: defaultFontColor.background_Font_Color }}>
                        { this.state.btnContent}
                    </Text>
                </Button>
                <View style={{width:screenWidth,height:10}}/>

            </View>
        )
    }
}
const styles = StyleSheet.create({

    container: {
        flex:1,
    },
    buttonCommit: {
        left:15,
        width: screenWidth - 30,
        height: 46,
        justifyContent:'center',
        alignItems:'center',
        borderRadius: 6,
    },
    
});


function select(store){
    return {
         store_id:store.userInfo.store_id,
    }
}

export default connect(select)(MineOpinionNew);

















