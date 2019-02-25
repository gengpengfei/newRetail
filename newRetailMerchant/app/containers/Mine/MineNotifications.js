import React, { Component } from "react";
import {
    StyleSheet,
    View,
    Text,
    Switch
} from "react-native";
import Header from '../../components/Header/Header'
import { connect } from 'react-redux';
import { defaultSeparateLineColor, defaultFontColor, defaultBackgroundColor } from "../../utils/appTheme";
import { storeDefaultConfigList, storeSetConfig, } from "../../network/shopNetApi";
import Toast from 'react-native-simple-toast';
import { isNotEmptyArray, } from '../../utils/structureJudgment';

class MineNotifications extends Component {
    constructor(props) {
        super(props);
        this.state = {
            configList: [],
        }
        this._renderItem = this._renderItem.bind(this);
        this._storeDefaultConfigList = this._storeDefaultConfigList.bind(this);
        this._storeSetConfig = this._storeSetConfig.bind(this);
    }
    static navigationOptions = ({ navigation }) => ({
        header: <Header leftPress={() => {
            navigation.goBack();
        }} centerTitle="通知设置" />,
    });

    componentDidMount() {
        this._storeDefaultConfigList();
    }

    _storeDefaultConfigList() {
        let formData = {
            store_id: this.props.storeInfo_storeID,
        };
        storeDefaultConfigList(formData, (responseData) => {
            let { msg, code, data } = responseData;
            if (code == 1) {
                this.setState({
                    configList: data,
                })
            } else {
                Toast.showWithGravity(msg, 1, Toast.CENTER);
            }
        })
    }
    _storeSetConfig() {
        let formData = {
            store_id: this.props.store_id,
            config_data: this.state.configList
        };
        storeSetConfig(formData, (responseData) => {
            let { msg, code, data } = responseData;
            if (code != 1) {
                Toast.showWithGravity(msg, 1, Toast.CENTER);
            }
        })
    }
    _renderItem(leftStr, selected, valueChange) {
        return (
            <View style={{ marginTop: 5, paddingHorizontal: 20, height: 40, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#fff', borderBottomColor: "#efefef", borderBottomWidth: 1 }} key={leftStr}>
                <Text style={{ fontSize: 14, color: "#4a4a4a", fontFamily: "PingFangSC-Regular" }}>{leftStr}</Text>
                <View style={{ borderRadius: 15 }}>
                    <Switch
                        style={{ width: 50, height: 30, borderRadius: 15, backgroundColor: isIOS ? defaultSeparateLineColor.dark_SeparateLine_Color : null }}
                        onValueChange={valueChange}
                        value={selected === '1' ? true : false}
                        onTintColor={defaultBackgroundColor.search_Background}
                        thumbTintColor={defaultFontColor.background_Font_Color}
                        tintColor={defaultSeparateLineColor.dark_SeparateLine_Color}
                    />
                </View>
            </View>
        )
    }
    render() {
        return (
            <View style={styles.container}>
                {
                    this.state.configList ? this.state.configList.map((item, index) => {
                        return this._renderItem(String(item.name), String(item.value), () => {
                            this.state.configList[index].value = String(item.value) === '1' ? '0' : '1';
                            this.setState({
                                configList: this.state.configList
                            }, () => {
                                this._storeSetConfig();
                            })
                        })
                    }) : null
                }
            </View>
        )
    }
}
const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
});

function select(store) {
    return {
        storeInfo_storeID: store.storeInfo.store_id,
    }
}
export default connect(select)(MineNotifications);