
import React, { Component } from 'react';
import Header from '../../components/Header/Header'
import { isNotEmptyArray } from '../../utils/structureJudgment'
import {
    StyleSheet,
    Text,
    View,
    Image,
    TouchableOpacity,
    ScrollView
} from 'react-native';
class MineHelpOnline extends Component{
    static navigationOptions = ({navigation}) => ({
        header:<Header 
        leftPress={()=>{
            navigation.goBack();
        }} centerTitle={navigation.state.params.Help_list.article_type_name}/>,
    });
    constructor(props) {
        super(props);
        this.state = {
            articleList: this.props.navigation.state.params.Help_list.article_list,
        };
    }
    public(cont) {
        return (
            <TouchableOpacity style={{
                width: screenWidth, height: 40, paddingLeft: 14, paddingRight: 14, alignItems: 'center', backgroundColor: '#fff',
                borderBottomWidth: 1, borderBottomColor: '#ececec', justifyContent: 'space-between', flexDirection: 'row'
            }}
                key={cont.article_id}
                onPress={() => {
                    this.props.navigation.navigate('MineHelpDetail', {
                        info: cont
                    });
                }}
            >
                <Text style={{ color: '#4a4a4a' }}>{cont.title}</Text>
                <View style={{ flexDirection: 'row' }}>
                    <Image source={require('./src/rightJ.png')} style={{ width: 15, height: 15 }} />
                </View>
            </TouchableOpacity>
        )
    }

    componentDidMount() {

    }
    render() {
        return (
            <ScrollView style={styles.container}>
                {
                    isNotEmptyArray(this.state.articleList) ?
                        this.state.articleList.map((e, i) => {
                            return this.public(e)
                        })
                        :
                        null
                }
            </ScrollView>
        );
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f6f6f6'

    }
});
export default MineHelpOnline;