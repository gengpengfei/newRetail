import React, {Component} from "react";
import {
    StyleSheet,
    View,
    Text,
    TouchableOpacity,
    Image,
} from "react-native";
import Header from '../../components/Header/Header'
import { getHelpList } from '../../network/OtherNetApi'
import {connect} from 'react-redux';
import {SetSection} from './components/Section'
import Communications from 'react-native-communications';
import {Loading} from '../../components/Loading/Loadings';
import { isNotEmptyArray } from '../../utils/structureJudgment'
class MineHelp extends Component {
    constructor(props){
        super(props);
        this.state = {
            articleList: {},
            phone: '',
            loading: true
        };
        this._getHelpList = this._getHelpList.bind(this);
        this._clickSectionItem = this._clickSectionItem.bind(this);
        
    }
    static navigationOptions = ({navigation}) => ({
        header:<Header leftPress={()=>{
            navigation.goBack();
        }} centerTitle="帮助中心"/>,
    });

    public(cont, cont_1 = null) {
        return (
            <TouchableOpacity style={{
                width: screenWidth, height: 40, paddingLeft: 14, paddingRight: 14, alignItems: 'center', backgroundColor: '#fff',
                borderBottomWidth: 1, borderBottomColor: '#ececec', justifyContent: 'space-between', flexDirection: 'row'
            }}
                key={cont.article_type_id}
                onPress={() => {
                    this.props.navigation.navigate('MineHelpOnline', {
                        Help_list: cont
                    });
                }}
            >
                <Text style={{ color: '#4a4a4a' }}>{cont.article_type_name}</Text>
                <View style={{ flexDirection: 'row' }}>
                    <Text style={{ color: '#9B9B9B', fontSize: 12 }}>{cont_1}</Text>
                    <Image source={require('./src/rightJ.png')} style={{ width: 15, height: 15 }} />
                </View>
            </TouchableOpacity>
        )
    }
    _clickSectionItem(str){
        if(str === "客服热线"){
            Communications.phonecall('400-888-2384', false);
        }
    }

    componentDidMount() {
        this._getHelpList();
    }

    _getHelpList() {
        let formData = {}
        getHelpList(formData, (response) => {
            const { data = null, code = -1, msg = '' } = response
            if (code == -1) {
                this.setState({
                    loading: false
                })
            } else {
                this.setState({
                    articleList: data.article_list,
                    phone: data.phone,
                    loading: false
                })
            }
        });
    }
    render(){
        return(
            <View style = {styles.container}>
            <Loading loading={this.state.loading} />
                {
                    isNotEmptyArray(this.state.articleList) ?
                        this.state.articleList.map((e, i) => {
                            return this.public(e)
                        })
                        :
                        null
                }
                <SetSection 
                    clickItem={this._clickSectionItem}
                    rightImgPath = {require("./src/rightJ.png")}
                    titleStr="客服热线"
                    rightStr={this.state.phone}   
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

export default connect(select)(MineHelp);

















