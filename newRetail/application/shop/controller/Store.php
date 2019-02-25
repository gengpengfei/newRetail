<?php
namespace app\shop\controller;
use app\api\service\ClientService;
use app\api\service\RewardService;
use app\shop\model\ActivityApplyModel;
use app\shop\model\AgreementRuleModel;
use app\shop\model\StoreAuditModel;
use app\shop\model\StoreConfigDefaultModel;
use app\shop\model\StoreConfigModel;
use app\shop\model\StoreProModel;
use app\shop\model\StoreModel;
use app\shop\model\StorePushMessageModel;
use app\shop\model\StoreReserveConfigModel;
use app\shop\model\StoreReserveModel;
use app\shop\model\UserMoneyLogModel;
use app\shop\model\UserScoreLogModel;
use geohash\Geohash;
use think\Config;
use think\Request;
use app\shop\model\CategoryModel;
use app\shop\service\UploadService;
use app\shop\model\StoreVoucherModel;
use app\shop\model\UserVoucherModel;
use app\shop\model\UsersModel;
use app\shop\model\StoreAttrRuleModel;
use app\shop\model\StoreVoucherAttrModel;
use app\shop\model\NavModel;
use app\shop\model\StoreReportModel;
use app\shop\model\RegionModel;
use think\Session;
use app\shop\model\StoreCommentModel;
use think\cache\driver\Redis;
use app\shop\model\StoreClearRuleModel;
use app\shop\model\StoreClearModel;
use app\shop\model\StoreOrderModel;
use app\shop\model\UserVoucherRefundModel;
use app\shop\model\RefundReasonModel;
use think\Db;

/**
 * Created by PhpStorm.
 * User: jlcr
 * Date: 2018/4/4
 * Time: 15:31
 */
class Store extends Common
{
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;
    /*
     * 在售单品模块
     * */
//    推荐单品列表
    public function productlist(StoreProModel $storeproModel,Request $request){
        $profield=array('store_pro_id','store_pro_name','store_pro_img','start_time','end_time','store_pro_like','store_name','a.store_id');
        // 获取推荐单品
        $storeInfo = $request->param();
        //获取分页数
        if (!empty($storeInfo['show_count'])){
            $show_count = $storeInfo['show_count'];
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($storeInfo['proshow'])){
            $proshownow=$storeInfo['proshow']-1;
            $where .= " and is_show = '" . $proshownow . "'";
        }
        if(!empty($storeInfo['datemin'])){
            $where .= " and end_time > '" . $storeInfo['datemin'] . "'";
        }
        if(!empty($storeInfo['datemax'])){
            $where .= " and start_time < '" . $storeInfo['datemax'] . "'";
        }
        if(!empty($storeInfo['datemin'])&&!empty($storeInfo['datemax'])&&$storeInfo['datemin']>$storeInfo['datemax']){
            $this->error("请正确选择时间");
        }
        if(!empty($storeInfo['keywords'])){
            $keywords = $storeInfo['keywords'];
            $where .= " and (a.store_pro_name like '%" . $keywords . "%' or w.store_name like '%" . $keywords . "%')";
        }
        $where .= " and a.store_id = " . Session::get('shop_id');
        //$userLogModel->admin_user_id = Session::get('shop_user_id');
        //排序条件
        if(!empty($storeInfo['orderBy'])){
            $orderBy = $storeInfo['orderBy'];
        }else{
            $orderBy = 'store_pro_id';
        }
        if(!empty($storeInfo['orderByUpOrDown'])){
            $orderByUpOrDown = $storeInfo['orderByUpOrDown'];
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $pro_list=$storeproModel
            ->alias('a')
            ->join('new_store w','a.store_id = w.store_id','LEFT')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->field($profield)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$pro_list->appends($parmas)->render();
        $this->assign('prolist',$pro_list);
        $this->assign('page',$page);
        $this->assign('where', $storeInfo);
        $this->assign('pronum',$pro_list->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/productlist");
    }

//    添加单品
    public function productadd(StoreProModel $storeproModel,StoreModel $storeModel,CategoryModel $categoryModel,Request $request,UploadService $uploadservice){
        $storeproModel->data($request->param());
        //如果是提交
        if(!empty($storeproModel->is_ajax)){
            //店铺所属城市,分类id
            if(!empty($storeproModel->store_id)){
                $store_id=$storeproModel->store_id;
                $city=$storeModel->field(['city','category_id'])->where('store_id ='.$store_id)->find();
                $storeproModel->city=$city->city;
            }
            //商品关键字
            if(!empty($storeproModel->keywords)){
                $keywordarr=$storeproModel->keywords;
                $storeproModel->store_pro_keywords=implode(',',$keywordarr);
            }
            if(!$storeproModel->allowField(true)->save($storeproModel)){
                $this->error("添加失败",'/shop/Store/productlist');
            }
            $add_pro_id=$storeproModel->getLastInsID();
            $this->setAdminUserLog("新增","添加的在售单品:id为$add_pro_id","new_store_pro",$add_pro_id);
            //获取图片
            $file=request()->file('store_pro_img');
            if (!empty($file)){
                $store_category=$city->category_id;
                $store_arr=explode(',',$store_category);
                $store_category_id=$store_arr[count($store_arr)-1];
                $imgUrl='/images/goods/'.$store_category_id.'/'.$store_id.'/detail/'.$add_pro_id.'/';
                $imgname=$this->imgName();
                $result=$uploadservice->upload($file,$imgUrl,$imgname);
                $storeobj=$storeproModel->where('store_pro_id='.$add_pro_id)->find();
                $storeobj->store_pro_img=$result;
                if($storeobj->save()){
                    $this->success("添加成功",'/shop/Store/productlist');
                }else{
                    $this->error("图片上传失败,产品添加成功",'/shop/Store/productlist');
                }

            }else{
                $this->success("添加成功",'/shop/Store/productlist');
            }
        }else{
            //获取店铺列表
            $storelist=$storeModel
                ->field(['store_id','store_name'])
                ->where(' audit_state = 1 and disabled=1 and store_id = ' . Session::get('shop_id'))
                ->find();
            if(!empty($storelist)){
                $storelist = $storelist->toArray();
            }
            //获取单品分类
            $this->assign('storelist', $storelist);
            // 模板输出
            return view("Store/productadd");
        }
    }

//    编辑单品
    public function productedit(StoreProModel $storeproModel,StoreModel $storeModel,CategoryModel $categoryModel,Request $request,UploadService $uploadservice){
        $storeproModel->data($request->param());
        if(empty($storeproModel->store_pro_id)){
            $this->error("单品id无效",'/shop/Store/productlist');
        }
        $store_pro_id=$storeproModel->store_pro_id;
        $store_pro_info = $storeproModel->where(["store_pro_id"=>$store_pro_id])->find();
        if(empty($store_pro_info)){
            $this->error("该产品不存在",'/shop/Store/productlist');
        }
        //如果是提交
        if(!empty($storeproModel->is_ajax)){
            $upWhere['store_pro_id'] = $store_pro_id;
            //店铺所属城市,分类id
            if(!empty($storeproModel->store_id)){
                $store_id=$storeproModel->store_id;
                $city=$storeModel->field(['city','category_id'])->where('store_id ='.$store_id)->find();
                $storeproModel->city=$city->city;
            }
            if(!$storeproModel->allowField(true)->save($storeproModel,$upWhere)){
                $this->error("添加失败",'/shop/Store/productlist');
            }
            $this->setAdminUserLog("编辑","修改的在售单品:id为$store_pro_id","new_store_pro",$store_pro_id);
            //获取图片
            $file=request()->file('store_pro_img');
            if (!empty($file)){
                $oldimg=$store_pro_info->store_pro_img;
                $store_category=$city->category_id;
                $store_arr=explode(',',$store_category);
                $store_category_id=$store_arr[count($store_arr)-1];
                $imgUrl='/images/goods/'.$store_category_id.'/'.$store_id.'/detail/'.$store_pro_id.'/';
                $imgname=$this->imgName();
                $result=$uploadservice->upload($file,$imgUrl,$imgname);
                $storeobj=$storeproModel->where('store_pro_id='.$store_pro_id)->find();
                $storeobj->store_pro_img=$result;
                if($storeobj->save()){
                    $uploadservice->delimage($oldimg);
                    $this->success("修改成功",'/shop/Store/productlist');
                }else{
                    $this->error("图片上传失败,产品信息修改成功",'/shop/Store/productlist');
                }

            }else{
                $this->success("修改成功",'/shop/Store/productlist');
            }

        }else{
//            //获取商品信息
            $field=array('store_pro_id','store_id','store_pro_name','store_pro_img','store_pro_price','store_pro_like','is_show','start_time','end_time');
            $storeproinfo=$storeproModel->field($field)->where('store_pro_id='.$store_pro_id)->find();
            //获取店铺列表
            $storelist=$storeModel
                ->field(['store_id','store_name'])
                ->where(' audit_state = 1 and disabled=1 and store_id = ' . Session::get('shop_id'))
                ->find();
            if(!empty($storelist)){
                $storelist = $storelist->toArray();
            }
            //获取单品分类
            $this->assign('storelist', $storelist);
            $this->assign('storeproinfo', $storeproinfo);
//            // 模板输出
            return view("Store/productedit");
        }
    }

//    删除单品
    public function productdel(Request $request,StoreProModel $storeproModel){
        $storeInfo = $request->param();
        if(empty($storeInfo['pro_id'])){
            $this->error("单品id不能为空");
        }
        $pro_id = $storeInfo['pro_id'];
        if(is_array($pro_id)){
            //多个删除
            foreach ($pro_id as $v){
                $store_pro_info = $storeproModel->where(["store_pro_id"=>$v])->find();
                if(!$store_pro_info->delete()){
                    $this->error("删除失败");
                }
                $this->setAdminUserLog("删除","删除在售单品:id为$v","new_store_pro",$v);
            }
            $this->success("删除成功");
        }
        //单个删除
        $store_pro_info = $storeproModel->where(["store_pro_id"=>$pro_id])->find();
        if($store_pro_info->delete()){
            $this->setAdminUserLog("删除","删除在售单品:id为$pro_id","new_store_pro",$pro_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }

    /*
     * 商铺优惠券模块
     * */
    //商铺优惠券列表
    public function voucherlist(StoreVoucherModel $storevouchermodel,StoreModel $storeModel,UserVoucherModel $usermouchermodel,Request $request){
        $voucherfield=array('voucher_id','store_name','voucher_name','voucher_price','voucher_type','voucher_amount','voucher_stock','use_start_date','use_end_date','voucher_desc','a.disabled as is_disabled','a.store_id');
        // 获取推荐单品
        $storevouchermodel->data($request->param());
        //获取分页数
        if (!empty($storevouchermodel->show_count)){
            $show_count = $storevouchermodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($storevouchermodel->issale)){
            $vouchertype=$storevouchermodel->issale-1;
            $where .= " and voucher_type = '" . $vouchertype . "'";
        }
        if(!empty($storevouchermodel->datemin)){
            $where .= " and use_end_date > '" . $storevouchermodel->datemin . "'";
        }
        if(!empty($storevouchermodel->datemax)){
            $where .= " and use_start_date < '" . $storevouchermodel->datemax . "'";
        }
        if(!empty($storevouchermodel->datemin)&&!empty($storevouchermodel->datemax)&&$storevouchermodel->datemin>$storevouchermodel->datemax){
            $this->error("请正确选择时间");
        }
        if(!empty($storevouchermodel->keywords)){
            $keywords = $storevouchermodel->keywords;
            $where .= " and (voucher_name like '%" . $keywords . "%' or store_name like '%" . $keywords . "%')";
        }
        $where .= " and a.store_id = " . Session::get('shop_id');
        //排序条件
        if(!empty($storevouchermodel->orderBy)){
            $orderBy = $storevouchermodel->orderBy;
        }else{
            $orderBy = 'voucher_id';
        }
        if(!empty($storevouchermodel->orderByUpOrDown)){
            $orderByUpOrDown = $storevouchermodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $storesql=$storeModel->buildSql();
        $voucher_list=$storevouchermodel
            ->alias('a')
            ->join([$storesql=> 'w'],'a.store_id = w.store_id','LEFT')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->field($voucherfield)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$voucher_list->appends($parmas)->render();
        //获取券总量
        $voucher_all_num=$storevouchermodel
            ->alias('a')
            ->join('new_store_audit s','a.store_id = s.store_id','LEFT')
            ->where('s.admin_id = ' . Session::get('shop_user_id'))
            ->sum('voucher_stock');
        //print $voucher_all_num;die;
        //获取券的状态量
        $voucher_unused_num=$usermouchermodel->getStateNum('C02',Session::get('shop_user_id')); //未使用
        $voucher_used_num=$usermouchermodel->getStateNum('C03',Session::get('shop_user_id'));   //已使用
        $voucher_expired_num=$usermouchermodel->getStateNum('C04',Session::get('shop_user_id')); //已失效
        $voucher_activated_num=$usermouchermodel
            ->alias('a')
            ->join('new_store_audit s','a.store_id = s.store_id','LEFT')
            ->where("a.used_state!='C01' and s.admin_id = ". Session::get('shop_user_id'))->count(); //已激活
        $this->assign('voucherlist',$voucher_list);
        $this->assign('page',$page);
        $this->assign('where', $storevouchermodel->toArray());
        $this->assign('pronum',$voucher_list->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('voucher_all_num', $voucher_all_num);
        $this->assign('voucher_unused_num', $voucher_unused_num);
        $this->assign('voucher_used_num', $voucher_used_num);
        $this->assign('voucher_expired_num', $voucher_expired_num);
        $this->assign('voucher_activated_num', $voucher_activated_num);
        // 模板输出
        return view("Store/voucherlist");
    }

    //新增商铺优惠券
    public function voucheradd(StoreVoucherModel $storevouchermodel,StoreModel $storeModel,Request $request,UploadService $uploadService,StoreVoucherAttrModel $storeVoucherAttrModel,StoreAttrRuleModel $storeAttrRuleModel){
        $storevouchermodel->data($request->param());
        $store_id = session('shop_id');
        //-- 添加优惠券属性
        $voucher_attr_rule = $storeAttrRuleModel->select();
        //如果是提交
        if(!empty($storevouchermodel->is_ajax)){
            //店铺所属城市,分类id
            if(!empty($store_id)){
                $city=$storeModel->field(['city','category_id'])->where('store_id ='.$store_id)->find();
                $storevouchermodel->city=$city->city;
            }
            $storevouchermodel->voucher_info = $storevouchermodel->editorValue;
            if(!$storevouchermodel->allowField(true)->save($storevouchermodel)){
                $this->error("添加失败",'/shop/Store/productlist');
            }
            $add_voucher_id=$storevouchermodel->getLastInsID();
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/store/voucher/';
                $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                if($newImgName){
                    $newImgName = str_replace("./","$baseUrl/",$newImgName);
                    //替换content
                    $storevouchermodel->voucher_info = str_replace($item,$newImgName,$storevouchermodel->voucher_info);
                    //删除原图
                    unlink($image);
                }
            }
            $upWhere['voucher_id'] = $add_voucher_id;
            $storevouchermodel->allowField(true)->save($storevouchermodel,$upWhere);
            $this->setAdminUserLog("新增","添加店铺优惠券:id为$add_voucher_id","new_store_voucher",$add_voucher_id);

            //-- 存储优惠券属性
            $attr_rule_id = $storevouchermodel->attr_rule_id;
            $attr_rule_value = $storevouchermodel->attr_value;
            foreach($attr_rule_id as $key=>$v){
                if(!empty($attr_rule_value[$key])){
                    $storeVoucherAttrModel->create(['store_id'=>$store_id,'voucher_id'=>$add_voucher_id,'attr_rule_id'=>$v,'attr_value'=>$attr_rule_value[$key]]);
                }
            }
            //成功存redis

            $redis = new Redis(Config::get('queue'));
            $redis = $redis->handler();
            $voucherStockKey='voucher'.$add_voucher_id;
            $resetRedis = $redis->llen("{$voucherStockKey}");
            if(!$resetRedis){
                for ($i = 0; $i < $storevouchermodel->voucher_stock; $i ++) {
                    $redis->lpush("{$voucherStockKey}", 1);
                }
            }
            $store_arr=explode(',',$city->category_id);
            $store_category_id=$store_arr[count($store_arr)-1];
            //获取图片
            $file=request()->file('voucher_img');
            if (!empty($file)){
                $imgUrl='/images/voucher/'.$store_category_id.'/'.$store_id.'/detail/'.$storevouchermodel->voucher_type.'/'.$add_voucher_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$storevouchermodel->where('voucher_id='.$add_voucher_id)->find();
                $storeobj->voucher_img=$result;
                if(!$storeobj->save()){
                    $this->error("图片上传失败,优惠券添加成功",'/shop/Store/voucherlist');
                }
            }
            //获取轮播图
            $i = 0;
            $info = array();
            $myFile = $_FILES['myFile'];
            if (!empty($myFile) && !empty($myFile['name'][0])) {
                if (is_string($myFile['name'])) { //单文件上传
                    $info[$i] = $myFile;
                    $i++;
                } else { // 多文件上传
                    foreach ($myFile['name'] as $key => $val) {//2维数组转换成1维数组
                        //取出一维数组的值，然后形成另一个数组
                        //新的数组的结构为：info=>i=>('name','size'.....)
                        $info[$i]['name'] = $myFile['name'][$key];
                        $info[$i]['type'] = $myFile['type'][$key];
                        $info[$i]['tmp_name'] = $myFile['tmp_name'][$key];
                        $info[$i]['error'] = $myFile['error'][$key];
                        $info[$i]['size'] = $myFile['size'][$key];
                        $i++;
                    }
                }
                if ($info) {
                    $imgUrl='/images/voucher/'.$store_category_id.'/'.$store_id.'/detail/'.$storevouchermodel->voucher_type.'/'.$add_voucher_id.'/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $imgWhere['voucher_id'] = $add_voucher_id;
                    $result = $storevouchermodel->update(['voucher_banner_img'=>$result], $imgWhere);
                    if (!$result) {
                        $this->error("多图片上传失败,店铺添加成功", '/admin/Store/storelist');
                    }
                }
            }
            $this->success("添加成功",'/admin/Store/voucherlist');

        }else{
            //获取店铺列表
            $storelist=$storeModel
                ->field(['store_id','store_name'])
                ->where('audit_state = 1 and disabled=1 and store_id = ' . Session::get('shop_id'))
                ->find();
            if(!empty($storelist)){
                $storelist = $storelist->toArray();
            }
            //清除上传图片session
            Session::delete('uploadimg');
            $this->assign('voucherAttrRule', $voucher_attr_rule);
            $this->assign('storelist', $storelist);
            // 模板输出
            return view("Store/voucheradd");
        }
    }

    //修改商铺优惠券
    public function voucheredit(StoreVoucherModel $storevouchermodel,StoreModel $storeModel,Request $request,UploadService $uploadService,StoreAttrRuleModel $storeAttrRuleModel,StoreVoucherAttrModel $storeVoucherAttrModel){
        $storeVoucher = $request->param();
        $voucher_id = $storeVoucher['voucher_id'];
        if(empty($voucher_id)){
            $this->error("优惠券id无效",'/shop/Store/voucherlist');
        }
        $store_id=session('shop_id');
        $voucher_info = $storevouchermodel->where(["voucher_id"=>$voucher_id])->find();
        $old_stock_num = $storeVoucher['voucher_stock']; //原券库存
        if(empty($voucher_info)){
            $this->error("该优惠券不存在",'/shop/Store/voucherlist');
        }
        $voucher_info['voucher_info'] = htmlspecialchars_decode($voucher_info['voucher_info']);
        $voucher_attr_rule = $storeAttrRuleModel
            ->alias('s')
            ->field('s.attr_rule_id,s.attr_name,r.attr_value')
            ->join('new_store_voucher_attr r','r.attr_rule_id=s.attr_rule_id and r.voucher_id='.$voucher_id,'left')
            ->select();
        //如果是提交
        if(!empty($storeVoucher['is_ajax'])){
            $upWhere['voucher_id'] = $voucher_id;
            //店铺所属城市,分类id
            if(!empty($store_id)){
                $city=$storeModel->field(['city','category_id'])->where('store_id ='.$store_id)->find();
                $storeVoucher['city']= $city->city;
            }
            $storeVoucher['voucher_info'] = $storeVoucher['editorValue'];
            if(!$storevouchermodel->update($storeVoucher,$upWhere)){
                $this->error("修改失败",'/shop/Store/voucherlist');
            }

            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/store/voucher/';
                $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                if($newImgName){
                    $newImgName = str_replace("./","$baseUrl/",$newImgName);
                    //替换content
                    $storeVoucher['voucher_info'] = str_replace($item,$newImgName,$storeVoucher['voucher_info']);
                    //删除原图
                    unlink($image);
                }
            }
            if(!$storevouchermodel->update($storeVoucher,$upWhere)){
                $this->error("修改失败",'/shop/Store/voucherlist');
            }

            //-- 修改优惠券属性
            $attr_rule_id = $storeVoucher['attr_rule_id'];
            $attr_rule_value = $storeVoucher['attr_value'];
            //清空优惠券属性
            $storeVoucherAttrModel->where('voucher_id',$voucher_id)->delete();
            foreach($attr_rule_id as $key=>$v){
                if(!empty($attr_rule_value[$key])){
                    $storeVoucherAttrModel->create(['store_id'=>$store_id,'voucher_id'=>$voucher_id,'attr_rule_id'=>$v,'attr_value'=>$attr_rule_value[$key]]);
                }
            }
            $this->setAdminUserLog("编辑","修改店铺优惠券:id为$voucher_id","new_store_voucher",$voucher_id);
            //修改redis
            $redis = new Redis(Config::get('queue'));
            $redis = $redis->handler();
            $voucherStockKey='voucher'.$voucher_id;
            if (!empty($storeVoucher['voucher_stock'])){
                $now_stock_num=(int)$storeVoucher['voucher_stock'];
                $resetRedis = $redis->llen("{$voucherStockKey}");
                if($resetRedis!=0){
                    if($now_stock_num>$old_stock_num){
                        for ($i = 0; $i < $now_stock_num-$old_stock_num; $i ++) {
                            $redis->lpush("{$voucherStockKey}", 1);
                        }
                    }else{
                        for ($i = 0; $i < $old_stock_num-$now_stock_num; $i ++) {
                            $redis->lpop("{$voucherStockKey}");
                        }
                    }
                }
            }
            $store_arr=explode(',',$city->category_id);
            $store_category_id=$store_arr[count($store_arr)-1];
            //获取图片
            $file=request()->file('voucher_img');
            if (!empty($file)){
                $oldimg=$voucher_info->voucher_img;
                $imgUrl='/images/voucher/'.$store_category_id.'/'.$store_id.'/detail/'.$storeVoucher['voucher_type'].'/'.$voucher_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$storevouchermodel->where('voucher_id='.$voucher_id)->find();
                $storeobj->voucher_img=$result;
                if(!$storeobj->save()){
                    $this->error("图片上传失败,优惠券修改成功",'/shop/Store/voucherlist');
                }
                $uploadService->delimage($oldimg);
            }
            //获取轮播图
            $i = 0;
            $info = array();
            $myFile = $_FILES['myFile'];
            if (!empty($myFile['name']) && !empty($myFile['name'][0])) {
                if (is_string($myFile['name'])) { //单文件上传
                    $info[$i] = $myFile;
                    $i++;
                } else { // 多文件上传
                    if (!empty($myFile['name'][0])) {
                        foreach ($myFile['name'] as $key => $val) {//2维数组转换成1维数组
                            //取出一维数组的值，然后形成另一个数组
                            //新的数组的结构为：info=>i=>('name','size'.....)
                            $info[$i]['name'] = $myFile['name'][$key];
                            $info[$i]['type'] = $myFile['type'][$key];
                            $info[$i]['tmp_name'] = $myFile['tmp_name'][$key];
                            $info[$i]['error'] = $myFile['error'][$key];
                            $info[$i]['size'] = $myFile['size'][$key];
                            $i++;
                        }
                    }
                }
                if ($info) {
                    $imgUrl='/images/voucher/'.$store_category_id.'/'.$store_id.'/detail/'.$storeVoucher['voucher_type'].'/'.$voucher_id.'/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $imgWhere['voucher_id'] = $voucher_id;
                    $result = $storevouchermodel->update(['voucher_banner_img'=>$result], $imgWhere);
                    if (!$result) {
                        $this->error("多图片上传失败,编辑成功", '/admin/Store/storelist');
                    }
                    //-- 删除老图片
                    $oldimgArr=$voucher_info->voucher_banner_img;
                    foreach ($oldimgArr as $v){
                        $uploadService->delimage($v);
                    }
                }
            }
            $this->success("修改成功",'/shop/Store/voucherlist');

        }else{
            //获取店铺列表
            $storelist=$storeModel
                ->field(['store_id','store_name'])
                ->where('audit_state = 1 and disabled=1 and store_id = ' . Session::get('shop_id'))
                ->find();
            if(!empty($storelist)){
                $storelist = $storelist->toArray();
            }
            //清除上传图片session
            Session::delete('uploadimg');
            $this->assign('bannerImage',$voucher_info->voucher_banner_img);
            $this->assign('voucherinfo', $voucher_info);
            $this->assign('voucherAttrRule',$voucher_attr_rule);
            $this->assign('storelist', $storelist);
            // 模板输出
            return view("Store/voucheredit");
        }
    }

    //优惠券信息(用户领取信息)
    public function voucherinfo(StoreVoucherModel $storevouchermodel,UsersModel $usermodel,UserVoucherModel $usermouchermodel,Request $request){
        $storevouchermodel->data($request->param());
        //获取分页数
        if (!empty($storevouchermodel->show_count)){
            $show_count = $storevouchermodel->show_count;
        }else{
            $show_count = 10;
        }
        $voucher_id=$storevouchermodel->voucher_id;
        $where='a.voucher_id = '.$voucher_id;
        $storevouchersql=$storevouchermodel->buildSql();
        $usersql=$usermodel->buildSql();
        $uservoucherlist=$usermouchermodel->alias('a')->join([$storevouchersql=> 'w'],'a.store_id = w.store_id and a.voucher_id=w.voucher_id')->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')->where($where)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$uservoucherlist->appends($parmas)->render();
        $this->assign('uservoucherlist',$uservoucherlist);
        $this->assign('page',$page);
        $this->assign('show_count', $show_count);
        $this->assign('voucher_id', $voucher_id);
        // 模板输出
        return view("Store/voucherinfo");
    }

    //优惠券规则
    public function voucherrule(StoreAttrRuleModel $storeattrruleModel,StoreVoucherAttrModel $storevoucherattrmodel,Request $request){
        $storevoucherattrmodel->data($request->param());
        $voucher_id=$storevoucherattrmodel->voucher_id;
        $store_id=$storevoucherattrmodel->store_id;
        if(!empty($voucher_id)&&!empty($store_id)){
            $where='store_id = '.$store_id;
            $where .=' and voucher_id = '.$voucher_id;
        }else{
            $this->error('该优惠券信息有误');
        }
        $storevoucherattrinfo=$storevoucherattrmodel->where($where)->find();
        if(!empty($storevoucherattrmodel->is_ajax)){
            $upWhere=array(
                'voucher_id'=>$voucher_id,
                'store_id'=>$store_id
            );
            if (empty($storevoucherattrinfo)){
                $storevoucherattrmodel->allowField(true)->save($storevoucherattrmodel);
                $this->setAdminUserLog("新增","添加店铺优惠券规则:id为$voucher_id","new_store_voucher_attr",$voucher_id);
            }else{
                $storevoucherattrmodel->allowField(true)->save($storevoucherattrmodel,$upWhere);
                $this->setAdminUserLog("编辑","修改店铺优惠券规则:id为$voucher_id","new_store_voucher_attr",$voucher_id);
            }
            $this->success('优惠券规则修改成功','/shop/Store/voucherlist');
        }else{
            //获取优惠券规则
            $voucher_rule_list=$storeattrruleModel->select();
            $this->assign('voucher_rule_list',$voucher_rule_list);
            $this->assign('voucher_id',$voucher_id);
            $this->assign('store_id',$store_id);
            $this->assign('voucherattrinfo',$storevoucherattrinfo);
            // 模板输出
            return view("Store/voucherrule");
        }
    }

    /*
     * 店铺模块
     * */
     //修改店铺
    public function storeedit(StoreModel $storeModel,CategoryModel $categorymodel,NavModel $navmodel,RegionModel $regionmodel,UploadService $uploadService,Request $request,Geohash $geohash,StoreAuditModel $storeAuditModel){
        $storeModel->data($request->param());
        $store_id = Session::get('shop_id');

        $storeInfo=$storeModel->where('store_id='.$store_id)->find();
        if (empty($storeInfo)) {
            $this->error("没有此店铺");
        }

        //如果是提交
        if(!empty($storeModel->is_ajax)){
            $linkurl='/shop/Store/storeedit';
            $upWhere['store_id'] = $store_id;
            if (!empty($storeModel->category_l1)){
                //获取等级分类
                $cagegorystr='';
                $cagegorystr.=$storeModel->category_l1.',';
            }
            if (!empty($storeModel->category_l2)){
                $cagegorystr.=$storeModel->category_l2.',';
            }
            if (!empty($storeModel->category_l3)){
                $cagegorystr.=$storeModel->category_l3.',';
            }
            if (isset($cagegorystr)){
                $cagegorystr=substr($cagegorystr,0,-1);
                $storeModel->category_id=$cagegorystr;
            }
            //获取经纬度
            if (!empty($storeModel->latlng)){
                $latlng=$storeModel->latlng;
                $latlngarr=explode(',',$latlng);
                $storeModel->lat=$latlngarr[0];
                $storeModel->lng=$latlngarr[1];
                $geohashstr=$geohash->encode($latlngarr[0],$latlngarr[1]);
                $storeModel->geohash=$geohashstr;
            }
            $storeModel->admin_id=Session::get('shop_user_id');

            $storeModel->store_info = $storeModel->editorValue;
            $imgData = Session::get("uploadimg");
            foreach ($imgData as $item) {
                //移动原图片
                $image = '.' . $item;
                $ImgName = rand(100, 999) . time();
                $imgUrl = './images/store/' . $store_id . '/';
                $newImgName = $uploadService->uploadImg($image, $imgUrl, $ImgName);
                if ($newImgName) {
                    $newImgName = str_replace("./", "/", $newImgName);
                    //替换pro_mes
                    $storeModel->store_info = str_replace($item, $newImgName, $storeModel->store_info);
                    //删除原图
                    unlink($image);
                }
            }
            if(!$storeModel->save($storeModel,$upWhere)){
                $this->error("修改失败",$linkurl);
            }
            $this->setAdminUserLog("编辑","修改店铺:id为$store_id","new_store",$store_id);
            //获取图片
            $file=request()->file('store_img');
            if (!empty($file)){
                $oldimg=$storeInfo->store_img;
                //获取店铺分类id最后一个
                $store_arr=explode(',',$cagegorystr);
                $store_category_id=$store_arr[count($store_arr)-1];
                $imgUrl='/images/store/'.$store_category_id.'/'.$store_id.'/detail/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$storeModel->where('store_id='.$store_id)->find();
                $storeobj->store_img=$result;
                if($storeobj->save()){
                    $uploadService->delimage($oldimg);
                }else{
                    $this->error("图片上传失败,店铺修改成功",$linkurl);
                }
            }
            //获取轮播图
            $i = 0;
            $info = array();
            $myFile = $_FILES['myFile'];
            if (!empty($myFile['name']) && !empty($myFile['name'][0])) {
                if (is_string($myFile['name'])) { //单文件上传
                    $info[$i] = $myFile;
                    $i++;
                } else { // 多文件上传
                    if (!empty($myFile['name'][0])) {
                        foreach ($myFile['name'] as $key => $val) {//2维数组转换成1维数组
                            //取出一维数组的值，然后形成另一个数组
                            //新的数组的结构为：info=>i=>('name','size'.....)
                            $info[$i]['name'] = $myFile['name'][$key];
                            $info[$i]['type'] = $myFile['type'][$key];
                            $info[$i]['tmp_name'] = $myFile['tmp_name'][$key];
                            $info[$i]['error'] = $myFile['error'][$key];
                            $info[$i]['size'] = $myFile['size'][$key];
                            $i++;
                        }
                    }
                }
                if ($info) {
                    $imgUrl = '/images/store/' . $store_id . '/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $store['store_banner_img'] = $result;
                    $imgWhere['store_id'] = $store_id;
                    $result = $storeModel->update($store, $imgWhere);
                    if (!$result) {
                        $this->error("图片上传失败,店铺添加成功", $linkurl);
                    }
                }
            }
            $this->success("修改成功",$linkurl);
        }else{
            $category_id=$storeInfo->category_id;
            //获取行业列表
            $navlist=$navmodel->where('disabled=1')->select();
            //获取分类列表
            $categorylist=$categorymodel->where("disabled=1 and grade =1 and category_id = '$category_id'")->select();
            //获取省份
            $provincelist=$regionmodel->where('level =1')->select();
            //获取信息的分类
            $categoryarr=explode(',',$category_id);
            $category_l1='';
            $category_l2='';
            $category_l3='';
            if (count($categoryarr)==1){
                $category_l1=$categoryarr[0];
                $categorylist_2=$categorymodel->field(['category_id','category_name'])->where('parent_id = '.$category_l1)->select();
            }
            if (count($categoryarr)==2){
                $category_l1=$categoryarr[0];
                $category_l2=$categoryarr[1];
            }
            if (count($categoryarr)==3){
                $category_l1=$categoryarr[0];
                $category_l2=$categoryarr[1];
                $category_l3=$categoryarr[2];
            }
            if (count($categoryarr)>1){
                $categorylist_2=$categorymodel->field(['category_id','category_name'])->where('parent_id = '.$category_l1)->select();
                $categorylist_3=$categorymodel->field(['category_id','category_name'])->where('parent_id = '.$category_l2)->select();
            }
            $storeInfo->category_l1=$category_l1;
            $storeInfo->category_l2=$category_l2;
            $storeInfo->category_l3=$category_l3;
            //获取信息的市区
            $citylist=$regionmodel->field(['region_id','name'])->where('p_id='.$storeInfo->province)->select();
            $districtlist=$regionmodel->field(['region_id','name'])->where('p_id='.$storeInfo->city)->select();
            //获取关键字转数组
            if (!empty($storeInfo->store_keywords)){
                $keywordarr=explode(',',$storeInfo->store_keywords);
            }
            $latlng = $storeInfo->lng . "," . $storeInfo->lat;
            $storeInfo->store_info = htmlspecialchars_decode($storeInfo->store_info);
            //清除上传图片session
            Session::delete('uploadimg');

            //获取验证信息
            $storeAuditInfo = $storeAuditModel->where('store_id = '.$store_id)->find();
            if (!empty($storeAuditInfo)){
                $audit_identity_face=$storeAuditInfo->audit_identity_face;
                $audit_identity_coin=$storeAuditInfo->audit_identity_coin;
                $audit_license=$storeAuditInfo->audit_license;
                $storeAuditInfo->identity_face_original=substr($audit_identity_face,6);
                $storeAuditInfo->identity_coin_original=substr($audit_identity_coin,6);
                $storeAuditInfo->license_original=substr($audit_license,6);
            }
            $this->assign('bannerImage',$storeInfo->store_banner_img);
            $this->assign('storeAuditInfo',$storeAuditInfo);
            $this->assign('navlist',$navlist);
            $this->assign('categorylist',$categorylist);
            $this->assign('provincelist',$provincelist);
            $this->assign('categorylist2',$categorylist_2);
            $this->assign('categorylist3',$categorylist_3);
            $this->assign('storeinfo',$storeInfo);
            $this->assign('is_kind',1);
            $this->assign('citylist',$citylist);
            $this->assign('latlng',$latlng);
            $this->assign('districtlist',$districtlist);
            $this->assign('keywordarr',$keywordarr);
            // 模板输出
            return view("Store/storeinfo");
        }
    }
    //省市区三级联动
    public function getregion(RegionModel $regionmodel,Request $request){
        $regionmodel->data($request->param());
        $region_id=$regionmodel->regionid;
        $regionlist=$regionmodel->field(['region_id','name'])->where('p_id = '.$region_id)->select();
        return $regionlist;
    }
    //行业分类三级联动
    public function getcategory(CategoryModel $categorymodel,Request $request){
        $categorymodel->data($request->param());
        $category_id=$categorymodel->categoryid;
        $categorylist=$categorymodel->field(['category_id','category_name'])->where('parent_id = '.$category_id)->select();
        return $categorylist;
    }
    /*
     * 店铺评价模块
     * */
    //店铺评价列表
    public function storecommentlist(StoreCommentModel $storecommentmodel,StoreModel $storeModel,UsersModel $usermodel,Request $request){
        $commentfield=array(' store_comment_id','store_name','user_name','order_id','comment_img','comment_cont','a.comment_num','price','a.disabled');
        $storecommentmodel->data($request->param());
        $where='1= 1';
        if (!empty($storecommentmodel->has_img)) {
            $hsa_img = $storecommentmodel->has_img-1;
            $where .= " and has_img = " . $hsa_img;
        }
        if(!empty($storecommentmodel->keywords)){
            $keywords = $storecommentmodel->keywords;
            $where .= " and user_name like '%" . $keywords . "%'";
        }
        //获取分页数
        if (!empty($storecommentmodel->show_count)){
            $show_count = $storecommentmodel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($storecommentmodel->orderBy)){
            $orderBy = $storecommentmodel->orderBy;
        }else{
            $orderBy = 'store_comment_id';
        }
        if(!empty($storecommentmodel->orderByUpOrDown)){
            $orderByUpOrDown = $storecommentmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $where .= " and a.store_id = " . Session::get('shop_id');
        $storesql=$storeModel->buildSql();
        $usersql=$usermodel->buildSql();
        $commentlist=$storecommentmodel
            ->alias('a')
            ->join([$storesql=> 'u'],'a.store_id = u.store_id','LEFT')
            ->join([$usersql=> 'j'],'a.user_id = j.user_id','LEFT')
            ->field($commentfield)
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$commentlist->appends($parmas)->render();
        $this->assign('commentlist',$commentlist);
        $this->assign('page',$page);
        $this->assign('where', $storecommentmodel->toArray());
        $this->assign('pronum',$commentlist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/storecommentlist");
    }
    //删除店铺评论
    public function storecommentdel(StoreCommentModel $storecommentmodel,Request $request){
        $storecommentmodel->data($request->param());
        $id=$storecommentmodel->id;
        if(is_array($id)){
            //多个删除
            foreach ($id as $v){
                if(!$storecommentmodel->where(["store_comment_id"=>$v])->delete()){
                    $this->error("删除失败");
                }
                $this->setAdminUserLog("删除","删除店铺评论:id为$v","new_store_comment",$v);
            }
            $this->success("删除成功");
        }
        if(!$storecommentmodel->where('store_comment_id='.$id)->delete()){
            $this->error('删除失败');
        }
        $this->setAdminUserLog("删除","删除店铺评论:id为$id","new_store_comment",$id);
        $this->success('删除成功');
    }

    /*
     * 店铺订单模块
     * */
     //店铺交易详情
    public function storeorderinfo(NavModel $navmodel,Request $request,CategoryModel $categorymodel,StoreClearRuleModel $clearrulemodel,StoreClearModel $storeclearmodel,StoreModel $storeModel){
        $parms=$request->param();
        $parms['store_id'] = Session::get('shop_id');
        if (empty($parms['store_id'])){
            $this->error("store_id无效",'/shop/Store/storeorderlist');
        }
        $store_id=$parms['store_id'];
        $storeinfo=$storeModel->where('store_id ='.$store_id)->find();
        if (empty($storeinfo)){
            $this->error("店铺信息无效",'/shop/Store/storeorderlist');
        }
        //所属行业
        $navinfo=$navmodel->field('nav_name')->where('nav_id = '.$storeinfo->nav_id)->find();
        //获取分类
        $category_id=$storeinfo->category_id;
        $categoryarr=explode(',',$category_id);
        $categorystr='';
        foreach ($categoryarr as $value){
            $categoryinfo=$categorymodel->field('category_name')->where('category_id='.$value)->find();
            $categorystr.=$categoryinfo->category_name.' ';
        }


        $this->assign('storeinfo',$storeinfo);
        $this->assign('navinfo',$navinfo);
        $this->assign('categorystr',$categorystr);
        // 模板输出
        return view("Store/storeorderinfo");
    }
    //商品订单列表(线上)
    public function storefororderlist(StoreOrderModel $storeordermodel,Request $request,StoreClearModel $storeclearmodel,UsersModel $usermodel){
        $storeordermodel->data($request->param());
        if (empty($storeordermodel->store_id)){
            $this->error("store_id无效",'/shop/Store/storeorderlist');
        }
        $store_id=$storeordermodel->store_id;
        if (empty($storeordermodel->order_type)){
            $this->error("order_type无效",'/shop/Store/storeorderinfo/store_id/'.$store_id);
        }
        $storefield=array('order_id','order_sn','order_price','buy_price','voucher_num','order_state','mobile','a.create_time','refund_price');
        //获取分页数
        if (!empty($storeordermodel->show_count)){
            $show_count = $storeordermodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1 and store_id ='.$store_id;
        $where.=" and order_type =".($storeordermodel->order_type-1);
        if(!empty($storeordermodel->keywords)){
            $keywords = $storeordermodel->keywords;
            $where .= " and (order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%')";
        }
        if(!empty($storeordermodel->datemin)){
            $where .= " and a.create_time > '" . $storeordermodel->datemin . "'";
        }
        if(!empty($storeordermodel->datemax)){
            $datemax=strtotime($storeordermodel->datemax);
            $datemax=date('Y-m-d',$datemax);
            $where .= " and a.create_time < '" . $datemax . "'";
        }
        if(!empty($storeordermodel->datemin)&&!empty($storeordermodel->datemax)&&$storeordermodel->datemin>$storeordermodel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($storeordermodel->orderBy)){
            $orderBy = $storeordermodel->orderBy;
        }else{
            $orderBy = 'order_id';
        }
        if(!empty($storeordermodel->orderByUpOrDown)){
            $orderByUpOrDown = $storeordermodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $usersql=$usermodel->buildSql();
        $orderlist=$storeordermodel->alias('a')->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')->field($storefield)->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);

        //分页带参数
        $parmas = request()->param();
        $page=$orderlist->appends($parmas)->render();

        $this->assign('orderlist',$orderlist);
        $this->assign('page',$page);
        $this->assign('where', $storeordermodel->toArray());
        $this->assign('pronum',$orderlist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('store_id', $store_id);
        $this->assign('order_type', $storeordermodel->order_type);
        // 模板输出
        return view("Store/storefororderlist");
    }
    //商品订单详情
    public function storeorderlistdetail(StoreOrderModel $storeordermodel,Request $request,StoreClearModel $storeclearmodel,UsersModel $usermodel,UserVoucherModel $uservouchermodel){
        $storeordermodel->data($request->param());
        if (empty($storeordermodel->order_id)){
            $this->error("order_id无效",'/shop/Store/storeorderlist');
        }
        $order_id=$storeordermodel->order_id;
        $storefield=array('order_id','order_sn','order_price','buy_price','voucher_num','order_state','create_time','user_id','store_id','refund_price');
        $storeorderinfo=$storeordermodel->field($storefield)->where('order_id = '.$order_id)->find();
        $userinfo=$usermodel->field(['user_name','mobile'])->where('user_id = '.$storeorderinfo->user_id)->find();
        $store_id=$storeorderinfo->store_id;

        //获取商品列表
        $uservoucherlist=$uservouchermodel->where('order_id ='.$order_id)->select();
        $voucherstats=array(
            'C01'=>待激活,
            'C02'=>待使用,
            'C03'=>已使用,
            'C04'=>已失效,
        );
        foreach ($uservoucherlist as $key => $val){
            $status=$val->used_state;
            $uservoucherlist[$key]->used_state=$voucherstats[$status];
        }
        //总金额
        $uservouchersum=$uservouchermodel->where('order_id ='.$order_id)->sum('buy_price');
        // 模板输出
        $this->assign('storeorderinfo',$storeorderinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('uservoucherlist',$uservoucherlist);
        $this->assign('uservouchersum',$uservouchersum);
        return view("Store/storeorderlistdetail");
    }

    /*
     * 退款模块
     * */
    //商品订单退款列表
    public function proorderrefundlist(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,StoreModel $storeModel,UsersModel $usermodel,RefundReasonModel $refundreasonmodel){
        $uservoucherrefundmodel->data($request->param());
        //获取分页数
        if (!empty($uservoucherrefundmodel->show_count)){
            $show_count = $uservoucherrefundmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($uservoucherrefundmodel->keywords)){
            $keywords = $uservoucherrefundmodel->keywords;
            $where .= " and (order_sn like '%" . $keywords . "%' or mobile like '%".$keywords."%')";
        }
        //订单状态
        if (!empty($uservoucherrefundmodel->audit_state)){
            $refund_state = $uservoucherrefundmodel->audit_state;
            $where .= " and refund_state = '".$refund_state."'";
        }
        if(!empty($uservoucherrefundmodel->datemin)){
            $where .= " and a.refund_time > '" . $uservoucherrefundmodel->datemin . "'";
        }
        if(!empty($uservoucherrefundmodel->datemax)){
            $datemax=strtotime($uservoucherrefundmodel->datemax);
            $datemax=date('Y-m-d',$datemax);
            $where .= " and a.refund_time < '" . $datemax . "'";
        }
        if(!empty($uservoucherrefundmodel->datemin)&&!empty($uservoucherrefundmodel->datemax)&&$uservoucherrefundmodel->datemin>$uservoucherrefundmodel->datemax){
            $this->error("请正确选择时间");
        }
        //排序条件
        if(!empty($uservoucherrefundmodel->orderBy)){
            $orderBy = $uservoucherrefundmodel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($uservoucherrefundmodel->orderByUpOrDown)){
            $orderByUpOrDown = $uservoucherrefundmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        //退款状态
        $refundstatearr=array(
            'D01'=>'退款申请中',
            'D02'=>'退款中',
            'D03'=>'已打款',
            'D04'=>'退款成功',
            'D06'=>'退款关闭'
        );
        $usersql=$usermodel->buildSql();
        $storesql=$storeModel->buildSql();
        $refundlist=$uservoucherrefundmodel->alias('a')->join([$usersql=> 'u'],'a.user_id = u.user_id','LEFT')->join([$storesql=> 'w'],'a.store_id = w.store_id','LEFT')->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$refundlist->appends($parmas)->render();
        foreach($refundlist as $key => $value){
            $refundstate=$value->refund_state;
            $refundlist[$key]->refundstate=$refundstatearr[$refundstate];
            $reasonid=$value->reason_id;
            if (empty($reasonid)){
                $reasonid=0;
            }
            $refundreasonrow=$refundreasonmodel->where('reason_id ='.$reasonid)->find();
            if (empty($refundreasonrow)){
                $refundreasoncon='';
            }else{
                $refundreasoncon=$refundreasonrow->reason_desc;
            }
            $refundlist[$key]->refundreasoncon=$refundreasoncon;
        }

        //统计退款笔数,金额
        $allrefundmoney=$uservoucherrefundmodel->sum('refund_price');
        $todaysql='to_days(refund_time) = to_days(now())';
        $todayrefundcount=$uservoucherrefundmodel->where($todaysql)->count();
        $todayrefundmoney=$uservoucherrefundmodel->where($todaysql)->sum('refund_price');
        $this->assign('refundlist',$refundlist);
        $this->assign('page',$page);
        $this->assign('where', $uservoucherrefundmodel->toArray());
        $this->assign('pronum',$refundlist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('refundstatearr', $refundstatearr);
        $this->assign('allrefundmoney', $allrefundmoney);
        $this->assign('todayrefundcount', $todayrefundcount);
        $this->assign('todayrefundmoney', $todayrefundmoney);
        // 模板输出
        return view("Store/proorderrefundlist");
    }
    //商品订单退款详情
    public function proorderrefundinfo(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UsersModel $usermodel,StoreModel $storeModel,RefundReasonModel $refundreasonmodel){
        $uservoucherrefundmodel->data($request->param());
        if(empty($uservoucherrefundmodel->id)){
           $this->error('商品退款订单id有误');
        }
        $id=$uservoucherrefundmodel->id;
        $refundinfo=$uservoucherrefundmodel->where('id ='.$id)->find();
        if (empty($refundinfo)){
            $this->error('找不到该商品退款订单');
        }
        $userinfo=$usermodel->where('user_id ='.$refundinfo->user_id)->find();
        if (empty($userinfo)){
            $this->error('找不到该用户信息');
        }
        $storeinfo=$storeModel->where('store_id ='.$refundinfo->store_id)->find();
        if (empty($storeinfo)){
            $this->error('找不到该店铺信息');
        }
        $refundreasoninfo=$refundreasonmodel->where('reason_id ='.$refundinfo->reason_id)->find();
        $refundstatearr=array(
            'D01'=>'退款申请中',
            'D02'=>'退款中',
            'D03'=>'已打款',
            'D04'=>'退款成功',
            'D06'=>'退款关闭'
        );
        $refundstate=$refundinfo->refund_state;
        $refundstate=$refundstatearr[$refundstate];
        $refund_img=$refundinfo->refund_img;
        $refund_img=substr($refund_img,6);
        $this->assign('refundinfo',$refundinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('storeinfo',$storeinfo);
        $this->assign('refundreasoninfo',$refundreasoninfo);
        $this->assign('refundstate',$refundstate);
        $this->assign('refund_img',$refund_img);
        // 模板输出
        return view("Store/proorderrefundinfo");
    }
    //商品订单退款操作
    public function proorderrefund(UserVoucherRefundModel $uservoucherrefundmodel,Request $request,UsersModel $usermodel,UserVoucherModel $uservouchermodel,StoreOrderModel $storeordermodel,UserMoneyLogModel $usermoneylogmodel,UserScoreLogModel $userscorelogmodel){
        $uservoucherrefundmodel->data($request->param());
        if(empty($uservoucherrefundmodel->id)){
            $this->error('商品退款订单id有误');
        }
        $id=$uservoucherrefundmodel->id;
        $refundinfo=$uservoucherrefundmodel->where('id',$id)->find();
        if ($refundinfo->refund_state!='D01'){
            $this->error('该商品状态不能退款');
        }
        if (empty($refundinfo)){
            $this->error('找不到该商品退款订单');
        }
        $user_id=$refundinfo->user_id;
        $userinfo=$usermodel->where('user_id',$user_id)->find();
        if (empty($userinfo)){
            $this->error('找不到用户信息');
        }
        $user_voucher_id=$refundinfo->user_voucher_id;
        $uservoucherinfo=$uservouchermodel->where('user_voucher_id ='.$user_voucher_id)->find();
        if (empty($uservoucherinfo)){
            $this->error('找不到用户抵用券信息');
        }
        $olduserscore=$userinfo->user_score;
        $oldusermoney=$userinfo->user_money;
        $deductscore=$uservoucherinfo->user_give_score;   //返还积分
        $backmoney=$refundinfo->refund_price;  //退款金额
        //需要更改的用户金额和积分
        $nowuserscore=$olduserscore-$deductscore;
        $nowusermoney=$oldusermoney+$backmoney;
        //订单退款金额
        $storeorderinfo=$storeordermodel->where('order_id',$refundinfo->order_id)->find();
        $order_refund_price=$storeorderinfo->refund_price;
        $change_refund_price=$order_refund_price+$backmoney;
        // 启动事务
        Db::startTrans();
        try{
            Db::table('new_user_voucher_refund')->where('id',$id)->update(['refund_state'=>'D04']);
            Db::table('new_users')->where('user_id',$user_id)->update(['user_score'=>$nowuserscore,'user_money'=>$nowusermoney]);
            Db::table('new_user_voucher')->where('user_voucher_id',$user_voucher_id)->update(['used_state'=>'C04']);
            Db::table('new_store_order')->where('order_id',$refundinfo->order_id)->update(['refund_price'=>$change_refund_price]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        //记日志
        $moneylogarr=array(
            'user_id'=>$user_id,
            'type'=>2,
            'desc'=>"商品退款,订单:".$refundinfo->order_sn,
            'money'=>$backmoney
        );
        $scorelogarr=array(
            'user_id'=>$user_id,
            'desc'=>'商品退款,订单:'.$refundinfo->order_sn,
            'score'=>'-'.$deductscore
        );
        $usermoneylogmodel->save($moneylogarr);
        $userscorelogmodel->save($scorelogarr);
        $this->setAdminUserLog("编辑","商品退款:id为$id","new_user_voucher_refund",$id);
        //判断订单状态是否需要修改
        $order_id=$refundinfo->order_id;
        $orderinfo=$storeordermodel->where('order_id',$order_id)->find();
        $voucher_num=$orderinfo->voucher_num;
        $refundcount=$uservoucherrefundmodel->where("order_id = $order_id and refund_state != 'D01'")->count();
        if ($voucher_num==$refundcount){
            if(!$storeordermodel->allowField(true)->save(['order_state'=>'T04'],['order_id'=>$order_id])){
                $this->error('退款成功,订单状态更改失败');
            }
        }
        $this->success('退款成功');
    }

    //提现规则列表
    public function withdrawrule(StoreClearRuleModel $clearrulemodel,Request $request){
        $clearrulemodel->data($request->param());
        $where = " rule_range_info IN (" . Session::get('shop_id') . ")";
        $ruleList = $clearrulemodel
            ->where($where)
            ->order('store_order Desc')
            ->find();
        if ($ruleList) {
            $ruleList = $ruleList->toArray();
        }
        //获取券总量
        $this->assign('ruleList',$ruleList);
        // 模板输出
        return view("Store/withdrawruleinfo");
    }
    //规则协议
    public function agreementRule(Request $request,AgreementRuleModel $agreementRuleModel)
    {
        $agreementRuleModel->data($request->param());
        if (!empty($agreementRuleModel->show_count)){
            $show_count = $agreementRuleModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($agreementRuleModel->orderBy)){
            $orderBy = $agreementRuleModel->orderBy;
        }else{
            $orderBy = 'agreement_id';
        }
        if(!empty($agreementRuleModel->orderByUpOrDown)){
            $orderByUpOrDown = $agreementRuleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $agreementRule = $agreementRuleModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $agreementRule->render();
        // 模板变量赋值
        $this->assign('agreementRule', $agreementRule);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('agreementRule');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/agreement_rule");
    }
    //使用优惠券
    public function voucher(UserVoucherModel $userVoucherModel,StoreVoucherModel $storeVoucherModel, Request $request,StoreOrderModel $storeOrderModel,StoreClearModel $storeClearModel,StorePushMessageModel $storePushMessageModel,UsersModel $usersModel){
        $param = $request->param();
        $store_id = Session::get('shop_id');
        //如果是提交
        if(!empty($param['is_ajax'])){
            $voucherSn = $param['voucher_sn'];
            if(empty($voucherSn)||empty($store_id)){
                $this->error('券码已过期或不存在');
            }
            $time = $this->getTime();
            //-- 获取待核销的抵用券
            $userVoucherInfo = $userVoucherModel
                ->append('refund_state')
                ->where("voucher_sn='$voucherSn' and store_id={$store_id} and use_end_date>'".$time."' and '".$time."'>use_start_date")
                ->find();
            if(empty($userVoucherInfo)){
                $this->error('该券不可使用,请重新核对');
            }
            if ($userVoucherInfo->used_state == 'C01') {
                $this->error('待激活，该券不可用');
            }
            if ($userVoucherInfo->used_state == 'C03') {
                $this->error('已使用，该券不可用');
            }
            if ($userVoucherInfo->used_state == 'C04') {
                $this->error('已失效，该券不可用');
            }
            //-- 开启事物
            $userVoucherModel->startTrans();
            //-- 改变抵用券状态
            $userVoucherId = $userVoucherInfo->user_voucher_id;
            if(!$userVoucherModel->save(['used_state'=>'C03','used_time'=>$time],['user_voucher_id'=>$userVoucherId])){
                $userVoucherModel->rollback();
                $this->error('网络延时,请稍后重试');
            }
            //-- 增加优惠券使用数量
            if(!$storeVoucherModel->where(['voucher_id'=>$userVoucherInfo->voucher_id])->setInc('used_num',1)){
                $userVoucherModel->rollback();
                $this->error('网络延时,请稍后重试');
            }
            $orderInfo = $storeOrderModel->where("order_sn='$userVoucherInfo->order_sn'")->find();
            //-- 添加店铺结算表
            $clearData = [
                'order_id'=>$orderInfo->order_id,
                'order_sn'=>$orderInfo->order_sn,
                'voucher_sn'=>$voucherSn,
                'user_id'=>$userVoucherInfo->user_id,
                'order_type'=>$orderInfo->order_type,
                'store_id'=>$orderInfo->store_id,
                'pay_type'=>$orderInfo->pay_type,
                'order_price'=>$userVoucherInfo->voucher_price,
                'clear_price'=>sprintf('%.2f',($userVoucherInfo->buy_price+$userVoucherInfo->coupons_price)),
                'discount_price'=>sprintf('%.2f',$userVoucherInfo->coupons_price),
                'clear_desc'=>'核销',
                'clear_state'=>0
            ];
            if($orderInfo->voucher_type==1){
                if(!$storeClearModel->allowField(true)->create($clearData)){
                    $userVoucherModel->rollback();
                    $this->error('网络延时,请稍后重试');
                }
            }
            //-- 判断是否改变订单状态(按状态优先级判断)
            $orderState = 'T05';
            if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C05'")->find()){
                $orderState = 'T04';
            }
            if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C04'")->find()){
                $orderState = 'T04';
            }
            if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C03'")->find()){
                $orderState = 'T03';
            }
            if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C02'")->find()){
                $orderState = 'T02';
            }
            if($userVoucherModel->where("order_id=$orderInfo->order_id and used_state='C01'")->find()){
                $orderState = 'T01';
            }
            if(!$storeOrderModel->update(['order_state'=>$orderState],['order_id'=>$orderInfo->order_id])){
                $userVoucherModel->rollback();
                $this->jkReturn('-1','网络延时,请稍后重试',[]);
            }
            //-- 消息推送
            $userInfo = $usersModel->where(['user_id'=>$orderInfo->user_id])->find();
            $info = [
                'user_name'=>$userInfo->user_name ,
                'head_img'=>$userInfo->head_img,
                'clear_price'=>$clearData['clear_price'],
                'clear_time'=>$this->getTime(),
                'clear_desc'=>$clearData['clear_desc'],
                'order_sn'=>$clearData['order_sn']
            ];
            $data = [
                'store_id'=>$orderInfo->store_id,
                'message_type'=>2,
                'message_cont'=>"券号:".$voucherSn."已核销",
                'message_data'=>json_encode($info,JSON_UNESCAPED_UNICODE)
            ];
            if(!$storePushMessageModel->allowField(true)->create($data)){
                $userVoucherModel->rollback();
                $this->error('网络延时,请稍后重试');
            }
            //极光推送
            $clientService = new ClientService();
            $receiver['alias'] = array('store'.$orderInfo->store_id);//接收者
            $data['id'] = $storePushMessageModel->getLastInsID();
            $data['create_time'] = $this->getTime();
            $clientService->push($data['message_cont'],$receiver,'核销消息',json_encode($data));

            $rewardService = new RewardService();
            $rewardService->giveUserReward($orderInfo->user_id,$orderInfo->voucher_price);

            $userVoucherModel->commit();
            $this->success('核销成功');
        }else{
            // 模板输出
            return view("Store/voucher_info");
        }
    }
    //提现列表
    public function clearList(StoreClearModel $storeClearModel, Request $request)
    {
        $storeClearModel->data($request->param());
        if (!empty($storeClearModel->show_count)){
            $show_count = $storeClearModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if (!empty($storeClearModel->clear_state)){
            $clear_state = $storeClearModel->clear_state-1;
            $where .= " and b.clear_state = $clear_state ";
        }
        $where .= " and b.store_id = " . Session::get('shop_id');
        //排序条件
        if(!empty($storeClearModel->orderBy)){
            $orderBy = $storeClearModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeClearModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeClearModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storeClearList = $storeClearModel
            ->field('b.*,u.user_name')
            ->alias('b')
            ->join('new_users u','b.user_id = u.user_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $storeClearList->render();
        // 模板变量赋值
        $this->assign('storeClearList', $storeClearList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $storeClearModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('protectList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_clear_list");
    }
    //店铺预约配置
    public function storeReserveConfig(StoreReserveConfigModel $storeReserveConfigModel,StoreModel $storeModel, Request $request)
    {
        $reserveConfigInfo = $request->param();
        $where = " store_id = " . Session::get('shop_id');
        $store = $storeModel->field('store_name')->where($where)->find();
        $reserveConfig = $storeReserveConfigModel
            ->where($where)
            ->find();
        if ($reserveConfig) {
            $reserveConfig = $reserveConfig->toArray();
        }
        if(!empty($reserveConfigInfo['is_ajax'])){
            if (empty($reserveConfig)) {
                $result = $storeReserveConfigModel->create($reserveConfigInfo);
                if($result){
                    $this->setAdminUserLog("添加","添加店铺预约配置：store_id为" . Session::get('shop_id') );
                    $this->success("添加成功");
                }else{
                    $this->error("添加失败");
                }
            }
            $upWhere['store_id'] = $reserveConfigInfo['store_id'];
            $result = $storeReserveConfigModel->update($reserveConfigInfo,$upWhere);
            if($result){
                $this->setAdminUserLog("编辑","编辑店铺预约配置：store_id为" . $reserveConfigInfo['store_id'] );
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $reserveConfig['store_name'] = $store['store_name'];
            //获取券总量
            $this->assign('reserveConfig',$reserveConfig);
            $this->assign('store_id',Session::get('shop_id'));
            // 模板输出
            return view("Store/store_reserve_config");
        }
    }
    //店铺推送消息
    public function storePushMessage(StorePushMessageModel $storePushMessageModel,Request $request)
    {
        $where = " store_id = " . Session::get('shop_id');
        //编码  json_encode  解码 json_decode
        $storePushMessageModel->data($request->param());
        if (!empty($storePushMessageModel->show_count)){
            $show_count = $storePushMessageModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($storePushMessageModel->orderBy)){
            $orderBy = $storePushMessageModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storePushMessageModel->orderByUpOrDown)){
            $orderByUpOrDown = $storePushMessageModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storePushMessage = $storePushMessageModel
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $storePushMessage->render();
        // 模板变量赋值
        $this->assign('storePushMessage', $storePushMessage);
        //权限按钮
        $action_code_list = $this->getChileAction('storePushMessage');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_push_message");

    }

    //活动报名消息
    public function storeActivityMessage(StorePushMessageModel $storePushMessageModel,Request $request)
    {
        $where = "message_type = 3 and store_id = " . Session::get('shop_id');
        //编码  json_encode  解码 json_decode
        $storePushMessageModel->data($request->param());
        if (!empty($storePushMessageModel->show_count)){
            $show_count = $storePushMessageModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($storePushMessageModel->orderBy)){
            $orderBy = $storePushMessageModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storePushMessageModel->orderByUpOrDown)){
            $orderByUpOrDown = $storePushMessageModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storePushMessage = $storePushMessageModel
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $storePushMessage->render();
        // 模板变量赋值
        $this->assign('storePushMessage', $storePushMessage);
        //权限按钮
        $action_code_list = $this->getChileAction('storePushMessage');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_push_message");

    }
    //活动申请
    public function activityApply(ActivityApplyModel $activityApplyModel,StorePushMessageModel $storePushMessageModel,StoreVoucherModel $storeVoucherModel,Request $request)
    {
        //编码  json_encode  解码 json_decode
        $activityApply = $request->param();
        $storePushMessage = $storePushMessageModel
            ->where(["id"=>$activityApply['message_id']])
            ->find();
        $message_data = json_decode($storePushMessage->message_data);
        $storeVoucher = $storeVoucherModel->where(["store_id"=>Session::get('shop_id')])->select();
        $this->assign('storeVoucher', $storeVoucher);
        //如果是提交
        if(!empty($activityApply['is_ajax'])){
            $applyInfo = [
                'activity_list_id'=>$message_data->activity_list_id,
                'store_id'=>Session::get('shop_id'),
                'voucher_id'=>$activityApply['voucher_id']
            ];
            $result = $activityApplyModel->create($applyInfo);
            if($result){
                $id = $activityApplyModel->getLastInsID();
                $this->setAdminUserLog("新增","添加抵用券活动：id为" . $id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $where = " activity_list_id = " . $message_data->activity_list_id . " and store_id = " . Session::get('shop_id');
            $activity = $activityApplyModel
                ->where($where)
                ->find();
            if ($activity) {
                $this->assign('activity', $activity);
            }
            $this->assign('message_id', $activityApply['message_id']);
            // 模板输出
            return view("Store/store_activity_info");
        }
    }

    //店铺配置
    public function storeConfig(StoreConfigDefaultModel $storeConfigDefaultModel,StoreConfigModel $storeConfigModel,Request $request){
        $store_id = Session::get('shop_id');
        $storeConfig = $request->param();
        if (empty($store_id)) {
            $this->error("没有此店铺");
        }
        //如果是提交
        if(!empty($storeConfig['is_ajax'])){
            unset($storeConfig['is_ajax']);
            foreach ($storeConfig as $key=>$value){
                $where['code'] = $key;
                $data['value'] = $value;
                $configInfo = $storeConfigModel->where(['code'=>$key,'store_id'=>$store_id])->find();
                if (!empty($configInfo)) {
                    $storeConfigModel->update($data,$where);
                }else{
                    $storeConfigInfo = $storeConfigDefaultModel->where(['code'=>$key])->find();
                    $configData = array(
                        'store_id'=>$store_id,
                        'name'=>$storeConfigInfo->name,
                        'code'=>$key,
                        'desc'=>$storeConfigInfo->desc,
                        'value'=>$value
                    );
                    $storeConfigModel->create($configData);
                }
            }
            $this->setAdminUserLog("编辑","编辑店铺设置" ,'','');
            $this->success("编辑成功");
        }
        else{
            $storeConfig = $storeConfigModel->where('store_id='.$store_id)->select();
            if (empty($storeConfig->toArray())) {
                $storeConfig = $storeConfigDefaultModel->select();
            }
            $this->assign('storeConfig',$storeConfig);
            // 模板输出
            return view("Store/storeConfig");
        }
    }

    //预约记录表
    public function reserveList(storeReserveModel $storeReserveModel, Request $request){
        $store_id = Session::get('shop_id');
        $storeReserveModel->data($request->param());
        if (!empty($storeReserveModel->show_count)){
            $show_count = $storeReserveModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($storeReserveModel->orderBy)){
            $orderBy = $storeReserveModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeReserveModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeReserveModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $where = 'b.store_id = ' . $store_id;

        if(!empty($storeReserveModel->reserve_state)){
            $where .= " and b.reserve_state = '" . $storeReserveModel->reserve_state . " ' ";
        }
        $reserveList = $storeReserveModel
            ->field('b.*,s.store_name')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        //$page = $reserveList->appends($request->param())->render();
        $page = $reserveList->render();

        // 模板变量赋值
        $this->assign('reserveList', $reserveList);
        $this->assign('orderBy', $orderBy);
        $this->assign('where', $storeReserveModel->toArray());
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('reserveList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_reserve_list");
    }

    //查看预约信息(预约审核)
    public function reserveShow(storeReserveModel $storeReserveModel, Request $request){
        $param = $request->param();
        $store_id = Session::get('shop_id');
        $reserveInfo = $storeReserveModel
            ->field('b.*,s.store_name,u.user_name,s.store_desc')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->join('new_users u','u.user_id = b.user_id','left')
            ->where("b.store_id = $store_id and b.id = $param[reserve_id]")
            ->find()->toArray();

        $this->assign('reserveInfo',$reserveInfo);
        //如果不是是提交
        if(empty($param['is_ajax'])){
            // 模板输出
            return view("Store/reserve_show");
        }
        else{
            $result = $storeReserveModel->update($param,['id'=>$param['reserve_id']]);
            if($result){
                $this->setAdminUserLog("审核","审核预约：id为" . $param['reserve_id'] ,'reserveShow',$param['reserve_id']);
                $this->success("审核成功",'/shop/Store/reserveList');
            }else{
                $this->error("审核失败",'/shop/Store/reserveList');
            }
        }
    }
    //店铺轮播图
    public function bannerInfo(StoreModel $storeModel, UploadService $uploadService, Request $request)
    {
        $banners = $request->param();
        $store_id = Session::get('shop_id');
        $storeInfo=$storeModel->where('store_id='.$store_id)->find();
        if (empty($storeInfo)) {
            $this->error("没有此店铺");
        }
        //如果是提交
        if(!empty($banners['is_ajax'])){
            $i = 0;
            $info = array();
            foreach ($_FILES as $v){//三维数组转换成2维数组
                if(is_string($v['name'])){ //单文件上传
                    $info[$i] = $v;
                    $i++;
                }else{ // 多文件上传
                    foreach ($v['name'] as $key=>$val){//2维数组转换成1维数组
                        //取出一维数组的值，然后形成另一个数组
                        //新的数组的结构为：info=>i=>('name','size'.....)
                        $info[$i]['name'] = $v['name'][$key];
                        $info[$i]['type'] = $v['type'][$key];
                        $info[$i]['tmp_name'] = $v['tmp_name'][$key];
                        $info[$i]['error'] = $v['error'][$key];
                        $info[$i]['size'] = $v['size'][$key];
                        $i++;
                    }
                }
            }
            if ($info) {
                $imgUrl  = '/images/store/'.$store_id.'/';
                $result = $uploadService->uploadmore($info,$imgUrl);
                $store['store_banner_img'] = $result;
            }
            $upWhere['store_id'] = $store_id;
            $result = $storeModel->update($store,$upWhere);
            if($result){
                $this->setAdminUserLog("上传","上传店铺轮播图");
                $this->success("上传成功");
            }else{
                $this->error("上传失败");
            }
        }else{
            $this->assign('bannerImage',$storeInfo->store_banner_img);
            // 模板输出
            return view("Store/banner_info");
        }
    }

    public function getCat(CategoryModel $categoryModel,Request $request){
        $info = $request->param();
        $category_list = array();
        $where = " disabled = 1 ";
        if(!empty($info['nav_id'])){
            $where .= " and nav_id = " . $info['nav_id'];
            $category_list = $categoryModel->field('category_id,category_name')->where($where)->select();
        }
        $this->success("查找成功","",$category_list);
    }
}