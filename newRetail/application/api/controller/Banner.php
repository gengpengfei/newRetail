<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/5
 * Time: 14:31
 */

namespace app\api\controller;

use app\api\model\BannerModel;
class Banner extends Common
{
    /*
     * explain:首页banner
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 10:39
     */
    public function indexBanner(BannerModel $bannerModel){
        $where['disabled'] = 1;
        $where['position_name'] = 'indexBanner';
        $where['start_time'] = array(ELT,date("Y-m-d H:i:s",time()));
        $where['end_time'] = array(EGT,date("Y-m-d H:i:s",time()));
        $bannerList = $bannerModel
            ->alias('b')
            ->where($where)
            ->join('new_banner_position p','p.position_id=b.position_id','left')
            ->order(['sort_order'=>'asc'])
            ->select();
        $this->jkReturn(1,'首页banner',$bannerList);
    }

    /*
     * explain:首页中间部分banner
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 10:39
     */
    public function indexMiddBanner(BannerModel $bannerModel){
        $where['disabled'] = 1;
        $where['position_name'] = 'indexMiddBanner';
        $where['start_time'] = array(ELT,date("Y-m-d H:i:s",time()));
        $where['end_time'] = array(EGT,date("Y-m-d H:i:s",time()));
        $bannerList = $bannerModel
            ->alias('b')
            ->where($where)
            ->join('new_banner_position p','p.position_id=b.position_id','left')
            ->order(['sort_order'=>'asc'])
            ->select();
        $this->jkReturn(1,'首页中间部分banner',$bannerList);
    }

    /*
     * explain:积分商城banner
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 10:39
     */
    public function shopBanner(BannerModel $bannerModel)
    {
        $where['position_name'] = 'shopBanner';
        $where['disabled'] = 1;
        $where['start_time'] = array(ELT,date("Y-m-d H:i:s",time()));
        $where['end_time'] = array(EGT,date("Y-m-d H:i:s",time()));
        $bannerList = $bannerModel
            ->alias('b')
            ->where($where)
            ->join('new_banner_position p','p.position_id=b.position_id','left')
            ->order(['sort_order'=>'asc'])
            ->select();
        $this->jkReturn(1,'积分商城banner',$bannerList);
    }

    /*
     * explain:首页附近优惠
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 10:39
     */
    public function indexNearBanner(BannerModel $bannerModel)
    {
        $where['position_name'] = 'indexNear';
        $where['disabled'] = 1;
        $where['start_time'] = array(ELT,date("Y-m-d H:i:s",time()));
        $where['end_time'] = array(EGT,date("Y-m-d H:i:s",time()));
        $bannerList = $bannerModel
            ->alias('b')
            ->where($where)
            ->join('new_banner_position p','p.position_id=b.position_id','left')
            ->order(['sort_order'=>'asc'])
            ->select();
        $this->jkReturn(1,'附近优惠banner',$bannerList);
    }

    /*
     * explain:首页top榜banner
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/3 10:39
     */
    public function indexTopBanner(BannerModel $bannerModel)
    {

        $where['disabled'] = 1;
        $where['start_time'] = array(ELT,date("Y-m-d H:i:s",time()));
        $where['end_time'] = array(EGT,date("Y-m-d H:i:s",time()));
        $banner = array('indexPraiseStore','indexTopStore','indexNearStore');
        $bannerList = array();
        foreach ($banner as $v){
            $where['position_name'] = $v;
            $banner = $bannerModel
                ->alias('b')
                ->where($where)
                ->join('new_banner_position p','p.position_id=b.position_id','left')
                ->order(['sort_order'=>'asc'])
                ->find();
            $bannerList[] = $banner??[];
        }
        $this->jkReturn(1,'积分商城banner',$bannerList);
    }
}