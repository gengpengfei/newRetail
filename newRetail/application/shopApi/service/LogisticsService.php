<?php
namespace app\shopapi\service;

class LogisticsService
{
    public function logisticsInfo($invoice_no)
    {
        header('Access-Control-Allow-Origin:*');
        header("Content-type: text/html; charset=utf-8");
        $host = "http://jisukdcx.market.alicloudapi.com";
        $path = "/express/query";
        $method = "GET";
        $appcode = "4c36dc61251c4a0fbec228f17ad9d0ef";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "number=$invoice_no&type=auto";
        $url = $host . $path . "?" . $querys;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result);
        if (empty($result->result))
            return array();
        $array = $this->object_array($result->result->list);
        return $array;
    }

    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }
}
