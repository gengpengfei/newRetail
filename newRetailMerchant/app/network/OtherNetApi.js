
import {
    NetWork_Post,
    NetWork_Get
} from './baseNet';

//获取下级地址列表
function getAddressLast(bodydata, callback) {

    let netapi = 'getAddressLast',
        reqError = {
            error: {
                code: '-1',
                msg: '获取下级地址失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
//根据经纬度获取地址
function getCurrentAddress(bodydata, callback) {

    let netapi = 'getCurrentAddress',
        reqError = {
            error: {
                code: '-1',
                msg: '获取下级地址失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

//根据经纬度获取地址id,定位信息处理接口
function getCurrentHandLocation(bodydata, callback) {

    let netapi = 'getCurrentHandLocation',
        reqError = {
            error: {
                code: '-1',
                msg: '定位失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
//根据经纬度获取地址id,定位信息处理接口
function getLocationRegionHot(bodydata, callback) {

    let netapi = 'getLocationRegionHot',
        reqError = {
            error: {
                code: '-1',
                msg: '定位失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

//区域、城市模糊查询
function areaFuzzyQuery(bodydata, callback) {

    let netapi = 'areaFuzzyQuery',
        reqError = {
            error: {
                code: '-1',
                msg: '获取城市、区域失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


//搜索自动补全
function searchComplement(bodydata, callback) {

    let netapi = 'searchComplement',
        reqError = {
            error: {
                code: '-1',
                msg: '搜索自动补全请求失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


//搜索自动补全
function searchCategoryConditions(bodydata, callback) {

    let netapi = 'searchCategoryConditions',
        reqError = {
            error: {
                code: '-1',
                msg: '分类搜索条件'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}




function getPriceInterval(bodydata, callback) {

    let netapi = 'getPriceInterval',
        reqError = {
            error: {
                code: '-1',
                msg: '获取搜索价格区间失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


//- 文章列表
function getHelpList(bodydata, callback) {

    let netapi = 'getHelpList'
    reqError = {
        error: {
            code: '-1',
            msg: '文章类型获取失败'
        }
    }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function getHelpInfo(bodydata, callback) {

    let netapi = 'getHelpInfo'
    reqError = {
        error: {
            code: '-1',
            msg: '文章详情获取失败'
        }
    }
    NetWork_Post(netapi, bodydata, callback, reqError);
}



export {
    getAddressLast,
    searchComplement,
    getCurrentAddress,
    getCurrentHandLocation,
    getLocationRegionHot,
    searchCategoryConditions,
    getPriceInterval,
    areaFuzzyQuery,
    getHelpList,
    getHelpInfo
}