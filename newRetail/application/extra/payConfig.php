<?php

return [
    'alipay'=>[
        'app_id' => '',
        'notify_url' => '',
        'return_url' => '',
        'ali_public_key' => '',
        // 加密方式： **RSA2**
        'private_key' => '',
        'log' => [
            'file' => './logs/alipay.log',
            'level' => 'debug'
        ],
        'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
    ],
    'wechat'=>[
        'appid' => 'wxa3dd959056ec940b', // APP APPID
        'app_id' => '', // 公众号 APPID
        'miniapp_id' => '', // 小程序 APPID
        'mch_id' => '',
        'key' => '',
        'notify_url' => '',
        'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key' => './cert/apiclient_key.pem',// optional，退款等情况时用到
        'log' => [
            'file' => './logs/wechat.log',
            'level' => 'debug'
        ],
        'mode' => 'dev', // optional, 设置此参数，将进入沙箱模式
    ]
];
