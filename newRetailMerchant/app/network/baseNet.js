import {isObject} from '../utils/structureJudgment';

const reqUrl = {
  accountLogin: global_BASEURL + '/shopapi/login/login', //账号登录
  quickLogin: global_BASEURL + '/shopapi/Login/quickLogin', //快捷登录
  sendMobileCode: global_BASEURL + '/shopApi/Login/sendMobileCode', //发送手机验证码
  register: global_BASEURL + '/shopapi/login/register', //注册

  storeVoucherList: global_BASEURL + '/shopapi/store/storeVoucherList', //店铺抵用券列表
  storeVoucherInfo: global_BASEURL + '/shopapi/store/storeVoucherInfo', //店铺抵用券详情
  messageList: global_BASEURL + '/shopapi/Message/messageList', //店铺消息推送列表接口
  messageInfo: global_BASEURL + '/shopapi/Message/messageInfo', //店铺消息推送详情
  editMessage: global_BASEURL + '/shopapi/Message/editMessage', //店铺消息状态改动接口
  addActivityVoucher: global_BASEURL + '/shopapi/store/activityApply', //店铺活动报名

  storeClearBillList: global_BASEURL + '/shopapi/store/storeClearBillList', //店铺账单列表
  storeClearBillInfo: global_BASEURL + '/shopapi/store/storeClearBillInfo', //店铺账单详情
  storeDefaultConfigList: global_BASEURL +
    '/shopapi/Store/storeDefaultConfigList', //店铺配置列表
  storeSetConfig: global_BASEURL + '/shopapi/Store/storeSetConfig', //店铺配置提交

  storeList: global_BASEURL + '/shopapi/store/storeList', //店铺列表
  claimStore: global_BASEURL + '/shopapi/store/claimStore', //认领店铺
  createStore: global_BASEURL + '/shopapi/store/createStore', //创建新门店
  storeCategoryAll: global_BASEURL + '/shopapi/store/storeCategoryAll', //品类列表

  getAddressLast: global_BASEURL + '/shopapi/location/getAddressLast', //下级地址列表
  setStoreAudit: global_BASEURL + '/shopapi/store/setStoreAudit', //店铺提交资质
  uploadImg: global_BASEURL + '/api/upload/uploadImg', //图片上传
  storeAuditInfo: global_BASEURL + '/shopapi/store/storeAuditInfo', //店铺资质详情
  storeClose: global_BASEURL + '/shopapi/store/storeClose', //店铺关闭
  storeCloseCancel: global_BASEURL + '/shopapi/store/storeCloseCancel', //店铺去洗哦啊关闭
  information: global_BASEURL + '/shopapi/admin/information', //个人信息来判断首页的进度走到哪一步了
  storeCloseInfo: global_BASEURL + '/shopapi/store/storeCloseInfo', //店铺关店申请进度
  storeDetail: global_BASEURL + '/shopapi/store/storeDetail', //店铺详情
  editStore: global_BASEURL + '/shopapi/store/editStore', //店铺详情
  getStoreSignCode: global_BASEURL + '/shopapi/store/getStoreSignCode', //店铺二维码详情
  updateStoreAudit: global_BASEURL + '/shopapi/store/updateStoreAudit', //编辑店铺资质
  storeOpinionSub: global_BASEURL + '/shopapi/store/storeOpinionSub', //店铺一件反馈
  storeQrcodeImg: global_BASEURL + '/shopapi/store/storeQrcodeImg', //店铺二维码生成
  memberList: global_BASEURL + '/shopapi/admin/memberList', //业务员列表
  delMember: global_BASEURL + '/shopapi/admin/delMember', //移除业务员

  updatePassword: global_BASEURL + '/shopapi/login/updatePassword', //修改密码
  token_login: global_BASEURL + '/shopApi/login/token_login', //token 登录
  checkUnbindCode: global_BASEURL + '/shopapi/admin/checkUnbindCode', //解绑手机验证
  bindNewMobile: global_BASEURL + '/shopapi/admin/bindNewMobile', //绑定手机验证

  //其他   定位相关
  getAddressLast: global_BASEURL + '/api/location/getAddressLast', //获取下级地址列表
  getCurrentAddress: global_BASEURL + '/api/location/getLocation', //根据经纬度获取地址
  getCurrentHandLocation: global_BASEURL + '/api/location/handLocation', //根据经纬度获取地址id
  getLocationRegionHot: global_BASEURL + '/api/location/regionHot', //根据经纬度获取地址id
  areaFuzzyQuery: global_BASEURL + '/api/search/areaFuzzyQuery', //城市区域模糊查询

  storeOrderList: global_BASEURL + '/shopapi/store/storeOrderList', //商铺订单列表
  addMember: global_BASEURL + '/shopapi/admin/addMember', //添加业务员
  checkUsedVoucher: global_BASEURL + '/shopapi/store/checkUsedVoucher', //核销券码
  storeOrderReport: global_BASEURL + '/shopapi/store/storeOrderReport', //店铺订单金额
  storeOrderInfo: global_BASEURL + '/shopapi/store/storeOrderInfo', //店铺订单详情

  getHelpList: global_BASEURL + '/shopapi/article/articleTypeList', //帮组文档列表
  getHelpInfo: global_BASEURL + '/shopapi/article/articleContent', //帮助文档详情
};

let CryptoJS = require ('crypto-js');

/*** 对请求数据进行加密*/
function serviceRequestEncryption (bodyData) {
  let preKey = '87749CECEA24B1C314CC27CF7952EBC3'; //Md5加密（32位大写）
  let tempBodyData = [];
  if (isObject (bodyData)) {
    tempBodyData = bodyData;
  }
  tempBodyData['isAjax'] = '1';
  // tempBodyData["user_id"] = USER_UUID;

  let objKeys = Object.keys (tempBodyData);
  objKeys.sort (); //排序

  let signStr = '';
  objKeys.forEach (item => {
    signStr = signStr + tempBodyData[item];
  });
  signStr = signStr + preKey;

  let md51 = CryptoJS.MD5 (signStr).toString ();
  let md51Super = md51.toUpperCase ();

  md51Super = md51Super.substring (2, 18);
  let md52 = CryptoJS.MD5 (md51Super).toString ();

  tempBodyData['sign'] = md52.toUpperCase ();

  return tempBodyData;
}

/**
 * NetWork_Post post请求方法
 */
const NetWork_Post = function (net_api, bodyData, callback, netOptions) {
  // 加密
  bodyData = serviceRequestEncryption (bodyData);

  let opt_headers, opt_error;
  if (typeof netOptions === 'object') {
    opt_headers = netOptions['headers'];
    opt_error = netOptions['error'];
  }
  let post_header = opt_headers
    ? opt_headers
    : {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      };
  let post_error = opt_error
    ? opt_error
    : {
        code: '-1',
        msg: '请求失败',
      };

  let url = reqUrl[net_api];
  let fetchOptions = {
    method: 'POST',
    headers: post_header,
    body: JSON.stringify (bodyData),
  };
  fetch (url, fetchOptions)
    .then (response => response.text ())
    .then (responseText => {
      let responseData = JSON.parse (responseText);
      callback (responseData);
    })
    .catch (error => {
      callback (post_error);
    })
    .done ();
};
/**
 * NetWork_Get
 */
const NetWork_Get = function (net_api, callback, netOptions) {
  let url = reqUrl[net_api];
  let opt_error;
  if (typeof netOptions === 'object') {
    opt_error = netOptions['error'];
  }
  let get_error = opt_error
    ? opt_error
    : {
        status: '-1',
        msg: '请求失败',
      };
  //类似封装后的ajax方法
  fetch (url, {
    method: 'GET',
    credentials: 'include',
  })
    .then (response => response.text ())
    .then (responseText => {
      let responseData = JSON.parse (responseText);
      callback (responseData);
    })
    .catch (error => {
      callback (get_error);
    });
};
const NetApi = {
  NetWork_Post,
  NetWork_Get,
};
module.exports = NetApi;
