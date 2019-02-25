<?php

namespace app\shop\service;
use think\Validate;

class ValidateService extends CommonService
{
    use \traits\controller\Jump;
    protected $rule = [
        'user_name' => 'require|min:2|max:15',
        'email' => 'email',
        'mobile'=>'require|checkMobile',
        'password'=>'require|min:8|max:15|alphaDash',
        'password_confirm'=>'require|min:8|max:15|alphaDash|confirm:password',
        'captcha'=>'require|captcha',
    ];
    protected $field = [
        "user_name" => '用户名',
        "mobile"=>"手机号",
        "password"=>"登陆密码",
    ];

    protected $data;
    protected $validate;
    public function __construct()
    {
        $this->validate = new Validate([],[],$this->field);
        //-- 自定义检验规则
        \think\Validate::extend('checkMobile', function ($value) {
            return preg_match('/^0?(13[0-9]|15[012356789]|17[013678]|18[0-9]|14[57])[0-9]{8}$/', $value)? true : false;
        });
        \think\Validate::setTypeMsg('checkMobile', '手机号格式错误!');
    }
    public function validate($data)
    {
        $this->data = $data;
        foreach ($this->data as $key => $v) {
            if (!empty($this->rule[$key])) {
                $validator[$key] = $this->rule[$key];
            }
        }
        if(!empty($validator)){
            $this->validate->rule($validator);
            if (!$this->validate->check($this->data)) {
                $msg = $this->validate->getError();
                $this->error($msg);
            }
        }
    }
}