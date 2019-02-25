<?php
namespace app\common\exception;
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/5
 * Time: 18:28
 */

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\PDOException;
use think\exception\ThrowableError;
use think\exception\ValidateException;

class HException extends Handle
{
    public function render(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json($e->getError(), 422);
        }
        // 请求异常
        if ($e && $_REQUEST['isAjax']) {
            $data['code'] = -1;
            $data['msg'] = $e->getMessage();
            $data['data'] = [];
            print_r($e);die;
        }

        //TODO::开发者对异常的操作
        //可以在此交由系统处理
        return parent::render($e);
    }
}