<?php
namespace app\api\controller;
/**
    +----------------------------------------------------------
     * @explain 店铺类
    +----------------------------------------------------------
     * @access class
    +----------------------------------------------------------
     * @return Store
    +----------------------------------------------------------
     * @acter Mr.Geng
    +----------------------------------------------------------
**/
use app\api\model\DistanceIntervalModel;
use app\api\model\PriceIntervalModel;
use app\api\model\RefundReasonModel;
use app\api\model\SearchLogModel;
use app\api\model\StoreBrowseLogModel;
use app\api\model\StoreConfigModel;
use app\api\model\StoreOrderModel;
use app\api\model\StoreProLikeModel;
use app\api\model\StoreProModel;
use app\api\model\StoreProtectModel;
use app\api\model\StoreCommentModel;
use app\api\model\StoreModel;
use app\api\model\StoreReserveConfigModel;
use app\api\model\StoreReserveModel;
use app\api\model\StoreReserveReasonModel;
use app\api\model\StoreVoucherAttrModel;
use app\api\model\StoreVoucherModel;
use app\api\model\UserCouponsModel;
use app\api\model\UserMoneyLogModel;
use app\api\model\UserScoreLogModel;
use app\api\model\UsersModel;
use app\api\model\UserTraceModel;
use app\api\model\UserVoucherModel;
use app\api\model\UserVoucherRefundModel;
use app\api\model\StoreRebateLogModel;
use app\api\service\PayService;
use app\api\service\StoreService;
use app\api\service\UserService;
use geohash\Geohash;
use think\cache\driver\Redis;
use think\Config;
use think\Queue;
use think\Request;

class Store extends Common {
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;

    public function test(StoreOrderModel $storeOrderModel)
    {
        $storePrice = $storeOrderModel->where("store_id=1 and order_state<>'T01'")->avg('buy_price');
        $storePrice = sprintf('%.0f',$storePrice);
        var_dump($storePrice);die;
    }
    /*
     * params :@locationData 位置信息
     * explain:首页 - 人气好店 top20
     * authors:Mr.Geng
     * addTime:2018/3/12 16:37
     */
    public function storeTop(Request $request,StoreModel $storeModel,StoreService $storeService)
    {
        $city_id = $request->locationData->city_id;
        $store_list = $storeModel
            ->where(["audit_state"=>1,"disabled"=>1,'city'=>$city_id,'is_close'=>0])
            ->limit(20)
            ->order("store_hot","desc")
            ->select();
        //算出实际距离
        foreach ($store_list as &$v){
            $storeService->getStoreDistance($v);
        }
        $this->jkReturn(1,"店铺人气top20",$store_list);
    }

    /*
     * params :@locationData 位置信息
     * explain:首页 - 好评店铺 top20
     * authors:Mr.Geng
     * addTime:2018/3/12 16:37
     */
    public function storePraise(StoreModel $storeModel,StoreService $storeService)
    {
        $city_id = request()->locationData->city_id;
        $list = $storeModel
            ->where(["audit_state"=>1,"disabled"=>1,'city'=>$city_id,'is_close'=>0])
            ->limit(20)
            ->order('comment_num','desc')
            ->select();
        //算出实际距离
        foreach ($list as &$v){
            $storeService->getStoreDistance($v);
        }
        $this->jkReturn(1,'好评店铺榜',$list);
    }

    /*
     * params :@locationData 位置信息
     * explain:首页 - 附近好店 top20
     * authors:Mr.Geng
     * addTime:2018/3/20 13:59
     */
    public function storeNear(Request $request,StoreModel $storeModel,StoreService $storeService)
    {
        $cityId = $request->locationData->city_id;

        //-- 后台配置的距离范围
        $nearDistance = $this->getConfig('near_distance');
        //-- 当前位置geohash值
        $like_geohash = $storeService->getGeohashLike($nearDistance);
        $store_list = $storeModel
            ->where("geohash like '".$like_geohash."%' and audit_state=1 and disabled=1 and city=$cityId and is_close=0")
            ->select();
        //算出实际距离
        $nearDistance = $this->getConfig('near_distance');
        foreach ($store_list as &$v){
            $storeService->getStoreDistance($v);
            //排序列
            if($v->distance>$nearDistance*1000){
                unset($v);
            }
        }
        //距离排序
        $store_list = $store_list->toArray();
        array_multisort(array_column($store_list,'distance'),SORT_ASC,$store_list);
        $store_list = array_slice($store_list,0,20);
        $this->jkReturn(1,"附近好店榜",$store_list);
    }

    /**
     * 附近优惠
    */
    public function storeNearPromotion(Geohash $geohash,StoreModel $storeModel,StoreService $storeService){
        //-- 获取请求参数的值
        $locationData = request()->locationData;
        $cityId = $locationData->city_id;
        $categoryId = request()->param('categoryId');
        $fineness = request()->param('fineness');
        $n_geohash = $geohash->encode($locationData->lat,$locationData->lng);
        //-- 后台配置的距离范围
        $nearDistance = $this->getConfig('near_distance');
        //-- 参数n代表Geohash精确的位数,就是大概距离；n=6时候，大概为附近1.2千米 n=5 为附近2.4千米 , n=4 为附近20千米
        switch($nearDistance){
            case 1:
                $n = 6;
                break;
            case 2:
                $n = 5;
                break;
            default:
                $n = 4;
        }
        $like_geohash = substr($n_geohash, 0, $n);
        if(!empty($fineness)){
            $sqlStr = "geohash like '".$like_geohash."%' AND v.is_push=1 and audit_state=1 and s.disabled=1 and s.city=$cityId and is_close=0 ";
        }
        else{
            $sqlStr = "geohash like '".$like_geohash."%' AND find_in_set(".$categoryId.",category_id) and audit_state=1 and s.disabled=1 and s.city=$cityId and is_close=0 ";
        }
        $time = $this->getTime();
        $sqlStr .= " and v.disabled=1 and v.sell_start_date<'".$time."' and '".$time."'<sell_end_date";
        $store_list = $storeModel
            ->alias('s')
            ->field('s.lat,s.lng,s.store_img,s.store_keywords,s.store_name,s.store_hot,s.store_address,v.*')
            ->where($sqlStr)
            ->join('new_store_voucher v','v.store_id=s.store_id','left')
            ->group('v.store_id')
            ->select();
        //算出实际距离
        foreach ($store_list as &$v){
            $storeService->getStoreDistance($v);
            //排序列
            if($v->distance>$nearDistance*1000){
                unset($v);
            }
        }
        //距离排序
        $store_list = $store_list->toArray();
        array_multisort(array_column($store_list,'distance'),SORT_ASC,$store_list);
        $this->jkReturn(1,"附近优惠",$store_list);
    }

    /*
     * params :首页 - 猜你喜欢
     * explain:
     * authors:Mr.Geng
     * addTime:2018/3/13 16:43
     */
    public function storeLike(Request $request,StoreModel $storeModel,StoreService $storeService,Geohash $geohash,UserTraceModel $userTraceModel)
    {
        $city_id = request()->locationData->city_id;
        $user_id = $request->param('user_id');
        $where = "city=$city_id and s.disabled=1 and audit_state=1 and is_close=0";
        if(!empty($user_id)){
            //-- 获取两个历史访问的分类
            $category = $userTraceModel->field('category_id')->where('user_id',$user_id)->limit(2)->order('create_time','asc')->group('category_id')->select();
            $whereHas = '';
            if($category->count()==0){
                $whereHas = $where;
            }
            if($category->count()==1){
                $whereHas = $where." and find_in_set(".$category[0]->category_id.",category_id) ";
            }
            if($category->count()==2){
                $whereHas = $where." and (find_in_set(".$category[0]->category_id.",category_id) or find_in_set(".$category[1]->category_id.",category_id))";
            }
            $storeList = $storeModel
                ->alias('s')
                ->field('s.*,n.nav_name')
                ->where($whereHas)
                ->join('new_nav n','n.nav_id=s.nav_id','left')
                ->limit(50)
                ->select();
        }else{
            $storeList = $storeModel
                ->alias('s')
                ->field('s.*,n.nav_name')
                ->where($where)
                ->join('new_nav n','n.nav_id=s.nav_id','left')
                ->limit(50)
                ->select();
        }
        //算出实际距离
        foreach ($storeList as &$v){
            $storeService->getStoreDistance($v);
        }
        $this->jkReturn(1,"猜你喜欢",$storeList);
    }

    /*
     * params :
     * explain:店铺列表
     * authors:Mr.Geng
     * addTime:2018/3/13 19:11
     */
    public function storeList(Request $request,StoreModel $storeModel,StoreService $storeServer,PriceIntervalModel $priceIntervalModel,DistanceIntervalModel $distanceIntervalModel,SearchLogModel $searchLogModel,StoreService $storeService)
    {
        $req = $request->param();
        $req['distance_num'] = sprintf('%.0f',$req['distance_num']);
        //-- 当前选择的城市id
        $locationData = $request->locationData;
        $city_id = $locationData->city_id;
        if(empty($city_id)){
            $this->jkReturn(-1,'请选择当前城市',' ');
        }
        $where = 'audit_state=1 and s.disabled=1 and city='.$city_id.' and is_close=0 ';
        $order = ['store_hot'=>'desc'];
        //-- 选择区县
        if($locationData->district_id??0){
            $where .= " and district=".$locationData->district_id;
        }
        //-- 好评
        if($req['comment_num'] ?? 0)
            $order["comment_num"] = 'desc';
        //-- 行业分类
        if($req['nav_id'] ?? 0)
            $where .= " and s.nav_id = ".$req['nav_id'] ;
        //-- 人气值
        if($req['store_hot'] ?? 0)
            $order["store_hot"] = 'desc';
        //-- 分类
        if($req['category_id'] ?? 0){
            $where .= " and find_in_set(".$req['category_id'].",category_id) ";
        }
        //-- 人均价格区间
        if($req['price_interval_id']??0){
            $priceInterval = $priceIntervalModel->find($req['price_interval_id']);
            $where .= "and store_price>={$priceInterval->min_price} and store_price<={$priceInterval->max_price} ";
        }
        //-- 距离区间
        if($req['distance_num']??0){
            //-- 当前位置geohash值
            $like_geohash = $storeService->getGeohashLike($req['distance_num']);
            $where .= ' and s.geohash like "' .$like_geohash.'%" ';
        }
        //-- 关键字检索
        if($req['keywords'] ?? 0){
            $where .= " and (LOCATE('".$req['keywords']."', `store_name`)>0 or LOCATE('".$req['keywords']."', `store_desc`)>0 or LOCATE('".$req['keywords']."', `store_keywords`)>0 or store_id in ( select store_id from new_store_pro where city= $city_id and ( LOCATE('".$req['keywords']."', `store_pro_name`)>0 or LOCATE('".$req['keywords']."', `store_pro_keywords`)>0) ) ) ";
        }
        $store_list = $storeModel
            ->alias('s')
            ->field('s.*,n.nav_name')
            ->where($where)
            ->join('new_nav n','n.nav_id=s.nav_id','left')
            ->limit($req["limit"]??300000)
            ->page($req['page']??1)
            ->order($order)
            ->select();

        foreach ($store_list as &$v){
            $storeServer->getStoreDistance($v);
            //判断是否按距离排序
            if(($req['distance_num'])??0){
                if($v->distance>($req['distance_num'])*1000){
                    unset($v);
                }
            }
        }
        if($req['distance_sort']??0){
            //距离排序
            $store_list = $store_list->toArray();
            array_multisort(array_column($store_list,'distance'),SORT_ASC,$store_list);
        }
        if($req['keywords'] ?? 0){
            //-- 存储关键字
            $num = count($store_list);
            if($count=$searchLogModel->where('search_name',$req['keywords'])->count()>0){
                $searchLogModel->where('search_name',$req['keywords'])->setInc('search_times',1);
                $searchLogModel->update(['search_num'=>$num],['search_name'=>$req['keywords']]);
            }else{
                $searchLogModel->create(['search_name'=>$req['keywords'],'search_num'=>$num]);
            }
        }
        $this->jkReturn(1,'店铺列表',$store_list);
    }

    /*
 * params :
 * explain:附近优惠
 * authors:Mr.Geng
 * addTime:2018/3/13 19:11
 */
    public function nearbyDiscount(Request $request,StoreModel $storeModel,StoreService $storeService,SearchLogModel $searchLogModel)
    {
        $req = $request->param();
        $time = $this->getTime();

        //-- 当前选择的城市id
        $locationData = $request->locationData;
        $city_id = $locationData->city_id;
        if(empty($city_id)){
            $this->jkReturn(-1,'请选择当前城市',' ');
        }
        //-- 后台配置的距离范围
        $nearDistance = $this->getConfig('near_distance');
        //-- 当前位置geohash值
        $like_geohash = $storeService->getGeohashLike($nearDistance);
        $where = 's.audit_state=1 and s.disabled=1 and s.city='.$city_id.' and s.is_close=0 and s.geohash like "' .$like_geohash.'%"  and v.sell_start_date < "' . $time . '" and  v.sell_end_date > "' . $time . '"';
        // and sell_start_date<'".$time."' and '".$time."'< sell_end_date
        //
        //-- 行业分类
        if($req['nav_id'] ?? 0)
            $where .= " and s.nav_id = ".$req['nav_id'] ;
        //-- 关键字检索
        if($req['keywords'] ?? 0){
            $where .= " and (LOCATE('".$req['keywords']."', `store_name`)>0 or LOCATE('".$req['keywords']."', `store_desc`)>0 or LOCATE('".$req['keywords']."', `store_keywords`)>0 or store_id in ( select store_id from new_store_pro where city= $city_id and ( LOCATE('".$req['keywords']."', `store_pro_name`)>0 or LOCATE('".$req['keywords']."', `store_pro_keywords`)>0) ) ) ";
        }
        $store_list = $storeModel
            ->alias('s')
            ->field('v.disabled as is_dev,s.*,n.nav_name,v.voucher_id,v.voucher_name,v.voucher_price,v.voucher_type,v.voucher_img')
            ->where($where)
            ->join('new_store_voucher v','v.store_id = s.store_id and v.disabled = 1','left')
            ->join('new_nav n','n.nav_id=s.nav_id','left')
            ->limit($req["limit"]??300000)
            ->page($req['page']??1)
            ->group('s.store_id')
            ->select();
        //算出实际距离
        $nearDistance = $this->getConfig('near_distance');
        foreach ($store_list as &$v){
            $storeService->getStoreDistance($v);
            //排序列
            if($v->distance>$nearDistance*1000){
                unset($v);
            }
        }
        //距离排序
        $store_list = $store_list->toArray();
        array_multisort(array_column($store_list,'distance'),SORT_ASC,$store_list);
        if($req['keywords'] ?? 0){
            //-- 存储关键字
            $num = count($store_list);
            if($count=$searchLogModel->where('search_name',$req['keywords'])->count()>0){
                $searchLogModel->where('search_name',$req['keywords'])->setInc('search_times',1);
                $searchLogModel->update(['search_num'=>$num],['search_name'=>$req['keywords']]);
            }else{
                $searchLogModel->create(['search_name'=>$req['keywords'],'search_num'=>$num]);
            }
        }

        $this->jkReturn(1,'店铺列表',$store_list);
    }

    /*
     * params :@store_id 店铺id
     * explain:店铺广告
     * authors:Mr.Geng
     * addTime:2018/3/15 9:36
     */
    public function storeBanner(Request $request,StoreModel $storeModel)
    {
        $store_id = $request->param("store_id");
        $storeBannerList = $storeModel
            ->field('store_banner_img')
            ->where(["store_id"=>$store_id])
            ->find();
        $this->jkReturn('1',"店铺广告",$storeBannerList->store_banner_img);
    }

    /*
     * params :@store_id 店铺id
     * explain:店铺详情
     * authors:Mr.Geng
     * addTime:2018/3/14 11:50
     */
    public function storeDetail(Request $request,StoreModel $storeModel,StoreService $storeService,UserTraceModel $userTraceModel,StoreBrowseLogModel $storeBrowseLogModel)
    {
        $storeId = $request->param("store_id");
        if(empty($storeId)){
            $this->jkReturn(-1,"参数错误",[]);
        }
        //-- append 为追加属性 用于判断用户是否收藏
        $storeInfo = $storeModel
            ->append(['is_collect','is_reserve'])
            ->where(["store_id"=>$storeId])
            ->find();
        !$storeInfo && $this->jkReturn(-1,"参数错误",[]);
        //-- 计算距离
        $storeService->getStoreDistance($storeInfo);
        //-- 转义店铺详情
        $storeInfo->store_info = htmlspecialchars_decode(htmlspecialchars_decode($storeInfo->store_info));
        if($userId=$request->param('user_id')??0){
            //-- 插入用户足迹
            $data = [
                'user_id'=>$userId,
                'store_id'=>$storeInfo->store_id,
                'category_id'=>end(explode(',',$storeInfo->category_id)),
                'is_line'=>0
            ];
            $userTraceModel->where($data)->delete();
            $userTraceModel->allowField(true)->create($data);
            //-- 记录用户访问店铺足迹
            $storeBrowseLogModel->where(['user_id'=>$userId,'store_id'=>$storeInfo->store_id])->delete();
            $storeBrowseLogModel->allowField(true)->create(['user_id'=>$userId,'store_id'=>$storeInfo->store_id]);
        }
        $this->jkReturn(1,"店铺详情",$storeInfo);
    }

    /*
     * params :@store_id 店铺id
     * explain:店铺详情（富文本详情）
     * authors:Mr.Geng
     * addTime:2018/3/14 11:50
     */
    public function storeDetailArticle(Request $request,StoreModel $storeModel)
    {
        $storeId = $request->param("store_id");
        $storeInfo = $storeModel
            ->field('store_info')
            ->where(["store_id"=>$storeId])
            ->find();
        //-- 转义店铺详情
        $content = htmlspecialchars_decode(htmlspecialchars_decode($storeInfo->store_info));
        $this->assign('content',$content);
        return view("Store/store_detail_article");
    }

    /*
     * explain:店铺配置信息
     * params :@store_id
     * authors:Mr.Geng
     * addTime:2018/5/9 17:31
     */
    public function storeConfig(Request $request,StoreConfigModel $storeConfigModel )
    {
        $param = $request->param();
        $system = $storeConfigModel->where("store_id=".$param['store_id'])->column('value','code');
        $this->jkReturn('1','店铺配置',$system);
    }

    /*
     * params :@store_id 店铺id @limit @page @voucher_type
     * explain:店铺抵扣券
     * authors:Mr.Geng
     * addTime:2018/3/15 11:40
     */
    public function storeVoucherList(Request $request,StoreVoucherModel $storeVoucherModel)
    {
        $store_id = $request->param('store_id');
        $time = $this->getTime();
        $where = "store_id = $store_id and disabled=1 and sell_start_date<'".$time."' and '".$time."'<sell_end_date and '".$time."'<use_end_date";
        if($request->has('voucher_type')){
            $voucherType = $request->param('voucher_type');
            $where .= " and voucher_type=$voucherType ";
        }
        $voucherList = $storeVoucherModel
            ->where( $where)
            ->limit($request->param('limit')??3)
            ->page($request->param('page')??1)
            ->order('sell_num','desc')
            ->select();
        $this->jkReturn(1,"优惠券列表",$voucherList);
    }

    /*
     * explain:店铺抵扣券详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 14:36
     */
    public function storeVoucherInfo(Request $request,StoreVoucherModel $storeVoucherModel,StoreVoucherAttrModel $storeVoucherAttrModel,StoreModel $storeModel)
    {
        $voucherId = $request->param('voucher_id');
        $storeId = $request->param('store_id');
        $time = $this->getTime();
        $voucherInfo = $storeVoucherModel
            ->append('is_collect')
            ->where("voucher_id = $voucherId and store_id=$storeId and sell_start_date<'".$time."' and '".$time."'< sell_end_date and disabled=1")
            ->find();
        if(empty($voucherInfo)){
            $this->jkReturn(-1,'该商品已下架!');
        }
        $storeInfo = $storeModel->field('store_name,store_hot,store_address')->where(['store_id'=>$storeId])->find();
        $voucherInfo->store_name = $storeInfo->store_name;
        $voucherInfo->store_hot = $storeInfo->store_hot;
        $voucherInfo->store_address = $storeInfo->store_address;
        $voucherAttr = $storeVoucherAttrModel
            ->alias('a')
            ->field('a.*,r.attr_name')
            ->join('new_store_attr_rule r','r.attr_rule_id=a.attr_rule_id','LEFT')
            ->where(['voucher_id'=>$voucherId,'store_id'=>$storeId])
            ->select();
        $data = [
            'voucher_info'=>$voucherInfo,
            'voucher_attr'=>$voucherAttr
        ];
        $this->jkReturn(1,"店铺抵扣券详情",$data);
    }
        /*
     * params :@store_id 店铺id
     * explain:店铺抵用券详情（富文本详情）
     * authors:Mr.Geng
     * addTime:2018/3/14 11:50
     */
    public function storeVoucherDetailArticle(Request $request,StoreVoucherModel $storeVoucherModel)
    {
        $voucherId = $request->param('voucher_id');
        $storeId = $request->param('store_id');
        $voucherInfo = $storeVoucherModel
            ->field('voucher_info')
            ->where(["store_id"=>$storeId,"voucher_id"=>$voucherId])
            ->find();
        //-- 转义店铺详情
        $content = htmlspecialchars_decode(htmlspecialchars_decode($voucherInfo->voucher_info));
        $this->assign('content',$content);
        return view("Store/store_voucher_detail_article");
    }
    /*
     * explain:店铺商品列表
     * params :@store_id 店铺id @limit @page
     * authors:Mr.Geng
     * addTime:2018/3/21 16:01
     */
    public function storePro(Request $request,StoreProModel $storeProModel )
    {
        $param = $request->param();
        $where = "store_id = {$param['store_id']} and is_show = ".$param['is_show'];
        //-- append 为追加属性 用于判断用户是否点赞
        $proList = $storeProModel->append('is_like')->where( $where)->limit($request->param('limit')??4)->page($request->param('page')??1)->order('store_pro_like','desc')->select();
        $this->jkReturn(1,"店铺单品列表",$proList);
    }

    /*
     * params :@store_id 店铺id @limit 分页 @page 分页
     * explain:店铺评论列表
     * authors:Mr.Geng
     * addTime:2018/3/14 11:52
     */
    public function storeComment(Request $request,StoreCommentModel $storeCommentModel)
    {
        $param = $request->param();
        //--------商品评价数量统计
        $com1 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>1])->count();
        $com2 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>2])->count();
        $com3 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>3])->count();
        $com4 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>4])->count();
        $all = $com1+$com2+$com3+$com4;
        $com1_ = $all==0? 0: $com1/$all;
        $com2_ = $all==0? 0: $com2/$all;
        $com3_ = $all==0? 0: $com3/$all;
        $com4_ = $all==0? 0: $com4/$all;
        $where = ["store_id"=>$param['store_id']];
        if ($param['has_img']??0) $where['has_img'] = 1;
        if ($param['comment_num']??0) $where['comment_num'] = $param['comment_num'];
        $store_comment_list = $storeCommentModel
            ->append('is_collect')
            ->alias('c')
            ->field('c.*,u.user_name,u.head_img')
            ->where($where)
            ->join('new_users u','u.user_id=c.user_id','left')
            ->limit($request->param('limit')??3)
            ->page($request->param('page')?? 1)
            ->order('create_time','asc')
            ->select();

        $data = [
            'comment_list'=>$store_comment_list,//商品评价列表
            'comment_info'=>[
                ['超赞',$com4_,$com4],
                ['满意',$com3_,$com3],
                ['一般',$com2_,$com2],
                ['差评',$com1_,$com1]
            ],
            'satisfaction'=>$all==0? '0%': sprintf('%.0f',(($com3+$com4)/$all*100)).'%'
        ];
        $this->jkReturn(1,'店铺评论',$data);
    }
    
    /*
     * explain:权益保障列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/3/26 14:58
     */
    public function storeProtect(StoreProtectModel $storeProtectModel)
    {
        $list = $storeProtectModel->where('disabled','1')->order('sort_order','asc')->select();
        $this->jkReturn(1,'权益保障列表',$list);
    }

    /*
     * explain:生成券号二维码
     * params :type 1:店铺二维码 2:券号核销二维码
     * authors:Mr.Geng
     * addTime:2018/6/6 10:13
     */
    public function getVoucherSignCode(Request $request,StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel)
    {
        $param = $request->param();
        //-- 查看可使用券码
        $userVoucherList = $userVoucherModel
            ->append('refund_state')
            ->where("order_sn='{$param['order_sn']}' and used_state='C02'")
            ->select();
        foreach ($userVoucherList as $v){
            if(empty($v->refund_state)){
                $codeInfo = $this->authCode($v->voucher_sn,'ENCODE','shike');
                $data[] = [
                    'code'=>$codeInfo,
                    'type'=>2,
                    'sign'=>'9ef72e6748a99be8ae83',
                    'voucher_sn'=>$v->voucher_sn
                ];
            }
        }
        $this->jkReturn('1','店铺抵用券二维码',$data);
    }

    /*
     * explain:判断订单是否存在
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/25 10:02
     */
    public function orderIsSuccess(Request $request,StoreOrderModel $storeOrderModel)
    {
        $orderSn = $request->param('order_sn');
        $info = $storeOrderModel->where('order_sn',$orderSn)->find();
        if(empty($info)){
            $this->jkReturn('-1','下单判断',[]);
        }
        $this->jkReturn('1','下单成功',$info);
    }

    /*
     * explain:店铺订单列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/23 16:48
     */
    public function orderList(Request $request,StoreOrderModel $storeOrderModel)
    {
        $param = $request->param();
        $where = 'user_id='.$param['user_id'].' and order_type="'.$param['order_type'].'" and (voucher_type=1 or voucher_type is null) ';
        if ($param['order_state']??0){
            $where .= ' and order_state="'.$param['order_state'].'"';
        }else{
            if ($param['order_type'] == 1) {
                $where .= ' and order_state <> "T01"';
            }
        }

        if ($param['comment_state']??0){
            $comment_state = $param['comment_state']-1;
            $where .= ' and comment_state="'.$comment_state.'"';
        }
        $orderList = $storeOrderModel
            ->alias('o')
            ->field('o.*,s.store_name,s.store_img')
            ->where($where)
            ->join('new_store s','s.store_id=o.store_id','left')
            ->order('create_time','desc')
            ->select();
        $this->jkReturn(1,'店铺订单列表',$orderList);
    }
    
    /*
     * explain:店铺订单商品详情
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/23 17:48
     */
    public function orderInfo(Request $request,StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel,StoreService $storeService)
    {
        $param = $request->param();
        $orderInfo = $storeOrderModel
            ->alias('o')
            ->field('o.*,s.store_name,s.store_img,s.store_hot,s.store_address')
            ->where(['order_id'=>$param['order_id']])
            ->join('new_store s','s.store_id=o.store_id','left')
            ->find();
        $storeService->getStoreDistance($orderInfo);
        $voucherList = $userVoucherModel->append(['refund_state','refund_time'])->where(['order_id'=>$param['order_id']])->select();
        $data = [
            'order_info'=>$orderInfo,
            'voucher_list'=>$voucherList
        ];
        $this->jkReturn('1','订单详情',$data);
    }

    /*
     * explain:取消订单
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/11 18:09
     */

    public function cancelOrder(Request $request,StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel,StoreVoucherModel $storeVoucherModel,UserCouponsModel $userCouponsModel)
    {
        $param = $request->param();
        $orderInfo = $storeOrderModel->where("order_id=".$param['order_id']." and user_id=".$param['user_id']." and order_state='T01'")->find();
        if(empty($orderInfo)) $this->jkReturn('-1','该订单不能取消',[]);
        //-- 开启事物
        $storeOrderModel->startTrans();
        //-- 开启redis事务
        $redisModel = new Redis(Config::get('queue'));
        $redis = $redisModel->handler();
        $redis->multi();
        $voucherStockKey = 'voucher'.$orderInfo->voucher_id;
        for ($i=0;$i<$orderInfo->voucher_num;$i++){
            if(!$redis->lpush("$voucherStockKey",1)){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        //-- 编辑订单
        if(!$storeOrderModel->update(['order_state'=>'T05'],['order_id'=>$orderInfo->order_id])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 编辑用户抵用券
        if(!$userVoucherModel->update(['used_state'=>'C04'],['order_id'=>$orderInfo->order_id])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 加库存
        if(!$storeVoucherModel->where('voucher_id',$orderInfo->voucher_id)->setInc('voucher_stock',$orderInfo->voucher_num)){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 返还优惠券
        if($orderInfo->user_coupons_id>0){
            $data = [
                'used_state'=>'C02',
                'used_time'=>null
            ];
            if(!$userCouponsModel->update($data,['user_coupons_id'=>$orderInfo->user_coupons_id])){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
        }
        $storeOrderModel->commit();
        $redis->exec();
        $this->jkReturn('1','取消成功',[]);
    }
    
    /*
     * explain:提交用户订单商品评价
     * params :@user_id @order_id
     * authors:Mr.Geng
     * addTime:2018/4/9 14:42
     */
    public function subComment(Request $request,StoreCommentModel $storeCommentModel,StoreOrderModel $storeOrderModel,StoreProLikeModel $storeProLikeModel,StoreModel $storeModel)
    {
        $param = $request->param();
        if(!empty($param['comment_img'])){
            $param['comment_img'] = urldecode($param['comment_img']);
        }
        $storeCommentModel->startTrans();
        //-- 推荐单品
        if($param['store_pro_list']??0){
            $pro = [];
            foreach ($param['store_pro_list'] as $v){
                $res = $storeProLikeModel->where(['user_id'=>$request->param('user_id'),'store_pro_id'=>$v['store_pro_id']])->find();
                if(empty($res)) {
                    if (!$storeProLikeModel->create(['user_id' => $request->param('user_id'),'store_pro_id'=>$v['store_pro_id']])){
                        $storeOrderModel->rollback();
                        $this->jkReturn('-1', '网络延时,请稍后重试', []);
                    }
                }
                $pro[]= $v['store_pro_name'];
            }
        }
        //-- 插入评论
        $param['store_pro_name'] = empty($pro)? null: implode(',',$pro);
        if (!$storeCommentModel->allowField(true)->create($param))
        {
            $storeCommentModel->rollback();
            $this->jkReturn('-1', '网络延时,请稍后重试', []);
        }
        if (!$storeOrderModel->update(['comment_state'=>'1'],['order_id'=>$param['order_id']])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1', '网络延时,请稍后重试', []);
        }
        //-- 计算平均分
        $com1 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>1])->count();
        $com2 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>2])->count();
        $com3 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>3])->count();
        $com4 = $storeCommentModel->where(['store_id'=>$param['store_id'],'comment_num'=>4])->count();
        $all = $com1+$com2+$com3+$com4;
        $avg = sprintf('%.1f',(1*$com1+2*$com2+3*$com3+4*$com4)/$all);
        if (!$storeModel->update(['comment_num'=>$avg],['store_id'=>$param['store_id']])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1', '网络延时,请稍后重试', []);
        }
        //-- 执行用户评论行为
        $data = [
            'user_id'=>$request->param('user_id'),
            'code'=>'storeComment',
            'store_id'=>$param['store_id'],
            'order_id'=>$param['order_id']
        ];
        $request = Queue::push('app\job\ExecBehavior', serialize($data) , $queue = "ExecBehavior");
        if (!$request){
            $storeOrderModel->rollback();
            return false;
        }
        $storeOrderModel->commit();
        $this->jkReturn('1', '感谢您的评价', []);
    }

    /*
     * explain:编辑用户订单商品评价
     * params :@user_id @order_id
     * authors:Mr.Geng
     * addTime:2018/4/9 14:42
     */
    public function editComment(Request $request,StoreCommentModel $storeCommentModel,StoreOrderModel $storeOrderModel,StoreProLikeModel $storeProLikeModel)
    {
        $param = $request->param();
        if(!empty($param['comment_img'])){
            $param['comment_img'] = urldecode($param['comment_img']);
        }
        $storeCommentModel->startTrans();
        //-- 推荐单品
        if($param['store_pro_list']??0){
            $pro = [];
            foreach ($param['store_pro_list'] as $v){
                $res = $storeProLikeModel->where(['user_id'=>$request->param('user_id'),'store_pro_id'=>$v['store_pro_id']])->find();
                if(empty($res)) {
                    if (!$storeProLikeModel->create(['user_id' => $request->param('user_id'),'store_pro_id'=>$v['store_pro_id']])){
                        $storeOrderModel->rollback();
                        $this->jkReturn('-1', '网络延时,请稍后重试', []);
                    }
                }
                $pro[]= $v['store_pro_name'];
            }
            $param['store_pro_name'] = empty($pro)? null: implode(',',$pro);
        }
        //-- 编辑评论
        if (!$storeCommentModel->allowField(true)->update($param,['store_comment_id'=>$param['store_comment_id']]))
        {
            $storeCommentModel->rollback();
            $this->jkReturn('-1', '网络延时,请稍后重试', []);
        }
        $storeOrderModel->commit();
        $this->jkReturn('1', '编辑成功', []);
    }


    /*
     * explain:申请退款
     * authors:Mr.Geng
     * addTime:2017/11/16 14:16
     */
    public function refundVoucher(Request $request,StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel,RefundReasonModel $refundReasonModel)
    {
        $param = $request->param();
        $orderInfo = $storeOrderModel->where(['order_id' =>$param['order_id'],'user_id'=>$param['user_id']])->find();
        $userVoucher = $userVoucherModel->append('refund_state')->where(['user_voucher_id'=>$param['user_voucher_id'],'user_id'=>$param['user_id']])->find();
        !$userVoucher && $this->jkReturn('-1','该商品不存在',[]);
        if($userVoucher->refund_state??0){
            $this->jkReturn('-1','该商品正在退款中,请联系管理员核实',[]);
        }
        if($userVoucher->used_state != 'C02'){
            $this->jkReturn('-1','只有已付款的商品可以退款,请联系管理员核实',[]);
        }
        $reasonList = $refundReasonModel->select();
        $data = array(
            'order_info'=>$orderInfo,
            'user_voucher'=> $userVoucher,
            'reason'=>$reasonList,
        );
        $this->jkReturn('1','退款确认',$data);
    }

    /*
     * explain:申请退款提交
     * authors:Mr.Geng
     * addTime:2017/11/16 14:34
     */
    public function refundSub(Request $request,UserVoucherModel $userVoucherModel,UserVoucherRefundModel $userVoucherRefundModel)
    {
        $param = $request->param();
        if (empty($param['user_id'])) {
            $this->jkReturn('-1','用户未登录',[]);
        }
        $userVoucher = $userVoucherModel->append('refund_state')->where(['user_voucher_id'=>$param['user_voucher_id'],'user_id'=>$param['user_id']])->find();
        if (empty($userVoucher)) {
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        if($userVoucher->refund_state != '0'){
            $this->jkReturn('-1','该商品正在退款中,请联系管理员核实',[]);
        }
        if($userVoucher->used_state != 'C02'){
            $this->jkReturn('-1','只有已付款的商品可以退款,请联系管理员核实',[]);
        }
        //-- 添加退款记录
        $userVoucherRefundModel->startTrans();
        $orderRefundData = $userVoucher->toArray();
        $orderRefundData['voucher_img'] = serialize(array_splice($orderRefundData['voucher_img'],1));
        $orderRefundData['refund_sn'] = $this->refundSn();
        $orderRefundData['reason_id'] = $param['reason_id'];
        $orderRefundData['refund_desc'] = $param['refund_desc'];
        $orderRefundData['refund_price'] = $userVoucher->buy_price;
        $orderRefundData['refund_img'] = $param['refund_img'];
        $orderRefundData['refund_state'] = 'D01';
        $orderRefundData['refund_time'] = $this->getTime();
        if(!$userVoucherRefundModel->create($orderRefundData)){
            $userVoucherRefundModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试', []);
        }
        $userVoucherRefundModel->commit();
        $this->jkReturn('1', '退货申请成功,请耐心等候客服处理',$orderRefundData['refund_sn']);
    }

    /*
     * explain:确认退款
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/3 14:05
     */
    public function refundPrice(Request $request,UserVoucherRefundModel $userVoucherRefundModel,StoreOrderModel $storeOrderModel,UsersModel $usersModel,UserMoneyLogModel $userMoneyLogModel,UserVoucherModel $userVoucherModel,UserScoreLogModel $userScoreLogModel,StoreModel $storeModel,StoreRebateLogModel $storeRebateLogModel,PayService $payService)
    {
        $userVoucherId = $request->param('user_voucher_id');
        $info = $userVoucherRefundModel->where('user_voucher_id',$userVoucherId)->find();
        $orderInfo = $storeOrderModel
            ->alias('o')
            ->field('o.order_sn,o.pay_type,o.user_give_score,o.voucher_num,o.store_give_score,p.pay_sn,p.total_fee')
            ->where(['o.order_id'=>$info->order_id,'p.pay_state'=>1])
            ->join('new_store_order_pay p','p.order_id=o.order_id','left')
            ->find();
        $storeOrderModel->startTrans();
        switch ($orderInfo->pay_type){
            case 0:
                //-- 余额支付
                //-- 退款列表状态
                $refundState = 'D04';
                if(!$usersModel->where('user_id',$info->user_id)->setInc('user_money',$info->buy_price)){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                //-- 记录用户账户变动日志
                if (!$userMoneyLogModel->create(['money'=>$info->buy_price,'type'=>2,'desc'=>'订单退款:'.$info->voucher_sn,'user_id'=>$info->user_id])){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                break;
            case 1:
                //-- 支付宝支付
                $refundState = 'D04';

                break;
            case 2:
                //-- 微信支付
                $refundState = 'D04';
                $order = [
                    'out_trade_no' => $orderInfo->pay_sn,
                    'out_refund_no' =>$info->refund_sn ,
                    'total_fee' => $orderInfo->total_fee*100,
                    'refund_fee' => $info->buy_price*100,
                    'refund_desc' => '新零售-订单退款:'.$orderInfo->order_sn,
                    'type' => 'app'
                ];
                $config = [
                    'notify_url'=>$this->getConfig('base_url').'/Api/pay/wechatRefundNotify'
                ];
                $payRefund = $payService->setConfig($config)->wechatRefund($order);
                if($payRefund->result_code != 'SUCCESS'){
                    $storeOrderModel->rollback();
                    $this->jkReturn('-1','网络延时,请稍后重试',[]);
                }
                break;
        }
        //-- 改变退款列表状态
        if(!$userVoucherRefundModel->update(['refund_state'=>$refundState],['user_voucher_id'=>$info->user_voucher_id])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 改变用户抵用券状态
        if(!$userVoucherModel->update(['used_state'=>'C05'],['user_voucher_id'=>$info->user_voucher_id])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 订单退款金额
        if(!$storeOrderModel->where(['order_id'=>$info->order_id])->setInc('refund_price',$info->buy_price)){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        //-- 如果用户有赠送积分,扣除用户积分
        if($orderInfo->user_give_score>0){
            $delScore = ceil($orderInfo->user_give_score/($orderInfo->voucher_num-$userVoucherRefundCount+1));
            if($orderInfo->user_give_score-$delScore<0){
                $delScore = $orderInfo->user_give_score;
            }
            //-- 更新订单增送积分
            if(!$storeOrderModel->where(['order_id'=>$info->order_id])->setDec('user_give_score',$delScore)){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 扣除积分并记录日志
            if (!$usersModel->where('user_id',$info->user_id)->setDec('user_score',$delScore)) {
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 记录日志
            if (!$userScoreLogModel->save(['score'=>-$delScore,'desc'=>'订单退款,订单:'.$info->order_sn,'user_id'=>$info->user_id])) {
                $storeOrderModel->rollback();
                $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
            }
        }
        //-- 如果店铺有赠送积分,扣除店铺积分
        if($orderInfo->store_give_score>0){
            $delScore = ceil($orderInfo->store_give_score/($orderInfo->voucher_num-$userVoucherRefundCount+1));
            if($orderInfo->store_give_score-$delScore<0){
                $delScore = $orderInfo->store_give_score;
            }
            //-- 更新订单增送积分
            if(!$storeOrderModel->where(['order_id'=>$info->order_id])->setDec('store_give_score',$delScore)){
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 扣除积分并记录日志
            if (!$storeModel->where('store_id',$info->store_id)->setDec('store_score',$delScore)) {
                $storeOrderModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 记录日志
            if (!$storeRebateLogModel->save(['score'=>-$delScore,'desc'=>'有用户订单退款,订单:'.$info->order_sn,'user_id'=>$info->user_id])) {
                $storeOrderModel->rollback();
                $this->jkReturn(-1,'网络延时,请稍后刷新重试,对您造成的不便敬请谅解1',[]);
            }
        }
        //-- 判断是否改变订单状态
        $orderState = 'T05';
        if($userVoucherModel->where("order_id=$info->order_id and used_state='C05'")->find()){
            $orderState = 'T04';
        }
        if($userVoucherModel->where("order_id=$info->order_id and used_state='C04'")->find()){
            $orderState = 'T04';
        }
        if($userVoucherModel->where("order_id=$info->order_id and used_state='C03'")->find()){
            $orderState = 'T03';
        }
        if($userVoucherModel->where("order_id=$info->order_id and used_state='C02'")->find()){
            $orderState = 'T02';
        }
        if($userVoucherModel->where("order_id=$info->order_id and used_state='C01'")->find()){
            $orderState = 'T01';
        }
        if(!$storeOrderModel->update(['order_state'=>$orderState],['order_id'=>$info->order_id])){
            $storeOrderModel->rollback();
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $storeOrderModel->commit();
        $this->jkReturn('1','退款成功',[]);
    }
    
    /*
     * explain:用户扫码支付确认页
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 17:13
     */
    public function userScanCheck(Request $request,StoreModel $storeModel)
    {
        $param = $request->param();
        //-- 验证二维码合法性
        if(empty($param['code_info'])){
            $this->jkReturn('-1',"网络延时,请刷新重试!");
        }
        $storeId = $this->authCode($param['code_info'],'DECODE','shike');
        $storeInfo = $storeModel
            ->where(["store_id"=>$storeId,'is_close'=>0,'audit_state'=>1])
            ->find();
        empty($storeInfo) && $this->jkReturn('-1','无效的信息,请核对店铺二维码',[]);
        $this->jkReturn('1','扫码支付确认店铺信息',$storeInfo);
    }
    
    /*
     * explain:用户扫码支付
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/7 9:29
     */
    public function userScanPay(Request $request,UserCouponsModel $userCouponsModel,StoreOrderModel $storeOrderModel,UserVoucherModel $userVoucherModel,StoreModel $storeModel,UserService $userService,StoreBrowseLogModel $storeBrowseLogModel)
    {
        $param = $request->param();
        $store_id = $param['store_id'];
        $userService->judgeUser();
        $storeInfo = $storeModel->where('store_id',$store_id)->find();
        empty($storeInfo) && $this->jkReturn('-1',"网络延时,请刷新重试!");
        //-- 创建订单
        $time = $this->getTime();
        $amount = $param['order_price'];
        $orderAmount = $amount-$param['no_price']??0;
        if($param['user_voucher_id']??0){
            $userVoucher = $userVoucherModel
                ->append('refund_state')
                ->where("store_id=".$store_id." and voucher_type=0 and is_pay_used=1 and min_amount<=".$orderAmount." and use_start_date<'".$time."' and use_end_date>'".$time."' and used_state='C02' and user_id=".$param['user_id']." and user_voucher_id=".$param['user_voucher_id'])
                ->find();
            if(empty($userVoucher)){
                $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试1!");
            }
            if($userVoucher->use_method === 0){
                //-- 满减
                if($userVoucher->use_method_info != $param['user_voucher_price']){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试2!");
                }
            }else{
                //-- 满折
                if(sprintf('%.2f',(100-$userVoucher->use_method_info)*$orderAmount/100) != $param['user_voucher_price']){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试3!");
                }
            }
        }
        if($param['user_coupons_id']??0){
            $orderAmount = $orderAmount-$param['user_voucher_price'];
            //-- 判断红包是否可用
            $userCoupons = $userCouponsModel
                ->where("user_id=".$param['user_id']." and used_state='C02' and use_start_date<'".$time."' and use_end_date>'".$time."' and min_amount<=$orderAmount and user_coupons_id=".$param['user_coupons_id'])
                ->order('create_time','desc')
                ->find();
            if(empty($userCoupons)){
                $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试4!");
            }
            if($userCoupons->use_method === 0){
                //-- 满减
                if($userCoupons->use_method_info != $param['coupons_price']){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试6!");
                }
            }else{
                //-- 满折
                if(sprintf('%.2f',((100-$userCoupons->use_method_info)*$orderAmount)/100) != $param['coupons_price']){
                    $this->jkReturn('-1',"使用的优惠券有变动,请刷新重试7!");
                }
            }
        }
        //-- 开启订单事物
        $storeOrderModel->startTrans();
        //-- 订单数据
        $order_sn = $this->getOrderSn();
        $orderData['user_id'] = $param['user_id'];
        $orderData['order_price'] = $amount;
        $orderData['buy_price'] = $amount-$param['coupons_price']-$param['user_voucher_price'];
        $orderData['coupons_price'] = $param['coupons_price'];
        $orderData['user_coupons_id'] = $param['user_coupons_id'];
        $orderData['user_voucher_price'] = $param['user_voucher_price']??0;
        $orderData['user_voucher_id'] = $param['user_voucher_id'];
        $orderData['order_sn'] = $order_sn;
        $orderData['order_type'] = 1;
        $orderData['order_state'] = 'T01';
        $orderData['store_id'] = $store_id;
        //-- 创建订单
        if(!$storeOrderModel->allowField(true)->create($orderData)){
            $storeOrderModel->rollback();
            $this->jkReturn(-1,"系统繁忙,请重试!");
        }
        $orderId = $storeOrderModel->getLastInsID();
        //-- 记录用户访问店铺足迹
        $storeBrowseLogModel->where(['user_id'=>$param['user_id'],'store_id'=>$store_id])->delete();
        $storeBrowseLogModel->allowField(true)->create(['user_id'=>$param['user_id'],'store_id'=>$store_id]);
        $storeOrderModel->commit();
        $this->jkReturn(1,"下单成功!",$orderId);
    }
    
    /*
     * explain:读取店铺预约配置
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/11 10:52
     */
    public function storeReserveConfig(Request $request,StoreReserveConfigModel $storeReserveConfigModel)
    {
        $storeId = $request->param('store_id');
        $info = $storeReserveConfigModel->where('store_id',$storeId)->find();
        empty($info) && $this->jkReturn('-1','网络延时,请稍后重试',[]);
        $info->morning_time = $this->splitTime($info->morning_start_time,$info->morning_end_time,30*60,'H:i');
        $info->afternoon_time = $this->splitTime($info->afternoon_start_time,$info->afternoon_end_time,30*60,'H:i');
        $this->jkReturn('1','店铺预约配置',$info);
    }
    
    /*
     * explain:用户预约服务
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 15:37
     */
    public function userReserve(Request $request,StoreReserveModel $storeReserveModel)
    {
        $param = $request->param();
        if(!$storeReserveModel->create($param)){
            $this->jkReturn('-1','网络延时,请稍后重试',[]);
        }
        $this->jkReturn('1','预订成功',[]);
    }

    /*
     * explain:预约列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/14 15:20
     */
    public function userReserveLit(Request $request,StoreReserveModel $storeReserveModel)
    {
        $param = $request->param();
        $time = $this->getTime();
        $where = "r.user_id={$param['user_id']} ";
        if($param['store_id']??0){
            $where .= " and r.store_id={$param['store_id']}";
        }
        if($param['reserve_time']??0){
            $where .= " and reserve_time>'".$time."' ";
        }
        $list = $storeReserveModel
            ->alias('r')
            ->field('r.*,s.store_name,s.store_address')
            ->where($where)
            ->join('new_store s','s.store_id=r.store_id','left')
            ->order('r.create_time','desc')
            ->select();
        foreach ($list as $v){

        }
        $this->jkReturn('1','预订列表',$list);
    }

    /*
     * explain:用户取消预约原因列表
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 17:41
     */
    public function userCancelReserve(StoreReserveReasonModel $storeReserveReasonModel)
    {
        $list = $storeReserveReasonModel->where('disabled',1)->select();
        $this->jkReturn('1','预订取消原因列表',$list);
    }

    /*
     * explain:用户取消预约
     * params :
     * authors:Mr.Geng
     * addTime:2018/5/10 17:41
     */
    public function reserveCancel(Request $request,StoreReserveModel $storeReserveModel)
    {
        $param = $request->param();
        if(!$storeReserveModel->update(['reason'=>$param['reason'],'reserve_state'=>'R04'],['id'=>$param['id']])){
            $this->jkReturn('-1','预订取消失败',[]);
        }
        $this->jkReturn('1','预订取消成功',[]);
    }
    public function storeShare(Request $request,StoreModel $storeModel,StoreService $storeService,StoreCommentModel $storeCommentModel){
        $storeId = $request->param("store_id");
        if(empty($storeId)){
            $this->jkReturn(-1,"参数错误",[]);
        }
        //-- append 为追加属性 用于判断用户是否收藏
        $storeInfo = $storeModel
            ->where(["store_id"=>$storeId])
            ->find();
        !$storeInfo && $this->jkReturn(-1,"参数错误",[]);
        //-- 计算距离
        $storeService->getStoreDistance($storeInfo);
        //-- 转义店铺详情
        $storeInfo->store_info = htmlspecialchars_decode(htmlspecialchars_decode($storeInfo->store_info));
        $store_img = $storeInfo->store_img[0] . $storeInfo->store_img[1] . $storeInfo->store_img[2] . $storeInfo->store_img[3];

        //--------商品评价数量统计
        $com1 = $storeCommentModel->where(['store_id'=>$storeId,'comment_num'=>1])->count();
        $com2 = $storeCommentModel->where(['store_id'=>$storeId,'comment_num'=>2])->count();
        $com3 = $storeCommentModel->where(['store_id'=>$storeId,'comment_num'=>3])->count();
        $com4 = $storeCommentModel->where(['store_id'=>$storeId,'comment_num'=>4])->count();
        $all = $com1+$com2+$com3+$com4;
        $com1_ = $all==0? 0: ($com1/$all)*100;
        $com2_ = $all==0? 0: ($com2/$all)*100;
        $com3_ = $all==0? 0: ($com3/$all)*100;
        $com4_ = $all==0? 0: ($com4/$all)*100;
        $where = ["store_id"=>$storeId];
        $where['has_img'] = 1;
        $store_comment_list = $storeCommentModel
            ->append('is_collect')
            ->alias('c')
            ->field('c.*,u.user_name,u.head_img')
            ->where($where)
            ->join('new_users u','u.user_id=c.user_id','left')
            ->limit(3)
            ->page(1)
            ->order('create_time','asc')
            ->select();

        /*if (!empty($store_comment_list)) {
            foreach ($store_comment_list as $value => $comment_list) {
                $store_comment_list[$value]['headImg'] = $comment_list->head_img[0] . $comment_list->head_img[1] . $comment_list->head_img[2] . $comment_list->head_img[3];
                if (!empty($store_comment_list[$value]->comment_img)) {
                    foreach ($store_comment_list[$value]->comment_img as $key => $comment_img) {
                        $store_comment_list[$value]['commentImg'][$key] = $comment_img[0] . $comment_img[1] . $comment_img[2] . $comment_img[3];
                    }
                }
            }
        }*/
        $comment_info = array(
            array('超赞',$com4_,$com4),
            array('满意',$com3_,$com3),
            array('一般',$com2_,$com2),
            array('差评',$com1_,$com1)
        );
        $satisfaction = $all==0? '0%': sprintf('%.0f',(($com3+$com4)/$all*100)).'%';

        $this->assign("comment_list",$store_comment_list);
        $this->assign("comment_info",$comment_info);
        $this->assign("satisfaction",$satisfaction);
        $this->assign("store_img",$store_img);
        $this->assign("storeInfo",$storeInfo);
        return view("Store/store_share");
    }
}