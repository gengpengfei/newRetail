<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    //-- 驱动类型，可选择 sync(默认):同步执行，database:数据库驱动,redis:Redis驱动,topthink:Topthink驱动
    'connector' => 'Redis',
    //-- 任务的过期时间，默认为60秒; 若要禁用，则设置为 null
    'expire'     => 60,
    //-- 默认队列名称
    'default'    => 'default',
    'host'       => \think\Env::get('redis.host','127.0.0.1'),	    // redis 主机ip
    'port'       => \think\Env::get('redis.port','6379'),			// redis 端口
    'password'  => \think\Env::get('redis.password','shike@2018'),				// redis 密码
    'select'     => \think\Env::get('redis.select','0'),				// 使用哪一个 db，默认为 db0
    'timeout'    => 0,				// redis连接的超时时间
    'persistent' => true,			// 是否是长连接
];
