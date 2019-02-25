<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\shop\service;

use app\shop\controller\Common;
use think\Controller;

class CommonService
{
    /**
     * 统一接口返回模板
     *
     * @param array  $data        返回数据
     * @param string $msg         返回信息
     * @param int    $code        编码
     * @param string $type        类型
     * @param int    $json_option
     */
    public function jkReturn($code,$msg,$data='',$type='',$json_option=0)
    {
        if (empty($type)) {
            $type = "JSON";
        }
        switch (strtoupper($type)) {
            case 'JSON' :
                //---------- 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], $json_option));
            case 'XML'  :
                //----------- 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode(['code' => $code, 'msg' => $msg, 'data' => $data]));
            case 'JSONP':
                //----------- 返回JSON数据格式到客户端
                header('Content-Type:application/json; charset=utf-8');
                exit('(' . json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], $json_option) . ');');
            default     :
                //----------- 用于扩展其他返回格式数据
        }
    }

}