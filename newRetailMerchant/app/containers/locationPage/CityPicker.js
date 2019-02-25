


import React,{Component} from 'react';
import {
  View,
  StyleSheet,
  Text,
  Dimensions,
  FlatList,
  ScrollView,
  TouchableOpacity,
} from 'react-native';

const { width ,height} = Dimensions.get('window')
import { connect } from 'react-redux';
import { getAddressLast } from '../../network/OtherNetApi';


export default class CityPicker extends Component {
  constructor(props) {
    super(props);
    this._onPressTitle = this._onPressTitle.bind(this);
    this._showTitleStr = this._showTitleStr.bind(this);
    this._getProvinceList = this._getProvinceList.bind(this);
    this._selectProvince = this._selectProvince.bind(this);
    this._renderItem = this._renderItem.bind(this);
    this._complete = this._complete.bind(this);

    this.state={
      regionId:0,
      currentIdx:0,
      item1:null,
      item2:null,
      item3:null,
      
      listData0:[],
      listData1:[],
      listData2:[],
      
    }

  }



  _complete(){
    let formData = {
      province : this.state.item1,
      city : this.state.item2,
      area : this.state.item3,
    }
  

  }

  componentDidMount() {
      this._getProvinceList(this.state.regionId,0);
  };

  _getProvinceList(regionId,idx){

    // 请求区域列表
    let formData = {
        region_id:regionId,
    }

    

    getAddressLast(formData,(responseData)=>{

    
    


      if (responseData['code']==1) {
          
        const {data=[]} = responseData;
        if (idx === 0) {

          this.setState({
            listData0:data,
          })
        }
        else if (idx === 1) {
          this._onPressTitle(idx)
          this.setState({
            listData1:data,
          })

        }
        else if (idx === 2) {
          this._onPressTitle(idx)
          this.setState({
            listData2:data,
          })
        }
       
      }else {
        // if (responseData['msg'] === '省市区列表为空') {
        //   this._complete();
        // }
      }

    })
  }

  _selectProvince(item){

    

    if (item.level == 1) {
      this.setState({
        item1:item,
        regionId:item.region_id,
        currentIdx:1,
      })

      

      this._getProvinceList(item.region_id,1);
    }
    else if (item.level == 2) {
      this.setState({
        item2:item,
        regionId:item.region_id,
        currentIdx:2,
      })

      this._getProvinceList(item.region_id,2);
    }
    else if (item.level == 3) {
        
      this.setState({
        item3:item,
        regionId:item.region_id,
        currentIdx:3,
      },this._complete);

    }

  }


  _onPressTitle(idx){
    this.scrollView.scrollTo({x: idx*width, y: 0, animated: true});
  };

  _showTitleStr(idx){
    let resutStr = null;
    if (idx === 0) {
      resutStr = this.state.currentIdx > 0? this.state.item1.name:'请选择';
    }
    else if (idx === 1) {
      resutStr = this.state.currentIdx > 1? this.state.item2.name:'请选择';
    }
    else if (idx === 2) {
      resutStr = this.state.currentIdx > 2? this.state.item3.name:'请选择';
    }
    return resutStr;

  }

  _renderItem(item){

    return(
      <View>
        <TouchableOpacity
          onPress={()=>{
            this._selectProvince(item.item);
          }}>
          <View style={{backgroundColor:'white',height:40,justifyContent:'center'}}>
            <Text style={styles.itemStyle}>
              {item.item.name}
            </Text>
          </View>
        </TouchableOpacity>

      </View>

    )
  }


  render(){

    return(

      <View style={styles.container}>
        <View style = {styles.titleCell}>
          <Text
            style = {this.state.currentIdx===0?styles.titleSelectStyle:styles.titleStyle}
            onPress={()=>{this._onPressTitle(0)}}>  {this._showTitleStr(0)}  </Text>
          {
            this.state.currentIdx>0?
            <Text
              style = {this.state.currentIdx===1?styles.titleSelectStyle:styles.titleStyle}
              onPress={()=>{this._onPressTitle(1)}}>  {this._showTitleStr(1)}  </Text>:
            null

          }
          {
            this.state.currentIdx>1?
            <Text
              style = {this.state.currentIdx===2?styles.titleSelectStyle:styles.titleStyle}
              onPress={()=>{this._onPressTitle(2)}}>  {this._showTitleStr(2)}  </Text>:
            null

          }
          {
            this.state.currentIdx>2?
            <Text
              style = {this.state.currentIdx===3?styles.titleSelectStyle:styles.titleStyle}
              onPress={()=>{this._onPressTitle(3)}}>  {this._showTitleStr(3)}  </Text>:
            null
          }



        </View>

        <View style = {styles.scrollContainer}>
          <ScrollView
            ref={(scrollView) => { this.scrollView = scrollView; }}
            scrollEnabled={false}
            showsHorizontalScrollIndicator={false}
            horizontal={true}>

            <View style={{width:width}}>
              <FlatList
                data={this.state.listData0}
                keyExtractor={(item, index) => index.toString()}
                renderItem={this._renderItem}
              />
            </View>

            <View style={{width:width}}>
              <FlatList
                data={this.state.listData1}
                keyExtractor={(item, index) => index.toString()}
                renderItem={this._renderItem}
              />
            </View>

            <View style={{width:width}}>
              <FlatList
                data={this.state.listData2}
                keyExtractor={(item, index) => index.toString()}
                renderItem={this._renderItem}
              />
            </View>


          </ScrollView>

        </View>
      </View>
    );
  }
}



const styles = StyleSheet.create({

  container: {
    backgroundColor:'#F1F1F1',
    flex:1,
  },
  scrollContainer:{
    flex:1,
  },
  titleCell:{
    flexDirection:'row',
    height:30,
    alignItems:'flex-end',
    borderBottomWidth:1,
    borderBottomColor:'#A9A9A9',
  },
  titleStyle:{
    color:'#898989',
    fontSize:14,
    height:20,
  },
  titleSelectStyle:{
    color:'red',
    fontSize:14,
    height:20,
    bottom:1,
    borderBottomWidth:1,
    borderBottomColor:'red',
  },
  itemStyle:{
    color:'#464646',
    fontSize:13,
    left:16,
  }

});
