var regObj = {
    // 通用手机号码
    phoneReg: /^(13\d|14[57]|15[012356789]|17[03678]|18\d)\d{8}$/g,
    // 移动号码
    motionReg: /^(13[456789]|147|15[012789]|18[23478]|178|170)\d$/g,
    // 联通号码
    unicomReg: /^(13[012]|145|15[56]|176|18[56]|170)\d$/g,
    // 电信号码
    telecomReg: /^(133|153|17[37]|18[019]|170)\d$/g,
    // 电子邮箱
    emailReg: /^([a-zA-Z0-9_\+\-\.])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/g,
    // 密码:长度在8位及以上，密码应包含数字, 特殊符号中得两种或两种以上
    passwordReg: /^(?![A-Z]+$)(?![a-z]+$)(?!\d+$)(?![\W_]+$)\S{8,30}$/g,
    // 身份证号码
    // idCarNoReg: /^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))((0[1-9])|([1-2]\d)|(3[0-1]))((\d{4})|(\d{3}[Xx]))$/g,
    idCarNoReg:/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/g,
   
    // 电话号码
    telePhoneReg: /^((85[23]|0[1-9]\d{1,2})-)?\d{7,8}$/g,
    // 微信号码
    weixinReg: /^[a-zA-Z][a-zA-Z_0-9-]{5,19}$/g,
    // 中文文本
    chineseReg: /^[\u2E80-\u9FFF]+$/g,
    // url地址
    urlReg: /^((https|http):\/\/).+$/g,
    //用户名
    userName:/^[\u4E00-\u9FA5A-Za-z0-9]{6,15}$/,
    userNickname: /^[\u4E00-\u9FA5A-Za-z][\u4E00-\u9FA5A-Za-z0-9]{3,15}$/,
    numberReq:/^[0-9]*$/,
    
    //银行卡号
    bankCardNumReg:/^([1-9]{1})(\d{14}|\d{18})$/
};
var infoObj = {
    phoneError: '手机号码错误',
    emailError: '邮箱格式错误',
    verifyCodeError: '验证码错误',
    passwordError: '密码由8~15位的数字、字母组合组成',
    idCardNoError: '身份证号码有误',
    telePhoneError: '电话号码有误',
    weixinError: '微信号码有误',
    chineseError: '不是中文',
    urlError: 'url地址有误',
    userNameError:"用户名由6~15位数字、字母组成",
    userNicknameError: "以中文或英文开头，限4-16位字符，英文、数字、下划线的组合",
    numberReqError:'输入金额错误，请重新输入',
    bankCardRegError:'输入的银行卡号有误，请重新输入'
};


// 工具函数，将输入源数字转字符，并去掉首尾的字符
function strTrim(str) {
    if (typeof str === 'number') {
        str = str.toString();
    }
    return str.trim();
};


RegExpTool = {
    phoneByReg:function(phone,config){
        // 配置信息
        let phoneNum = strTrim(phone),//传入的手机号
            resultObj={},//返回结果
            operatorInfor = false,
            phoneError = infoObj.phoneError,
            sectionStr = phoneNum.substring(0, 4);  //根据手机号前四位判断用户类型
        // 判断传入的配置信息
        if (typeof config === 'object') {
            operatorInfor = config['detail'] ? config['detail'] : false;
            phoneError = config['error'] ? config['error'] : infoObj.phoneError;
        }

        // 匹配手机号
        if (regObj.phoneReg.test(phoneNum)) {
            regObj.phoneReg.lastIndex = 0;
            if (operatorInfor) {
                switch(true) {
                    case regObj.telecomReg.test(sectionStr):
                        resultObj['type'] = '电信';
                        regObj.telecomReg.lastIndex = 0;
                        break;
                    case regObj.motionReg.test(sectionStr):
                        resultObj['type'] = '移动';
                        regObj.motionReg.lastIndex = 0;
                        break;
                    case regObj.unicomReg.test(sectionStr):
                        resultObj['type'] = '联通';
                        regObj.unicomReg.lastIndex = 0;
                        break;
                }
            };
            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        }else {
            resultObj['check'] = false;
            resultObj['error'] = phoneError;
        }
        return resultObj;
    },

    emailByReg: function(email,config){
        let emailStr = strTrim(email),
            resultObj = {},
            emailError = infoObj.emailError;
        if (typeof config === 'object') {
            emailError = config['error'] ? config['error'] : infoObj.emailError;
        }
        if (regObj.emailReg.test(emailStr)) {
            regObj.emailReg.lastIndex = 0;

            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        }else {
            resultObj['check'] = false;
            resultObj['error'] = emailError;
        }
        return resultObj;
    },

    passwordByReg: function(password, config) {
        var passwordStr     = strTrim(password),
            // 返回结果
            resultObj       = {},
            // 自定义错误信息
            passwordError   = infoObj.passwordError;
        // 判断传入的配置信息
        if (typeof config == 'object') {
            passwordError = config['error'] ? config['error'] : infoObj.passwordError;
        }
        if (regObj.passwordReg.test(passwordStr)) {
            regObj.passwordReg.lastIndex = 0;
            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        } else {
            resultObj['check'] = false;
            resultObj['error'] = passwordError;
        }
        return resultObj;
    },

    userNameByReg:function (userName,config) {
        var userNameStr     = strTrim(userName),
            resultObj       = {},
            userNameError   =infoObj.userNameError;
        if(typeof config == 'object'){
            userNameError = config['error'] ? config['error'] : infoObj.userNameError;
        }
        if (regObj.userName.test(userNameStr)) {
            regObj.userName.lastIndex = 0;
            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        } else {
            resultObj['check'] = false;
            resultObj['error'] = userNameError;
        }
        return resultObj;
    },

    userNicknameByReg:function (userNickname,config) {
        var userNicknameStr     = strTrim(userNickname),
            resultObj       = {},
            userNicknameError   =infoObj.userNicknameError;
        if(typeof config == 'object'){
            userNicknameError = config['error'] ? config['error'] : infoObj.userNicknameError;
        }
        if (regObj.userNickname.test(userNicknameStr)) {
            regObj.userNickname.lastIndex = 0;
            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        } else {
            resultObj['check'] = false;
            resultObj['error'] = userNicknameError;
        }
        return resultObj;
    },

    urlByReg: function(url, config) {
        var urlStr     = strTrim(url),
            // 返回结果
            resultObj       = {},
            // 自定义错误信息
            urlError   = infoObj.urlError;
        // 判断传入的配置信息
        if (typeof config == 'object') {
            urlError = config['error'] ? config['error'] : infoObj.urlError;
        }
        if (regObj.urlReg.test(urlStr)) {
            regObj.urlReg.lastIndex = 0;
            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        } else {
            resultObj['check'] = false;
            resultObj['error'] = urlError;
        }
        return resultObj;
    },


    numberRequire: function(num,config){
        let numStr = strTrim(num),
            resultObj = {},
            numberReqError = infoObj.numberReqError;
        if (typeof config === 'object') {
            numberReqError = config['error'] ? config['error'] : infoObj.numberReqError;
        }
        if (regObj.numberReq.test(numStr)) {
            regObj.numberReq.lastIndex = 0;

            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        }else {
            resultObj['check'] = false;
            resultObj['error'] = numberReqError;
        }
        return resultObj;
    },



    bankCardNumRequire: function(num,config){

        
        let numStr = strTrim(num),
            resultObj = {},
            bankCardRegError = infoObj.bankCardRegError;
        if (typeof config === 'object') {
            bankCardRegError = config['error'] ? config['error'] : infoObj.bankCardRegError;
        }
        if (regObj.bankCardNumReg.test(numStr)) {
            regObj.bankCardNumReg.lastIndex = 0;

            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        }else {
            resultObj['check'] = false;
            resultObj['error'] = bankCardRegError;
        }
        return resultObj;
    },

    idCardNoRequire: function(num,config){

        
        let numStr = strTrim(num),
            resultObj = {},
            idCardNoError = infoObj.idCardNoError;
        if (typeof config === 'object') {
            idCardNoError = config['error'] ? config['error'] : infoObj.idCardNoError;
        }
        if (regObj.idCarNoReg.test(numStr)) {
            regObj.idCarNoReg.lastIndex = 0;

            resultObj['check'] = true;
            resultObj['success'] = '匹配成功';
        }else {
            resultObj['check'] = false;
            resultObj['error'] = idCardNoError;
        }
        return resultObj;
    },
}

module.exports = RegExpTool;