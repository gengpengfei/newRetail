import React from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    TouchableHighlight,
    TouchableOpacity,
    Image,
    TextInput,
    FlatList
} from "react-native";

import { connect } from 'react-redux';
import { defaultSeparateLineColor, defaultFontColor, defaultBackgroundColor } from '../../utils/appTheme';

import { LargeList } from "react-native-largelist";
import { HotDefaultCity, CurrentPosition } from './components/HotCityCell';
import { getPositionInfo, getLastPositionInfo, savaLocationInfo } from '../../utils/LocationTool';
import { getLocationRegionHot, areaFuzzyQuery } from '../../network/OtherNetApi';
import { isNotEmptyString } from '../../utils/structureJudgment';


class HotCityPicker extends React.Component {

    constructor(props) {
        super(props);
        this._numberOfSections = this._numberOfSections.bind(this);
        this._numberOfRowsInSection = this._numberOfRowsInSection.bind(this);
        this._renderItem = this._renderItem.bind(this);
        this._renderSection = this._renderSection.bind(this);
        this._heightForCell = this._heightForCell.bind(this);
        this._getLocation = this._getLocation.bind(this);
        this._getHotCityList = this._getHotCityList.bind(this);
        this._selectCity = this._selectCity.bind(this);
        this._renderHotCityList = this._renderHotCityList.bind(this);
        this._rederCityIndicator = this._rederCityIndicator.bind(this);
        this._renderCityItem = this._renderCityItem.bind(this);

        this.cityData = [];
        this.hotCityList = [];
        this.lastPositon = { display_name: "定位中..." };
        this.cityTitleArray = ['定位', '热门', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'W', 'X', 'Y', 'Z'];
        this.state = {
            searchStr: null,
            refreshing: false,
            isSpread: false,
            searchCityList: [],
        }
    }

    static navigationOptions = ({ navigation }) => ({
        header: null,
    });


    componentDidMount() {
        this.getCityInfos();
        this._getLocation();
        this._getHotCityList();
    }


    _selectCity(item, sectionID) {

        let params = {};
        if (sectionID === "cityJson" || sectionID === "HotDefaultCity") {

            let params = {
                city_id: item.region_id,
                district_id: '',
                lat: item.lat,
                lng: item.lng,
                is_type: '1',
                display_name: item.short_name,
            }

            savaLocationInfo(params);
            this.props.navigation.state.params.callback(params);
            this.props.navigation.goBack();
        } else {
            if (item.display_name === '定位中...' || item.display_name === "定位失败") {
                this._getLocation();// 重新定位
            } else {

                let params = {
                    city_id: item.city_id,
                    district_id: item.district_id,
                    lat: item.lat,
                    lng: item.lng,
                    is_type: '0',
                    display_name: item.display_name,
                }

                savaLocationInfo(params);
                this.props.navigation.state.params.callback(params);
                this.props.navigation.goBack();
            }
        }
    }



    _getLocation() {

        getLastPositionInfo((response) => {
            const { code, data = [] } = response;

            code === 2 ? null : data["display_name"] = "定位失败";
            this.lastPositon = data;
            let index = { section: 1, row: 0 };
            this.largelist.reloadIndexPath(index)
        }, false);
    }

    _getHotCityList() {

        let formData = {};
        getLocationRegionHot(formData, (response) => {
            const { code, data = [] } = response;
            if (code === 1) {
                this.hotCityList = data;
                let index = { section: 2, row: 0 };
                this.largelist.reloadIndexPath(index)
            }
        })
    }

    async getCityInfos() {

        this.cityData = await require('../city0000.json');
        this.largelist.reloadData();
    }

    _numberOfSections() {
        return this.cityData.length + 3;
    }

    _numberOfRowsInSection(section) {
        if (section === 0) {
            return this.state.isSpread ? 1 : 0;
        } else if (section < 3) {
            return 1
        } else {
            section = section - 3;
            let sectionData = this.cityData[section];
            const { data } = sectionData;
            return data.length;
        }
    }

    _heightForCell(section, row) {
        if (section === 0) {
            return 400;
        } else if (section === 1) {
            return 50;
        }
        else if (section === 2) {
            return 130;
        }
        else {
            return 45;
        }
    }

    _heightForSection(section) {
        return 30;
    }

    _renderItem(section, row) {

        if (section === 0) {
            return (
                <View style={{ flex: 1, width: screenWidth, backgroundColor: '#eee', paddingLeft: 16, justifyContent: 'center' }}>
                    <Text>{imteData.short_name}</Text>
                </View>
            )
        } else if (section === 1) {

            return (
                <CurrentPosition
                    hotCityList={this.lastPositon}
                    onSelect={(item) => {
                        this._selectCity(item, "CurrentPosition");
                    }}
                />
            )
        }
        else if (section === 2) {
            return <HotDefaultCity
                hotCityList={this.hotCityList}
                onSelect={(item) => {
                    this._selectCity(item, "HotDefaultCity");
                }} />;
        }
        else {
            section = section - 3;
            let sectionData = this.cityData[section];
            const { data } = sectionData;
            let imteData = data[row];
            return (
                <TouchableHighlight
                    onPress={() => {
                        this._selectCity(imteData, "cityJson");
                    }}
                    style={{ flex: 1 }}>
                    <View style={{ flex: 1, width: screenWidth, backgroundColor: 'white', paddingLeft: 16, justifyContent: 'center' }}>
                        <Text style={{ color: defaultFontColor.main_Font_Color }}>{imteData.short_name}</Text>
                    </View>
                </TouchableHighlight>
            )
        }
    }


    _renderSection(section) {
        let title = '', bgcolor = defaultBackgroundColor.page_Background_Color, textColor = defaultFontColor.main_Font_Color, aFontWeight = 'normal';
        if (section === 0) {
            title = "当前位置";
            bgcolor = "#fff";
            textColor = "black";

        }
        else if (section === 1) {
            title = "定位";
            aFontWeight = 'bold'
        }
        else if (section === 2) {
            title = "热门城市";
            aFontWeight = 'bold'
        }
        else {

            title = this.cityTitleArray[section - 1];
        }

        return (
            <View style={{ flex: 1, width: screenWidth, backgroundColor: bgcolor, paddingLeft: 16, justifyContent: 'center' }}>
                <Text style={{ color: textColor, fontWeight: aFontWeight }}>{title}</Text>
            </View>
        )
    }

    renderIndexes(section, row) {
        return (
            <TouchableHighlight
                onPress={() => {
                    let index = { section: row + 1, row: 0 };
                    this.largelist.scrollToIndexPath(index, true);
                }}
                style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <View>
                    <Text style={{ color: "#00bea9", fontSize: 12, fontWeight: '600' }}>{this.cityTitleArray[row]}</Text>
                </View>
            </TouchableHighlight>
        )
    }

    _renderHotCityList() {
        return (

            <LargeList
                ref={(refsss) => this.largelist = refsss}
                style={{ flex: 1 }}
                bounces={true}
                refreshing={this.state.refreshing}
                onRefresh={() => {
                    this.setState({ refreshing: true });
                    setTimeout(() => this.setState({ refreshing: false }), 2000);
                }}
                safeMargin={600}
                numberOfSections={this._numberOfSections}
                numberOfRowsInSection={this._numberOfRowsInSection}
                heightForCell={this._heightForCell}
                heightForSection={this._heightForSection}
                renderCell={this._renderItem}
                renderSection={this._renderSection}
            />
        )
    }

    _rederCityIndicator() {
        return (
            <View style={styles.indicator}>
                <LargeList
                    renderItemSeparator={() => null}
                    style={{ width: 30, height: 25 * 20 }}
                    ref={ref => (this.indexes = ref)}
                    numberOfRowsInSection={() => this.cityTitleArray.length}
                    heightForCell={() => 20}
                    renderCell={this.renderIndexes.bind(this)}
                    showsVerticalScrollIndicator={false}
                    bounces={false}
                />
            </View>
        )
    }

    _renderCityItem(item) {
        return (
            <TouchableOpacity
                onPress={() => {
                    this._selectCity(item.item, "HotDefaultCity");
                }}
                style={{ width: screenWidth, height: 45, justifyContent: 'center', borderBottomColor: '#efefef', borderBottomWidth: 1, marginLeft: 15 }}>
                <Text>{item.item.name}</Text>
            </TouchableOpacity>
        )
    }

    renderServerCityList() {
        return (
            <FlatList
                keyExtractor={(item, index) => '' + index}
                data={this.state.searchCityList}
                renderItem={this._renderCityItem}

            />
        )
    }

    render() {



        return (

            <View style={styles.container}>
                <View style={{ width: 100, height: statusbarHeight }} />
                <View style={{ flex: 1 }}>

                    {/**  搜索栏  */}
                    {this._renderSearhBar()}

                    {isNotEmptyString(this.state.searchStr) ? null : this._renderHotCityList()}
                    {isNotEmptyString(this.state.searchStr) ? null : this._rederCityIndicator()}
                    {isNotEmptyString(this.state.searchStr) ? this.renderServerCityList() : null}

                </View>
            </View>

        );
    }

    _reqCityForServer(text) {
        let formData = {
            search_str: text,
        }
        areaFuzzyQuery(formData, (response) => {

            const { code, msg, data = [] } = response;
            if (code == 1) {
                this.setState({
                    searchCityList: data,
                })
            }


        });

    }




    _renderSearhBar = () => {
        // transparent
        return (
            <View style={styles.searchBar}>
                <TouchableHighlight
                    underlayColor={'transparent'}
                    onPress={() => {
                        this.props.navigation.goBack();
                    }}
                    style={{ width: 44, height: 40, justifyContent: 'center' }}>

                    <Image style={{ left: 10, width: 20, height: 20 }} source={require('./resource/X.png')} />

                </TouchableHighlight>



                <View style={{ flex: 1, flexDirection: 'row', alignItems: 'center', backgroundColor: '#e0e1e6', height: 30, borderRadius: 15, }}>
                    <View style={{ width: 7 }} />
                    <Image
                        style={{ width: 15, height: 15 }}
                        source={require('./resource/searc1.png')} />

                    <View style={{ width: 7 }} />
                    <View style={{ flex: 1 }}>

                        <TextInput
                            maxLength={30}
                            placeholder={"城市名/拼音"}
                            underlineColorAndroid='transparent'
                            style={{ flex: 1, fontSize: 14, color: '#4a4a4a', padding: 0, margin: 0, borderWidth: 0 }}
                            onChangeText={(text) => {

                                this.setState({
                                    searchStr: text,
                                })

                                this._reqCityForServer(text);

                            }}
                            value={this.state.searchStr}
                        />


                    </View>
                </View>


                <View style={{ width: 32, height: 40 }} />
            </View>
        )
    }
}


function select(store) {
    return {

    }
}


var styles = StyleSheet.create({

    container: {
        flex: 1,
        backgroundColor: defaultBackgroundColor.page_Background_Color,
    },
    indicator: {
        position: 'absolute',
        backgroundColor: 'transparent',
        width: 30,
        height: screenHeight - 40,
        top: 40,
        justifyContent: 'center',
        alignItems: 'center',
        left: screenWidth - 30,
    },
    searchBar: {
        width: screenWidth,
        height: 40,
        alignItems: 'center',
        flexDirection: 'row',
        borderBottomWidth: 1,
        borderBottomColor: '#e0e1e0',
    },

});

export default connect(select)(HotCityPicker);
