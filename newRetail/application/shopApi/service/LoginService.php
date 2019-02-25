<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\shopapi\service;

class LoginService extends CommonService
{
    use \app\shopapi\traits\GetConfig;
    /**
     * 验证上一次发送验证码是否60秒内
     * @return bool
     */
    public function checkCanSendMobileCode($add_time=0){
        if(!empty($add_time) && (time() - strtotime($add_time)) < 60){
            $this->jkReturn(-1,'短信验证码一分钟只能发送一次，请稍后重试',array());
        }else{
            return true;
        }
    }

    /**
     * 验证码是否有效时间内
     * @param int $create_time
     * @return bool
     */
    public function checkMobileCodeEffective($create_time=0){

        if(!empty($create_time) && (time() - strtotime($create_time) <= 60 *($this->getConfig('sms_mobile_code_active_time')))){
            return true;
        }else{
            $this->jkReturn(-1,'该验证码已过期,请重新获取',array());
        }
    }

    /**
     * 手机验证码是否相同
     * @param int $code
     * @param int $postCode
     * @return bool
     */
    public function checkMobileCodeMate($code = 0 , $postCode = 0){
        if(!empty($code) && ($code == $postCode)){
            return true;
        }else{
            $this->jkReturn(-1,'短信验证码不匹配，请重新获取',array());
        }
    }

}