<?php

namespace app\api\service;

class UserService extends CommonService
{

    /*
     * explain:用户下单判定
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/8 9:25
     */
    public function judgeUser()
    {
        $userInfo = request()->user;
        empty($userInfo) && $this->jkReturn('-1','该用户不存在或已被冻结',[]);
    }

}