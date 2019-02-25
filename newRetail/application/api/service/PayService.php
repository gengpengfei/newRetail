<?php
namespace app\api\service;

/**
    +----------------------------------------------------------
     * @explain 支付类
    +----------------------------------------------------------
     * @access class
    +----------------------------------------------------------
     * @return public
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use Yansongda\Pay\Pay;
class PayService{
    use \app\api\traits\GetConfig;
    protected $alipayConfig;
    protected $wechatConfig;
    public function __construct()
    {
        $this->alipayConfig = [
            'app_id' => $this->getConfig('alipay_app_id'),
            'notify_url' => '',
            'return_url' => '',
            'ali_public_key' => $this->getConfig('alipay_public_key'),
//             加密方式： **RSA2**
            'private_key' =>$this->getConfig('alipay_private_key'),
            'log' => [
                'file' => './logs/alipay.log',
                'level' => 'debug'
            ],
            'mode' => 'optional', // optional/dev, 设置此参数dev，将进入沙箱模式
        ];
        $this->wechatConfig = [
            'appid' =>$this->getConfig('weixin_app_id'), // APP APPID
            'app_id' => '', // 公众号 APPID
            'miniapp_id' =>'', // 小程序 APPID
            'mch_id' => $this->getConfig('weixin_merch_id'),//商户号
            'key' => $this->getConfig('weixin_key'),
            'notify_url' => '',
            'cert_client' => ROOT_PATH.'public\pay_static\cert\apiclient_cert.pem', // optional，退款等情况时用到
            'cert_key' => ROOT_PATH.'public\pay_static\cert\apiclient_key.pem',// optional，退款等情况时用到
            'log' => [
                'file' => './logs/wechat.log',
                'level' => 'debug'
            ],
            'mode' => 'optional', // optional/dev, 设置此参数dev，将进入沙箱模式
        ];
    }
    
    /*
     * explain:设置额外配置 (回调地址和异步回调地址等)
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/2 11:15
     */
    public function setConfig($config)
    {
        $this->alipayConfig = array_merge($this->alipayConfig,$config);
        $this->wechatConfig = array_merge($this->wechatConfig,$config);
        return $this;
    }

    /*
     * explain:支付宝APP支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/30 18:21
     */
    public function alipayApp($order)
    {
        return Pay::alipay($this->alipayConfig)->app($order)->getContent();
    }
    /*
     * explain:支付宝网站支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/30 18:21
     */
    public function alipayWeb($order)
    {
        return Pay::alipay($this->alipayConfig)->web($order)->getContent();
    }
    /*
     * explain:支付宝手机APP支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/30 18:26
     */
    public function alipayWap($order)
    {
        return Pay::alipay($this->alipayConfig)->wap($order)->getContent();
    }
    /*
     * explain:支付宝扫码支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/30 18:27
     */
    public function alipayScan($order)
    {
        $result = Pay::alipay($this->alipayConfig)->scan($order);
        return $result->qr_code;
    }
    /*
     * explain:支付宝转账
     * params :$order = [
                    'out_biz_no' => time(),
                    'payee_type' => 'ALIPAY_LOGONID',
                    'payee_account' => 'ghdhjw7124@sandbox.com',
                    'amount' => '0.01',
                    ];
     * authors:Mr.Geng
     * addTime:2018/3/30 18:28
     */
    public function alipayTransfer($order)
    {
        return Pay::alipay($this->alipayConfig)->transfer($order);
    }
    /*
     * explain:支付宝退款
     * params :$order = ['out_trade_no' => '1514027114','refund_amount' => '0.01'];
     * authors:Mr.Geng
     * addTime:2018/3/30 18:41
     */
    public function alipayRefund($order)
    {
        return Pay::alipay($this->alipayConfig)->refund($order);
    }
    
    /*
     * explain:支付宝查询
     * params :$order = ['out_trade_no' => '1514027114'];
     * authors:Mr.Geng
     * addTime:2018/4/2 10:05
     */
    public function alipayFind($order)
    {
        return Pay::alipay($this->alipayConfig)->find($order);
    }
    
    /*
     * explain:支付宝关闭
     * params :$order = ['out_trade_no' => '1514027114'];
     * authors:Mr.Geng
     * addTime:2018/4/2 10:08
     */
    public function alipayClose($order)
    {
        return Pay::alipay($this->alipayConfig)->close($order);
    }

    /*
     * explain:支付宝验签
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/2 10:18
     */
    public function alipayVerify()
    {
        return Pay::alipay($this->alipayConfig)->verify();
    }

    /*
     * explain:支付宝确认支付成功
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/2 10:09
     */
    public function alipaySuccess()
    {
        return Pay::alipay($this->alipayConfig)->success()->getContent();
    }
    
    /*
     * explain:微信公众号支付
     * params :$order = [
                'out_trade_no' => time(),
                'body' => 'subject-测试',
                'total_fee' => '1',
                'openid' => 'onkVf1FjWS5SBxxxxxxxx',
                ];
     * authors:Mr.Geng
     * addTime:2018/4/2 10:53
     */
    public function wechatMp($order)
    {
        return Pay::wechat($this->wechatConfig)->mp($order);
    }

    /*
     * explain:微信手机网站支付
     * params :$order = [
                    'out_trade_no' => time(),
                    'body' => 'subject-测试',
                    'total_fee' => '1',
                ];
     * authors:Mr.Geng
     * addTime:2018/4/2 10:55
     */
    public function wechatWap($order)
    {
        return Pay::wechat($this->wechatConfig)->wap($order)->getContent();
    }

    /*
    * explain:微信App支付
    * params :$order = [
                   'out_trade_no' => time(),
                   'body' => 'subject-测试',
                   'total_fee' => '1',
               ];
    * authors:Mr.Geng
    * addTime:2018/4/2 10:55
    */
    public function wechatApp($order)
    {
        return Pay::wechat($this->wechatConfig)->app($order)->getContent();
    }

    /*
    * explain:微信扫码支付
    * params :$order = [
                   'out_trade_no' => time(),
                   'body' => 'subject-测试',
                   'total_fee' => '1',
               ];
    * authors:Mr.Geng
    * addTime:2018/4/2 10:55
    */
    public function wechatScan($order)
    {
        $result = Pay::wechat($this->wechatConfig)->scan($order);
        return $result->code_url;
    }

    /*
     * explain:微信转账
     * params :$order = [
                    'partner_trade_no' => '',              //商户订单号
                    'openid' => '',                        //收款人的openid
                    'check_name' => 'NO_CHECK',            //NO_CHECK：不校验真实姓名\FORCE_CHECK：强校验真实姓名
                    // 're_user_name'=>'张三',              //check_name为 FORCE_CHECK 校验实名的时候必须提交
                    'amount' => '1',                       //企业付款金额，单位为分
                    'desc' => '帐户提现',                  //付款说明
                ];
     * authors:Mr.Geng
     * addTime:2018/3/30 18:29
     */
    public function wechatTransfer($order)
    {
        return Pay::wechat($this->wechatConfig)->transfer($order);
    }
    
    /*
     * explain:微信小程序支付
     * params :$order = [
                    'out_trade_no' => time(),
                    'body' => 'subject-测试',
                    'total_fee' => '1',
                    'openid' => 'onkVf1FjWS5SBxxxxxxxx',
                ];
     * authors:Mr.Geng
     * addTime:2018/4/2 11:01
     */
    public function wechatMiniapp($order)
    {
        return Pay::wechat($this->wechatConfig)->miniapp($order);
    }
    
    /*
     * explain:微信普通红包
     * params :$order = [
                    'mch_billno' => '商户订单号',
                    'send_name' => '商户名称',
                    'total_amount' => '1',
                    're_openid' => '用户openid',
                    'total_num' => '1',
                    'wishing' => '祝福语',
                    'act_name' => '活动名称',
                    'remark' => '备注',
                ];
     * authors:Mr.Geng
     * addTime:2018/4/2 11:03
     */
    public function wechatRedpack($order)
    {
        return Pay::wechat($this->wechatConfig)->redpack($order);
    }

    /*
     * explain:微信随机红包
     * params :$order = [
                'mch_billno' => '商户订单号',
                'send_name' => '商户名称',
                'total_amount' => '1',
                're_openid' => '用户openid',
                'total_num' => '3',
                'wishing' => '祝福语',
                'act_name' => '活动名称',
                'remark' => '备注',
            ];
     * authors:Mr.Geng
     * addTime:2018/4/2 11:04
     */
    public function wechatGroupRedpack($order)
    {
        return Pay::wechat($this->wechatConfig)->groupRedpack($order);
    }
    
    /*
     * explain:微信退款
     * params :$order = [
                    'out_trade_no' => '1514192025',
                    'out_refund_no' => time(),
                    'total_fee' => '1',
                    'refund_fee' => '1',
                    'refund_desc' => '测试退款haha',
                ];
    默认是非app和小程序退款 , 如果是请加入 ['type' => 'app']
     * authors:Mr.Geng
     * addTime:2018/4/2 11:05
     */
    public function wechatRefund($order)
    {
        return Pay::wechat($this->wechatConfig)->refund($order);
    }
    
    /*
     * explain:微信查询
     * params :$order = ['out_trade_no' => '1514027114'];
     * authors:Mr.Geng
     * addTime:2018/4/2 11:06
     */
    public function wechatFind($order)
    {
        return Pay::wechat($this->wechatConfig)->find($order);
    }

    /*
     * explain:微信关闭订单
     * params :$order = ['out_trade_no' => '1514027114'];
     * authors:Mr.Geng
     * addTime:2018/4/2 11:06
     */
    public function wechatClose($order)
    {
        return Pay::wechat($this->wechatConfig)->close($order);
    }
    
    /*
     * explain:微信验签
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/2 11:08
     */
    public function wechatVerify()
    {
        return Pay::wechat($this->wechatConfig)->verify();
    }
    
    /*
     * explain:微信支付/退款成功
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/2 11:09
     */
    public function wechatSuccess()
    {
        return Pay::wechat($this->wechatConfig)->success()->getContent();
    }
}