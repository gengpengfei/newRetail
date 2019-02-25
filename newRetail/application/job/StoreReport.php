<?php
namespace app\job;
/**
    +----------------------------------------------------------
     * @explain 生成店铺报表的队列任务(失败3次以上重新插入对列执行该任务)
    +----------------------------------------------------------
     * @access php think queue:work --daemon --queue StoreReport
+----------------------------------------------------------
     * @return class
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use think\Db;
use think\queue\Job;

class StoreReport{
    use \app\api\traits\GetConfig;
    public function fire(Job $job, $data){
        if($this->storeReport($data)){
            $job->delete();
        }else{
            //-- 失败超过3次, 重新插入对列
            if ($job->attempts() > 3) {
                $job->release();
            }
        }
    }

    public function storeReport($data)
    {
        //总订单数 , 线上有效订单 ,线上有效订单金额 ,  线下订单数  , 线下订单金额 , 退款订单数 , 退款订单金额 ,下单用户数 , 评论总数  , 店铺积分 , 补贴金额
        $storeId = $data;
        //-- 获取前一天的年月日
        $oldData = date("Y-m-d",strtotime("-1 day",time()));
        $newData = date('Y-m-d');
        $oldDataArray = explode('-',$oldData);
        $newDataArray = explode('-',$newData);
        //-- 昨日总订单数
        $totalOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->count();

        //-- 昨日总订单金额
        $totalOrderPrice = Db::table('new_store_order')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('buy_price');

        //-- 线上有效订单数
        $validOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=0 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->count();

        //-- 线上有效订单金额
        $validOrderPrice = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=0 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('buy_price');

        //-- 线下有效订单数
        $offlineOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=1 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->count();

        //-- 线下有效订单金额
        $offlineOrderlPrice = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=1 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('buy_price');

        //-- 退款订单数
        $refundOrder = Db::table('new_user_voucher_refund')
            ->where("store_id=$storeId and refund_state='D04' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->group('order_id')
            ->count();

        //-- 退款订单金额
        $refundOrderPrice = Db::table('new_user_voucher_refund')
            ->where("store_id=$storeId and refund_state='D04' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('refund_price');

        //-- 下单用户数
        $orderUserNum = Db::table('new_store_order')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->group('user_id')
            ->count();

        //-- 评论总数
        $commentTotal = Db::table('new_store_comment')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->count();

        //-- 店铺补贴积分
        $storeScore = Db::table('new_store_rebate_log')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('score');

        //-- 店铺补贴金额
        $couponsPriceBuyOrder = Db::table('new_store_order')
            ->where("store_id=$storeId and order_type=1 and order_state<>'T01' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('coupons_price');
        $couponsPriceBuyVoucher = Db::table('new_user_voucher')
            ->where("store_id=$storeId and used_state='C03' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('coupons_price');

        //-- 退还补贴金额
        $refundCouponsPrice = Db::table('new_user_voucher_refund')
            ->where("store_id=$storeId and refund_state='D04' and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->sum('coupons_price');
        $storeCouponsPrice = $couponsPriceBuyOrder+$couponsPriceBuyVoucher-$refundCouponsPrice;

        //-- 店铺访问量
        $storeBrowseNum = Db::table('new_store_browse_log')
            ->where("store_id=$storeId and DATE_FORMAT(`create_time`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y-%m-%d')")
            ->count();

        //-- 判断
        $info = Db::table('new_store_report_day')->where(['store_id'=>$storeId,'year'=>$oldDataArray[0],'month'=>$oldDataArray[1],'day'=>$oldDataArray[2]])->find();
        if(!empty($info)){
            return true;
        }
        Db::startTrans();
        //-- 生成日报表
        $storeNav = Db::table('new_store')->where("store_id=$storeId")->find();
        $data = [
            'store_id'=>$storeId,
            'nav_id'=>$storeNav['nav_id'],
            'total_order'=>$totalOrder,
            'total_order_price'=>$totalOrderPrice,
            'valid_order'=>$validOrder,
            'valid_order_price'=>$validOrderPrice,
            'offline_order'=>$offlineOrder,
            'offline_order_price'=>$offlineOrderlPrice,
            'refund_order'=>$refundOrder,
            'refund_order_price'=>$refundOrderPrice,
            'order_user_num'=>$orderUserNum,
            'comment_total'=>$commentTotal,
            'store_score'=>$storeScore,
            'coupons_price'=>$storeCouponsPrice,
            'store_browse_num'=>$storeBrowseNum,
            'year'=>$oldDataArray[0],
            'month'=>$oldDataArray[1],
            'day'=>$oldDataArray[2],
            'create_time'=>date("Y-m-d H:i:s")
        ];
        if(!Db::table('new_store_report_day')->insert($data)){
            Db::rollback();
            return false;
        }
        //-- 更新总统计表
        $storeReportInfo = Db::table('new_store_report')->where(['store_id'=>$storeId])->find();
        if(!empty($storeReportInfo)){
            $totalData = [
                'total_order'=>$totalOrder+$storeReportInfo['total_order'],
                'total_order_price'=>$totalOrderPrice+$storeReportInfo['total_order_price'],
                'valid_order'=>$validOrder+$storeReportInfo['valid_order'],
                'valid_order_price'=>$validOrderPrice+$storeReportInfo['valid_order_price'],
                'offline_order'=>$offlineOrder+$storeReportInfo['offline_order'],
                'offline_order_price'=>$offlineOrderlPrice+$storeReportInfo['offline_order_price'],
                'refund_order'=>$refundOrder+$storeReportInfo['refund_order'],
                'refund_order_price'=>$refundOrderPrice+$storeReportInfo['refund_order_price'],
                'order_user_num'=>$orderUserNum+$storeReportInfo['order_user_num'],
                'comment_total'=>$commentTotal+$storeReportInfo['comment_total'],
                'store_score'=>$storeScore+$storeReportInfo['store_score'],
                'coupons_price'=>$storeCouponsPrice+$storeReportInfo['coupons_price'],
                'store_browse_num'=>$storeBrowseNum+$storeReportInfo['store_browse_num'],
                'update_time'=>date("Y-m-d H:i:s")
            ];
            if(!Db::table('new_store_report')->where(['store_id'=>$storeId])->update($totalData)){
                Db::rollback();
                return false;
            }
        }else{
            $totalData = [
                'store_id'=>$storeId,
                'nav_id'=>$storeNav['nav_id'],
                'total_order'=>$totalOrder,
                'total_order_price'=>$totalOrderPrice,
                'valid_order'=>$validOrder,
                'valid_order_price'=>$validOrderPrice,
                'offline_order'=>$offlineOrder,
                'offline_order_price'=>$offlineOrderlPrice,
                'refund_order'=>$refundOrder,
                'refund_order_price'=>$refundOrderPrice,
                'order_user_num'=>$orderUserNum,
                'comment_total'=>$commentTotal,
                'store_score'=>$storeScore,
                'coupons_price'=>$storeCouponsPrice,
                'store_browse_num'=>$storeBrowseNum,
                'create_time'=>date("Y-m-d H:i:s")
            ];
            if(!Db::table('new_store_report')->insert($totalData)){
                Db::rollback();
                return false;
            }
        }
        $lastMonth = date('Y-m',strtotime('-1 month'));
        $lastYear = date('Y',strtotime('-1 Year'));
        $lastMonthArray = explode('-',$lastMonth);

        $monthReportInfo = Db::table('new_store_report_month')->where(['store_id'=>$storeId,'year'=>$lastMonthArray[0],'month'=>$lastMonthArray[1]])->find();
        $yearReportInfo = Db::table('new_store_report_year')->where(['store_id'=>$storeId,'year'=>$lastYear])->find();
        //-- 生成月报表
        if(empty($monthReportInfo)){
            $year = $lastMonthArray[0];
            $month = $lastMonthArray[1];
            //-- 上月总数
            $totalOrderMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('total_order+total_order_price+valid_order+valid_order_price+offline_order+offline_order_price+refund_order+refund_order_price+order_user_num+comment_total+store_score+coupons_price+store_browse_num');
            $totalOrderPriceMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('total_order_price');
            $validOrderMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('valid_order');
            $validOrderPriceMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('valid_order_price');
            $offlineOrderMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('offline_order');
            $offlineOrderlPriceMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('offline_order_price');
            $refundOrderMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('refund_order');
            $refundOrderPriceMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('refund_order_price');
            $orderUserNumMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('order_user_num');
            $commentTotalMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('comment_total');
            $storeScoreMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('store_score');
            $storeCouponsPriceMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('coupons_price');
            $storeBrowseNumMonth = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year and month=$month")
                ->sum('store_browse_num');
            $dataMonth = [
                'store_id'=>$storeId,
                'nav_id'=>$storeNav['nav_id'],
                'total_order'=>$totalOrderMonth,
                'total_order_price'=>$totalOrderPriceMonth,
                'valid_order'=>$validOrderMonth,
                'valid_order_price'=>$validOrderPriceMonth,
                'offline_order'=>$offlineOrderMonth,
                'offline_order_price'=>$offlineOrderlPriceMonth,
                'refund_order'=>$refundOrderMonth,
                'refund_order_price'=>$refundOrderPriceMonth,
                'order_user_num'=>$orderUserNumMonth,
                'comment_total'=>$commentTotalMonth,
                'store_score'=>$storeScoreMonth,
                'coupons_price'=>$storeCouponsPriceMonth,
                'store_browse_num'=>$storeBrowseNumMonth,
                'year'=>$year,
                'month'=>$month,
                'create_time'=>date("Y-m-d H:i:s")
            ];
            //-- 生成月报表
            if(!Db::table('new_store_report_month')->insert($dataMonth)){
                Db::rollback();
                return false;
            }
        }
        //-- 生成年报表
        if(empty($yearReportInfo) && $newDataArray[0] > '2018'){
            $year = $lastYear;
            //-- 上年总数
            $totalOrderYear = Db::table('new_store_report_month')
                ->where("store_id=$storeId and year=$year")
                ->sum('total_order+total_order_price+valid_order+valid_order_price+offline_order+offline_order_price+refund_order+refund_order_price+order_user_num+comment_total+store_score+coupons_price+store_browse_num');
            $totalOrderPriceYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('total_order_price');
            $validOrderYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('valid_order');
            $validOrderPriceYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('valid_order_price');
            $offlineOrderYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('offline_order');
            $offlineOrderlPriceYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('offline_order_price');
            $refundOrderYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('refund_order');
            $refundOrderPriceYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('refund_order_price');
            $orderUserNumYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('order_user_num');
            $commentTotalYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('comment_total');
            $storeScoreYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('store_score');
            $storeCouponsPriceYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('coupons_price');
            $storeBrowseNumYear = Db::table('new_store_report_day')
                ->where("store_id=$storeId and year=$year")
                ->sum('store_browse_num');

            $dataYear = [
                'store_id'=>$storeId,
                'nav_id'=>$storeNav['nav_id'],
                'total_order'=>$totalOrderYear,
                'total_order_price'=>$totalOrderPriceYear,
                'valid_order'=>$validOrderYear,
                'valid_order_price'=>$validOrderPriceYear,
                'offline_order'=>$offlineOrderYear,
                'offline_order_price'=>$offlineOrderlPriceYear,
                'refund_order'=>$refundOrderYear,
                'refund_order_price'=>$refundOrderPriceYear,
                'order_user_num'=>$orderUserNumYear,
                'comment_total'=>$commentTotalYear,
                'store_score'=>$storeScoreYear,
                'coupons_price'=>$storeCouponsPriceYear,
                'store_browse_num'=>$storeBrowseNumYear,
                'year'=>$year,
                'create_time'=>date("Y-m-d H:i:s")
            ];
            //-- 生成年报表
            if(!Db::table('new_store_report_Year')->insert($dataYear)){
                Db::rollback();
                return false;
            }
        }
        Db::commit();
        return 'ok';
    }
}