<?php
namespace app\shop\traits;

trait BuildParam{

    /*
     * explain:获取当前时间
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/4 13:49
     */
    public function getTime()
    {
        return date("Y-m-d H:i:s",time());
    }

    /*
     * explain:获取当天零点时间
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 16:13
     */
    public function getTimeToday()
    {
        return date("Y-m-d H:i:s",strtotime(date("Y-m-d",time())));
    }

    /*
     * explain:获取一周前的时间
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/10 16:29
     */
    public function getTimeWeek()
    {
        return date("Y-m-d H:i:s",strtotime(date('Y-m-d', strtotime("-7 day"))));
    }


    /*
     * explain:获取x天前后的时间
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/11 16:22
     */
    public function getTimeX($day)
    {
        return date('Y-m-d H:i:s', strtotime("$day day"));
    }

    /*
     * explain:创建订单sn编码
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 15:46
     */
    public function getOrderSn()
    {
        return date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /*
     * explain:创建券号sn编码
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 15:47
     */
    public function getVoucherSn()
    {
        return substr(time(),3) .str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 随机生成指定长度的数字
     * @param number $length
     * @return number
     */
    function randNumber($length = 6) {
        if ($length < 1) {
            $length = 6;
        }
        $min = 1;
        for($i = 0; $i < $length - 1; $i ++) {
            $min = $min * 10;
        }
        $max = $min * 10 - 1;
        return rand ( $min, $max );
    }

    /*
     * explain:生成图片名称
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/9 17:39
     */
    function imgName(){
        return time().rand(1,100);
    }
}
