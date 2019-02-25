<?php

namespace app\shopapi\service;
use DfaFilter\SensitiveHelper;
use think\Validate;

class ValidateService extends CommonService
{

    protected $rule = [
        'user_name' => 'require|min:2|max:15',
        'email' => 'email',
        'mobile'=>'require|checkMobile',
        'password'=>'require|min:8|max:15|alphaDash',
        'password_confirm'=>'require|min:8|max:15|alphaDash|confirm:password',
        'store_name'=>'require|min:2|max:12',
        'store_address'=>'min:5|max:30',
        'audit_bank'=>'require',
        'audit_bank_card'=>'require|min:16|max:19',
        'opinion_info'=>'require|min:5|max:500',
        'opinion_mobile'=>'require|checkMobile'
    ];
    protected $field = [
        "user_name" => '用户名',
        "mobile"=>"手机号",
        "password"=>"登陆密码",
        "store_name"=>"店铺名称",
        'store_address'=>'店铺地址',
        'audit_bank'=>'银行开户行',
        'audit_bank_card'=>'银行卡号',
        'opinion_info'=>'意见内容',
        'opinion_mobile'=>'意见手机号'
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
                $this->jkReturn(-1,$msg,array());
            }
        }
    }

    /*
     * explain:敏感词过滤
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/19 9:36
     */
    public function inputValidate($data)
    {
        $badword = array(
            '今天','张三丰','张三丰田'
        );
        echo strtr($data, array_combine($badword,array_fill(0,count($badword),'*')));
    }

    /**
     * 过滤敏感词
     */
    public function CFilterSensitiveWord($content){
        $path = "./CensorWords.txt"; //敏感词文件
        $filterContent = SensitiveHelper::init()->setTreeByFile($path)->replace($content, '***');
        return $filterContent;
    }
}