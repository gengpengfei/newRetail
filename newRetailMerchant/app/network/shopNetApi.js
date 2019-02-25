import {
    NetWork_Post,
    NetWork_Get
} from './baseNet';

function storeVoucherList(bodydata, callback) {

    let netapi = 'storeVoucherList',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺券列表失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function storeVoucherInfo(bodydata, callback) {

    let netapi = 'storeVoucherInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺用券详情失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}


//店铺消息推送列表接口
function messageList(bodydata, callback) {

    let netapi = 'messageList',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺消息推送列表失败1'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};

//店铺消息推送列表接口
function messageInfo(bodydata, callback) {

    let netapi = 'messageInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺消息推送详情失败2'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};

//店铺消息状态改动接口
function editMessage(bodydata, callback) {

    let netapi = 'editMessage',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺消息推送列表失败3'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

//业务员列表
function memberList(bodydata, callback) {

    let netapi = 'memberList',
        reqError = {
            error: {
                code: '-1',
                msg: '获取业务员列表失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};
//移除业务员
function delMember(bodydata, callback) {
    let netapi = 'delMember',
        reqError = {
            error: {
                code: '-1',
                msg: '移除业务员失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function storeList(bodydata, callback) {


    let netapi = 'storeList',
        reqError = {
            error: {
                code: '-1',
                msg: '获取店铺列表失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}

function claimStore(bodydata, callback) {


    let netapi = 'claimStore',
        reqError = {
            error: {
                code: '-1',
                msg: '认领店铺失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}

function createStore(bodydata, callback) {


    let netapi = 'createStore',
        reqError = {
            error: {
                code: '-1',
                msg: '创建店铺失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}
function storeOrderList(bodydata, callback) {

    let netapi = 'storeOrderList',
        reqError = {
            error: {
                code: '-1',
                msg: '获取订单列表失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function createStore(bodydata, callback) {


    let netapi = 'createStore',
        reqError = {
            error: {
                code: '-1',
                msg: '创建店铺失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}


function storeCategoryAll(bodydata, callback) {


    let netapi = 'storeCategoryAll',
        reqError = {
            error: {
                code: '-1',
                msg: '品类列表获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}

function getAddressLast(bodydata, callback) {


    let netapi = 'getAddressLast',
        reqError = {
            error: {
                code: '-1',
                msg: '下级地址获取失败'
            }
        }


    NetWork_Post(netapi, bodydata, callback, reqError);

}


function setStoreAudit(bodydata, callback) {


    let netapi = 'setStoreAudit',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺提交资质失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);

}


function getUploadImg(bodydata, callback) {

    let netapi = 'uploadImg',
        reqError = {
            error: {
                code: '-1',
                msg: '图片上传失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}




function storeAuditInfo(bodydata, callback) {

    let netapi = 'storeAuditInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺资质详情获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


function storeClose(bodydata, callback) {

    let netapi = 'storeClose',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺关闭提交失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


function storeCloseCancel(bodydata, callback) {

    let netapi = 'storeCloseCancel',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺取消关闭提交失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function information(bodydata, callback) {

    let netapi = 'information',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺主详情获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


function storeCloseInfo(bodydata, callback) {

    let netapi = 'storeCloseInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺关店申请进度获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function storeDetail(bodydata, callback) {

    let netapi = 'storeDetail',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺详情获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function editStore(bodydata, callback) {

    let netapi = 'editStore',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺详情编辑获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function checkUsedVoucher(bodydata, callback) {

    let netapi = 'checkUsedVoucher',
        reqError = {
            error: {
                code: '-1',
                msg: '核销券码获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function getStoreSignCode(bodydata, callback) {

    let netapi = 'getStoreSignCode',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺二维码获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

//店铺账单列表
function storeClearBillList(bodydata, callback) {

    let netapi = 'storeClearBillList',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺账单列表获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
//店铺账单列表
function storeClearBillInfo(bodydata, callback) {

    let netapi = 'storeClearBillInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺账单详情获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};

//店铺配置列表
function storeDefaultConfigList(bodydata, callback) {

    let netapi = 'storeDefaultConfigList',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺配置列表获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};

//店铺配置提交
function storeSetConfig(bodydata, callback) {

    let netapi = 'storeSetConfig',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺配置提交失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
};

//店铺账单列表
function updateStoreAudit(bodydata, callback) {

    let netapi = 'updateStoreAudit',
        reqError = {
            error: {
                code: '-1',
                msg: '店铺账单列表获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


function storeOrderReport(bodydata, callback) {

    let netapi = 'storeOrderReport',
        reqError = {
            error: {
                code: '-1',
                msg: '获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
function getStoreOrderInfo(bodydata, callback) {

    let netapi = 'storeOrderInfo',
        reqError = {
            error: {
                code: '-1',
                msg: '获取失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}

function storeOpinionSub(bodydata, callback) {

    let netapi = 'storeOpinionSub',
        reqError = {
            error: {
                code: '-1',
                msg: '意见反馈提交失败'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}


function storeQrcodeImg(bodydata, callback) {

    let netapi = 'storeQrcodeImg',
        reqError = {
            error: {
                code: '-1',
                msg: '二维码生成'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
//-- 参加活动报名
function addActivityVoucher(bodydata, callback) {
    let netapi = 'addActivityVoucher',
        reqError = {
            error: {
                code: '-1',
                msg: '网络延时，请稍后重试'
            }
        }
    NetWork_Post(netapi, bodydata, callback, reqError);
}
export {
    storeVoucherList,
    storeVoucherInfo,
    messageList,
    messageInfo,
    editMessage,
    memberList,
    delMember,
    storeList,
    claimStore,
    createStore,
    storeCategoryAll,
    getAddressLast,
    setStoreAudit,
    getUploadImg,
    storeOrderList,
    storeAuditInfo,
    storeClose,
    storeCloseCancel,
    information,
    storeCloseInfo,
    storeDetail,
    editStore,
    checkUsedVoucher,
    getStoreSignCode,
    storeClearBillList,
    storeClearBillInfo,
    storeDefaultConfigList,
    storeSetConfig,
    storeOrderReport,
    updateStoreAudit,
    storeOpinionSub,
    getStoreOrderInfo,
    storeQrcodeImg,
    addActivityVoucher
}