import React, { Component } from 'react';
import { Text, View, Image, TouchableOpacity, FlatList, StyleSheet } from 'react-native'
import { connect } from 'react-redux';
import Header from '../../components/Header/Header';
import { storeVoucherList, } from '../../network/shopNetApi';
import Toast from 'react-native-simple-toast';
import { configServerImagePath, } from '../../utils/commonMethod';
import { addActivityVoucher } from '../../network/shopNetApi'
import {
    defaultBackgroundColor,
    defaultSeparateLineColor,
    defaultFontColor,
} from "../../utils/appTheme";
class ActivityDetail extends Component {
    constructor(props) {
        super(props);
        this.state = {
            voucherList: [],
            checkIndex: ''
        }
        this._getDataList = this._getDataList.bind(this);
        this._submit = this._submit.bind(this);
    }
    static navigationOptions = ({ navigation }) => ({
        header: <Header
            leftPress={() => {
                navigation.goBack()
            }}
            centerTitle='参加活动'
        />
    });
    componentDidMount() {
        this._getDataList();
    }
    _getDataList() {
        let formData = {
            admin_id: this.props.admin_id,
            store_id: this.props.store_id,
            is_invalid: 1
        };
        storeVoucherList(formData, (responseData) => {
            let { code, msg, data } = responseData
            if (code === 1) {
                this.setState({
                    voucherList: data
                })
            } else {
                Toast.showWithGravity(msg, 1, Toast.CENTER);
            }
        })
    }
    _createEmptyView = () => {
        return (
            <View style={{ flex: 1, flexDirection: 'column', height: screenWidth, alignItems: 'center', justifyContent: 'center' }}>
                <Text style={{ fontSize: 14, }}>{this.state.msg != '' ? this.state.msg : '正在加载中....'}</Text>
            </View>
        );
    }

    _renderItemList = (item, index) => {
        return (
            <TouchableOpacity
                onPress={() => {
                    this.setState({
                        checkIndex: index
                    })
                }}
                key={index}
            >
                <View style={{ width: screenWidth, flexDirection: 'column', alignItems: 'center', height: 72, marginTop: 10 }}>
                    <View style={[styles.itemList, { backgroundColor: "#fff", borderColor: this.state.checkIndex === index ? '#f55d00' : '#fff', borderWidth: 1 }]}>
                        <View style={{ flexDirection: 'column', alignItems: 'center', justifyContent: 'center', marginHorizontal: 5 }}>
                            <Image
                                resizeMode='cover'
                                style={{ width: 55, height: 55, }}
                                source={{ uri: configServerImagePath(item.voucher_img) }}
                            />
                        </View>
                        <View style={{ flexDirection: 'column', flex: 1, height: 48, alignItems: 'flex-start', justifyContent: 'center', paddingHorizontal: 10 }}>
                            <Text numberOfLines={1} style={{ fontFamily: 'PingFangSC-Regular', fontSize: 14, color: defaultFontColor.main_Font_Color, paddingVertical: 3 }}>{item.voucher_name}</Text>
                            {
                                item.voucher_type == 1 ?
                                    <Text numberOfLines={2} style={{ fontSize: 14, color: defaultFontColor.element_Font_Color, paddingVertical: 5 }}>¥{item.voucher_price}</Text>
                                    :
                                    item.use_method == 0 ?
                                        <Text numberOfLines={2} style={{ fontSize: 14, color: defaultFontColor.element_Font_Color, paddingVertical: 5 }}>{item.use_method_info}元优惠券</Text> :
                                        <Text numberOfLines={2} style={{ fontSize: 14, color: defaultFontColor.element_Font_Color, paddingVertical: 5 }}>{item.use_method_inf / 10}折优惠券</Text>
                            }
                        </View>
                    </View>
                </View>
            </TouchableOpacity >
        )
    }
    _submit = () => {
        if (this.state.checkIndex === '') {
            Toast.showWithGravity('请选择您要参加活动的商品', 1, Toast.CENTER);
            return false;
        }
        let formData = {
            activity_list_id: this.props.navigation.state.params.activity_list_id,
            store_id: this.props.store_id,
            voucher_id: this.state.voucherList[this.state.checkIndex].voucher_id
        }
        addActivityVoucher(formData, (responseData) => {
            let { code, msg, data } = responseData
            Toast.showWithGravity(msg, 1, Toast.CENTER);
            if (code === '1') {
                this.props.navigation.goBack();
            }
        })
    }
    render() {
        return (
            <View style={{ flex: 1, width: screenWidth, alignItems: 'center' }}>
                <FlatList
                    data={this.state.voucherList}
                    renderItem={({ item, index }) => this._renderItemList(item, index)}
                    ListEmptyComponent={this._createEmptyView()}
                    numColumns={1} // 设置列数
                    keyExtractor={(item, index) => index.toString()}
                    refreshing={this.state.loading}
                    onEndReachedThreshold={-0.05}
                    onEndReached={(info) => {
                    }}
                    extraData={this.state}
                />
                <TouchableOpacity
                    activeOpacity={0.5}
                    underlayColor={'transparent'}
                    style={{ width: screenWidth, height: 40, position: 'absolute', bottom: 0, backgroundColor: '#f63300', justifyContent: 'center', alignItems: "center" }}
                    onPress={() => { this._submit() }}
                >
                    <Text style={{ fontSize: 18, color: '#fff' }}>
                        确认参加
                    </Text>
                </TouchableOpacity>
            </View >
        )
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: defaultBackgroundColor.page_Background_Color,
        justifyContent: 'center',
        alignItems: 'center',
    },
    itemList: {
        height: 70,
        flexDirection: 'row',
        alignItems: 'center',
        width: screenWidth - 30,
        justifyContent: 'space-between',
        paddingVertical: 5,
        borderBottomWidth: 1,
    }
});
function select(store) {
    return {
        admin_id: store.userInfo.admin_id,
        store_id: store.userInfo.store_id,
    }
}
export default connect(select)(ActivityDetail)