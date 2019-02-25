import React, { Component } from 'react';
import {
    StyleSheet,
    Text,
    View,
    Image,
    Dimensions,
    TouchableOpacity,
    ScrollView,
    TextInput
} from 'react-native';
import { connect } from 'react-redux';
import Toast from 'react-native-simple-toast';
import { orderState } from "../../redux/action/order_action";
import { storeOrderReport } from '../../network/shopNetApi';
const width = Dimensions.get('window').width;
class operateData extends Component {
    constructor(props) {
        super(props);
        this.state = {
            text: '',
            flag: 0,
            SpecificDate: new Date().getFullYear() + '-' + (new Date().getMonth() + 1) + '-' + new Date().getDate(),
            Zero: ' 0:00:00',
            TwoFour: ' 23:59:59',
            getData: null
        }
        this._storeOrderReport = this._storeOrderReport.bind(this)
    }


    _storeOrderReport(id, start, end) {
        let fromData = {
            store_id: id,
            start_time: start,
            end_time: end,
            sign: ''
        }

        storeOrderReport(fromData, (responentData) => {
            if (responentData['code'] == 1) {
                let allOrderMoney = responentData['data'].valid_order_amount + responentData['data'].offline_order_amount;
                let allOrderNum = responentData['data'].valid_order + responentData['data'].offline_order;
                let perOrderPrice = allOrderNum == 0 ? 0 : parseFloat(allOrderMoney / allOrderNum).toFixed(2);
                this.setState({
                    getData: responentData['data'],
                    allOrderNum: allOrderNum,
                    allOrderMoney: allOrderMoney,
                    perOrderPrice: perOrderPrice,
                })
            }

        })
    }

    componentDidMount() {
        this._storeOrderReport(this.props.user.store_id, this.state.SpecificDate + this.state.Zero, this.state.SpecificDate + " " + this.state.TwoFour)
    }
    //-- 收到推送更新统计信息
    componentWillReceiveProps(props) {
        if (props.isRefresh != this.props.isRefresh) {
            this._storeOrderReport(this.props.user.store_id, this.state.SpecificDate + this.state.Zero, this.state.SpecificDate + " " + this.state.TwoFour)
        }
    }
    shouldComponentUpdate(nextProps, nextState) {
        if (this.props.user.store_id != nextProps.user.store_id) {
            this._storeOrderReport(nextProps.user.store_id, this.state.SpecificDate + this.state.Zero, this.state.SpecificDate + " " + this.state.TwoFour)
            return true;
        }
        else {
            return true;
        }
    }
    public(cont1, cont2) {
        return (
            <View style={{ alignItems: 'center', width: (width - (width / 15)) / 3.1, borderRightWidth: 1, borderRightColor: '#F6F6F6' }}>
                <Text style={{ fontSize: 14, color: '#4A4A4A', marginTop: 10, marginBottom: 10 }}>{cont1}</Text>
                {
                    cont1 == '商品订单数' || cont1 == '支付订单数' || cont1 == '消费笔数' ?
                        <Text style={{ fontSize: 18, color: '#F63300' }}>{cont2}</Text> :
                        <Text style={{ fontSize: 18, color: '#F63300' }}>¥{cont2}</Text>
                }

            </View>
        )
    }
    upData(index) {
        const date = new Date();
        const newDate = new Date(date.getTime() - 1 * 24 * 60 * 60 * 1000);
        const sevenDate = new Date(date.getTime() - 6 * 24 * 60 * 60 * 1000);
        const time = newDate.getFullYear() + "-" + (newDate.getMonth() + 1) + "-" + newDate.getDate();
        const time1 = sevenDate.getFullYear() + "-" + (sevenDate.getMonth() + 1) + "-" + sevenDate.getDate();
        if (index == 0) {
            this._storeOrderReport(this.props.user.store_id, this.state.SpecificDate + this.state.Zero, this.state.SpecificDate + " " + this.state.TwoFour)
        } else if (index == 1) {
            this._storeOrderReport(this.props.user.store_id, time + this.state.Zero, time + " " + this.state.TwoFour)
        } else {
            this._storeOrderReport(this.props.user.store_id, time1 + this.state.Zero, this.state.SpecificDate + " " + this.state.TwoFour)
        }
    }
    render() {
        if (this.state.getData) {
            return (
                <View>
                    <View style={{ width: width - (width / 15), marginLeft: width / 30, marginTop: 11 }}>
                        <View style={{ flexDirection: 'row' }}>
                            <Image source={require('./src/time.png')} style={{ width: 15, height: 15 }} />
                            <Text style={{ fontSize: 14, color: '#F73C0A', marginLeft: 5 }}>经营数据</Text>
                        </View>

                        <View style={{ backgroundColor: '#fff', marginTop: 10, paddingBottom: 10 }}>
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between', paddingTop: 10 }}>
                                <Text style={{ fontSize: 14, color: 'black', paddingLeft: 10, width: screenWidth / 3 }} numberOfLines={2}>{this.props.store_name}</Text>
                                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                    {['今天', '昨天', '近7天'].map((cont, index) => {
                                        return (
                                            <TouchableOpacity
                                                onPress={() => {
                                                    this.setState({
                                                        flag: index
                                                    })
                                                    this.upData(index)
                                                }}
                                                key={index} style={{ paddingLeft: 10, borderBottomColor: this.state.flag == index ? '#F63300' : 'transparent', justifyContent: 'center', borderBottomWidth: 2, paddingBottom: 10 }}>
                                                {
                                                    this.state.flag == index ?
                                                        <Text style={{ fontSize: 15, color: '#f63300', marginRight: 10 }}>{cont}</Text> :
                                                        <Text style={{ fontSize: 15, color: 'black', marginRight: 10 }}>{cont}</Text>
                                                }

                                            </TouchableOpacity>
                                        )
                                    })}
                                </View>
                            </View>
                        </View>
                        <View style={{ width: width - (width / 15), alignItems: 'center', paddingBottom: 10, backgroundColor: "#fff" }}>
                            <View style={{ width: width - (width / 15) - 20, height: 1, backgroundColor: '#F2F2F2' }}></View>
                        </View>

                        <View style={{ flexDirection: 'row', flexWrap: 'wrap', backgroundColor: '#fff' }}>
                            {this.public('总营业额', this.state.allOrderMoney)}
                            {this.public('商品订单', this.state.getData.valid_order_amount)}
                            {this.public('支付订单', this.state.getData.offline_order_amount)}
                            {this.public('消费笔数', this.state.allOrderNum)}
                            {this.public('商品订单数', this.state.getData.valid_order)}
                            {this.public('支付订单数', this.state.getData.offline_order)}
                            {this.public('笔单价', this.state.perOrderPrice)}
                            {this.public('补贴金额', this.state.getData.discount_price)}
                            {this.public('退款金额', this.state.getData.refund_price)}




                        </View>
                        <View style={{ width: width - (width / 15), alignItems: 'center', paddingBottom: 10, paddingTop: 20, backgroundColor: "#fff" }}>
                            <View style={{ width: width - (width / 15) - 20, height: 1, backgroundColor: '#F2F2F2' }}></View>
                        </View>
                        <View style={{ width: width - (width / 15), alignItems: 'center', paddingBottom: 10, paddingTop: 20, backgroundColor: "#fff" }}>
                            <TouchableOpacity
                                onPress={() => {
                                    this.props.dispatch(orderState('user_order', 0))
                                    const { navigate } = this.props.ONavigate.navigation;
                                    navigate('Order', { 'num': 1 })
                                }}
                            >
                                <Text style={{ fontSize: 15, color: '#F63300' }}>查看订单明细 > </Text>
                            </TouchableOpacity>

                        </View>
                    </View>
                </View>
            );
        } else {
            return (
                <View style={{}}>
                </View>
            )
        }

    }
}


const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: "#F6F6F6",
    },
});

function select(store) {
    return {
        user: store.userInfo,
        store_name: store.storeInfo.store_name
    }
}
export default connect(select)(operateData);