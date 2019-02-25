<?php
namespace app\admin\controller;
use app\admin\model\storeActionModel;
use app\admin\model\StoreClearBillModel;
use app\admin\model\StoreCloseModel;
use app\admin\model\StoreDiscountRuleModel;
use app\admin\model\StoreHotConfigModel;
use app\admin\model\StoreProModel;
use app\admin\model\StoreModel;
use app\admin\model\StoreProtectModel;
use app\admin\model\StorePushMessageModel;
use app\admin\model\StoreRebateLogModel;
use app\admin\model\StoreReserveModel;
use app\admin\model\StoreReserveReasonModel;
use app\admin\model\StoreUserActionModel;
use app\admin\model\StoreUserModel;
use geohash\Geohash;
use think\Config;
use think\Request;
use app\admin\model\AdminUserModel;
use app\admin\model\CategoryModel;
use app\admin\service\UploadService;
use app\admin\model\StoreVoucherModel;
use app\admin\model\UserVoucherModel;
use app\admin\model\UserModel;
use app\admin\model\StoreAttrRuleModel;
use app\admin\model\StoreVoucherAttrModel;
use app\admin\model\StoreCategoryModel;
use app\admin\model\NavModel;
use app\admin\model\StoreReportModel;
use app\admin\model\RegionModel;
use think\Session;
use app\admin\model\StoreCommentModel;
use app\admin\model\StoreAuditModel;
use app\admin\model\StoreCreditLogModel;
use app\admin\model\StoreRebateRuleModel;
use think\cache\driver\Redis;
use app\admin\model\StoreClearRuleModel;

use app\admin\model\PriceIntervalModel;
use app\admin\model\DistanceIntervalModel;

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
    public function productlist(AdminUserModel $adminUserModel,StoreProModel $storeproModel,StoreModel $storeModel,Request $request){
        $profield=array('store_pro_id','store_pro_name','store_pro_img','start_time','end_time','store_pro_like','store_name','a.store_id');
        // 获取推荐单品
        $adminUserModel->data($request->param());
        //获取分页数
        if (!empty($adminUserModel->show_count)){
            $show_count = $adminUserModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($adminUserModel->store_id)){
            $where .= " and a.store_id = '" . $adminUserModel->store_id . "'";
        }else{
            $adminUserModel->store_id = 0;
        }
        if(!empty($adminUserModel->proshow)){
            $proshownow=$adminUserModel->proshow-1;
            $where .= " and is_show = '" . $proshownow . "'";
        }
        if(!empty($adminUserModel->datemin)){
            $where .= " and end_time > '" . $adminUserModel->datemin . "'";
        }
        if(!empty($adminUserModel->datemax)){
            $where .= " and start_time < '" . $adminUserModel->datemax . "'";
        }
        if(!empty($adminUserModel->datemin)&&!empty($adminUserModel->datemax)&&$adminUserModel->datemin>$adminUserModel->datemax){
            $this->error("请正确选择时间");
        }
        if(!empty($adminUserModel->keywords)){
            $keywords = $adminUserModel->keywords;
            $where .= " and (a.store_pro_name like '%" . $keywords . "%' or w.store_name like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($adminUserModel->orderBy)){
            $orderBy = $adminUserModel->orderBy;
        }else{
            $orderBy = 'store_pro_id';
        }
        if(!empty($adminUserModel->orderByUpOrDown)){
            $orderByUpOrDown = $adminUserModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $storesql=$storeModel->buildSql();
        $pro_list=$storeproModel->alias('a')->join([$storesql=> 'w'],'a.store_id = w.store_id','LEFT')->where($where)->order($orderBy.' '.$orderByUpOrDown)->field($profield)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$pro_list->appends($parmas)->render();

        $this->assign('store_id',$adminUserModel->store_id);
        $this->assign('prolist',$pro_list);
        $this->assign('page',$page);
        $this->assign('where', $adminUserModel->toArray());
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
                $this->error("添加失败",'/admin/Store/productlist');
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
                    $this->success("添加成功",'/admin/Store/productlist');
                }else{
                    $this->error("图片上传失败,产品添加成功",'/admin/Store/productlist');
                }

            }else{
                $this->success("添加成功",'/admin/Store/productlist');
            }
        }else{
            //获取店铺列表
            $where = 'audit_state = 1';
            if($storeproModel->store_id??0){
                $store_id = $storeproModel->store_id;
                $where .= ' And store_id = ' . $store_id;
            }
            $storelist=$storeModel->field(['store_id','store_name'])->where($where)->select();
            $this->assign('store_id', $storeproModel->store_id??0);
            $this->assign('storelist', $storelist);
            // 模板输出
            return view("Store/productadd");
        }
    }

//    编辑单品
    public function productedit(StoreProModel $storeproModel,StoreModel $storeModel,CategoryModel $categoryModel,Request $request,UploadService $uploadservice){
        $storeproModel->data($request->param());
        if(empty($storeproModel->store_pro_id)){
            $this->error("单品id无效",'/admin/Store/productlist');
        }
        $store_pro_id=$storeproModel->store_pro_id;
        $store_pro_info = $storeproModel->where(["store_pro_id"=>$store_pro_id])->find();
        if(empty($store_pro_info)){
            $this->error("该产品不存在",'/admin/Store/productlist');
        }
        $where = 'audit_state = 1';
        if(!empty($storeproModel->store_id)){
            $store_id = $storeproModel->store_id;
            $where .= ' And store_id = ' . $store_id;
        }
        $storelist=$storeModel->field(['store_id','store_name'])->where($where)->select();
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
                $this->error("添加失败",'/admin/Store/productlist');
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
                    $this->success("修改成功",'/admin/Store/productlist');
                }else{
                    $this->error("图片上传失败,产品信息修改成功",'/admin/Store/productlist');
                }

            }else{
                $this->success("修改成功",'/admin/Store/productlist');
            }

        }else{
//            //获取商品信息
            $field=array('store_pro_id','store_id','store_pro_name','store_pro_keywords','store_pro_img','store_pro_price','store_pro_like','is_show','start_time','end_time');
            $storeproinfo=$storeproModel->field($field)->where('store_pro_id='.$store_pro_id)->find();
            //获取店铺列表
            $this->assign('store_id', $storeproModel->store_id);
            $this->assign('storelist', $storelist);
            $this->assign('storeproinfo', $storeproinfo);
//            // 模板输出
            return view("Store/productedit");
        }
    }

//    删除单品
    public function productdel(AdminUserModel $adminUserModel,Request $request,StoreProModel $storeproModel){
        $adminUserModel->data($request->param());
        if(empty($adminUserModel->pro_id)){
            $this->error("单品id不能为空");
        }
        $pro_id=$adminUserModel->pro_id;
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

        if(!empty($storevouchermodel->store_id)){
            $where .= " and a.store_id = '" . $storevouchermodel->store_id . "'";
        }else{
            $storevouchermodel->store_id = 0;
        }
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
        $voucher_list=$storevouchermodel->alias('a')->join([$storesql=> 'w'],'a.store_id = w.store_id','LEFT')->where($where)->order($orderBy.' '.$orderByUpOrDown)->field($voucherfield)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$voucher_list->appends($parmas)->render();
        //获取券总量
        $voucher_all_num=$storevouchermodel->sum('voucher_stock');
        //获取券的状态量
        $voucher_unused_num=$usermouchermodel->getStateNum('C02'); //未使用
        $voucher_used_num=$usermouchermodel->getStateNum('C03');   //已使用
        $voucher_expired_num=$usermouchermodel->getStateNum('C04'); //已失效
        $voucher_activated_num=$usermouchermodel->where("used_state!='C01'")->count(); //已激活

        $this->assign('store_id',$storevouchermodel->store_id);
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
    public function voucheradd(StoreVoucherModel $storevouchermodel,StoreModel $storeModel,Request $request,StoreAttrRuleModel $storeAttrRuleModel,StoreVoucherAttrModel $storeVoucherAttrModel,UploadService $uploadService){
        $storevouchermodel->data($request->param());
        //-- 添加优惠券属性
        $voucher_attr_rule = $storeAttrRuleModel->select();
        //如果是提交
        if(!empty($storevouchermodel->is_ajax)){
            //店铺所属城市,分类id
            if(!empty($storevouchermodel->store_id)){
                $store_id=$storevouchermodel->store_id;
                $city=$storeModel->field(['city','category_id'])->where('store_id ='.$store_id)->find();
                $storevouchermodel->city=$city->city;
            }

            $storevouchermodel->voucher_info = $storevouchermodel->editorValue;
            if(!$storevouchermodel->allowField(true)->save($storevouchermodel)){
                $this->error("添加失败",'/admin/Store/productlist');
            }
            $add_voucher_id=$storevouchermodel->getLastInsID();
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/Store/voucher/';
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
                    $this->error("图片上传失败,优惠券添加成功",'/admin/Store/voucherlist');
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
            $where = 'audit_state = 1';
            if($storevouchermodel->store_id??0){
                $store_id = $storevouchermodel->store_id;
                $where .= ' And store_id = ' . $store_id;
            }
            //清除上传图片session
            Session::delete('uploadimg');
            //获取店铺列表
            $storelist=$storeModel->field(['store_id','store_name'])->where($where)->select();
            $this->assign('storelist', $storelist);
            $this->assign('voucherAttrRule', $voucher_attr_rule);
            $this->assign('store_id', $storevouchermodel->store_id??0);
            // 模板输出
            return view("Store/voucheradd");
        }
    }

    //修改商铺优惠券
    public function voucheredit(StoreVoucherModel $storevouchermodel,StoreModel $storeModel,Request $request,UploadService $uploadService,StoreAttrRuleModel $storeAttrRuleModel,StoreVoucherAttrModel $storeVoucherAttrModel){
        $storeVoucher = $request->param();
        $voucher_id = $storeVoucher['voucher_id'];
        if(empty($voucher_id)){
            $this->error("优惠券id无效",'/admin/Store/voucherlist');
        }
        $store_id = $storeVoucher['store_id'];
        $voucher_info = $storevouchermodel->where(["voucher_id"=>$voucher_id])->find();
        $old_stock_num=$voucher_info->voucher_stock; //原券库存
        if(empty($voucher_info)){
            $this->error("该优惠券不存在",'/admin/Store/voucherlist');
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
                $storeVoucher['city']=$city->city;
            }
            $storeVoucher['voucher_info'] = $storeVoucher['editorValue'];
            if(!$storevouchermodel->update($storeVoucher,$upWhere)){
                $this->error("修改失败",'/admin/Store/voucherlist');
            }
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/Store/voucher/';
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
                $this->error("修改失败",'/admin/Store/voucherlist');
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
                $imgUrl='/images/voucher/'.$store_category_id.'/'.$store_id.'/detail/'.$storeVoucher['voucher_type'].'/'.$voucher_id.'/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$storevouchermodel->where('voucher_id='.$voucher_id)->find();
                $storeobj->voucher_img=$result;
                if(!$storeobj->save()){
                    $this->error("图片上传失败,优惠券修改成功",'/admin/Store/voucherlist');
                }
                $oldimg=$voucher_info->voucher_img;
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
            $this->success("修改成功",'/admin/Store/voucherlist');

        }else{
            //获取店铺列表
            $where = 'audit_state = 1';
            if(!empty($store_id)){
                $where .= ' And store_id = ' . $store_id;
            }
            //清除上传图片session
            Session::delete('uploadimg');

            $storelist=$storeModel->field(['store_id','store_name'])->where($where)->select();
            $this->assign('bannerImage',$voucher_info->voucher_banner_img);
            $this->assign('store_id', $store_id);
            $this->assign('voucherinfo', $voucher_info);
            $this->assign('voucherAttrRule',$voucher_attr_rule);
            $this->assign('storelist', $storelist);
            // 模板输出
            return view("Store/voucheredit");
        }
    }

    //优惠券信息(用户领取信息)
    public function voucherinfo(StoreVoucherModel $storevouchermodel,UserModel $usermodel,UserVoucherModel $usermouchermodel,Request $request){
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

    /*
     * 店铺模块
     * */
    //店铺列表
    public function storelist(StoreModel $storemodel,StoreReportModel $storereportmodel,CategoryModel $categorymodel,NavModel $navmodel,Request $request,StoreAuditModel $storeAuditModel){
        $storemodel->data($request->param());
        $storefield=array('a.store_id','store_name','nav_name','is_reserve','is_recomm','is_close','audit_state','store_desc','store_credit','a.disabled','store_type');
        //获取行业列表
        $navlist=$navmodel->where('disabled=1')->select();
        //获取分类列表
        $categorylist=$categorymodel->where('disabled=1')->select();
        //获取分页数
        if (!empty($storemodel->show_count)){
            $show_count = $storemodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='audit_state = 1 ';
        if (!empty($storemodel->navshow)){
            $where .= " and a.nav_id = $storemodel->navshow ";
        }
        if (!empty($storemodel->categoryshow)){
            $where .= " and instr(category_id,$storemodel->categoryshow) ";
        }
        if(!empty($storemodel->keywords)){
            $keywords = $storemodel->keywords;
            $where.= " and (store_name like '%" . $keywords . "%' or store_desc like '%" . $keywords . "%'or a.store_keywords like '%" . $keywords. "%')";
        }
        //排序条件
        if(!empty($storemodel->orderBy)){
            $orderBy = $storemodel->orderBy;
        }else{
            $orderBy = 'a.store_id';
        }
        if(!empty($storemodel->orderByUpOrDown)){
            $orderByUpOrDown = $storemodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $navsql=$navmodel->buildSql();
        $reportsql=$storereportmodel->buildSql();
        $storelist=$storemodel->alias('a')->join([$navsql=> 'u'],'a.nav_id = u.nav_id','LEFT')->field($storefield)->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$storelist->appends($parmas)->render();
        $this->assign('navlist',$navlist);
        $this->assign('categorylist',$categorylist);
        $this->assign('storelist',$storelist);
        $this->assign('page',$page);
        $this->assign('where', $storemodel->toArray());
        $this->assign('pronum',$storelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //获取统计数量
        $passnum=$storeAuditModel->where('audit_state = 1')->count();  //审核通过数
        $waitnum=$storeAuditModel->where('audit_state = 0')->count();  //审核失败数
        $storescore=$storemodel->alias('a')->join([$reportsql=> 'u'],'a.store_id = u.store_id','LEFT')->field('u.store_score')->where('disabled=1')->sum('u.store_score');
        $storecoupons=$storemodel->alias('a')->join([$reportsql=> 'u'],'a.store_id = u.store_id','LEFT')->field('coupons_price')->where('disabled=1')->sum('coupons_price');
        $this->assign('passnum',$passnum);
        $this->assign('waitnum',$waitnum);
        $this->assign('storescore',$storescore);
        $this->assign('storecoupons',$storecoupons);
        // 模板输出
        return view("Store/storelist");
    }
    //添加店铺
    public function storeadd(StoreModel $storeModel,CategoryModel $categorymodel,NavModel $navmodel,RegionModel $regionmodel,UploadService $uploadService,Request $request,Geohash $geohash,StoreReportModel $storereportmodel){
        $storeModel->data($request->param());
        //如果是提交
        if(!empty($storeModel->is_ajax)){
            //获取等级分类
            $cagegorystr='';
            if (!empty($storeModel->category_l1)){
                $cagegorystr.=$storeModel->category_l1.',';
            }
            if (!empty($storeModel->category_l2)){
                $cagegorystr.=$storeModel->category_l2.',';
            }
            if (!empty($storeModel->category_l3)){
                $cagegorystr.=$storeModel->category_l3.',';
            }
            $cagegorystr=substr($cagegorystr,0,-1);
            $storeModel->category_id=$cagegorystr;
            //获取经纬度
            if (!empty($storeModel->latlng)){
                $latlng=$storeModel->latlng;
                $latlngarr=explode(',',$latlng);
                $storeModel->lat=$latlngarr[0];
                $storeModel->lng=$latlngarr[1];
                $geohashstr=$geohash->encode($latlngarr[0],$latlngarr[1]);
                $storeModel->geohash=$geohashstr;
            }
            $storeModel->admin_id=Session::get('admin_user_id');
            $storeModel->audit_state=1;
            $storeModel->store_info = $storeModel->editorValue;
            if(!$storeModel->allowField(true)->save($storeModel)){
                $this->error("添加失败",'/admin/Store/storelist');
            }
            $add_store_id=$storeModel->getLastInsID();
            $this->setAdminUserLog("新增","添加店铺:id为$add_store_id","new_store",$add_store_id);
            if(!$storereportmodel->allowField(true)->save(['store_id'=>$add_store_id])){
                $this->error("店铺添加成功,店铺统计信息表添加失败",'/admin/Store/storelist');
            }
            $imgData = Session::get("uploadimg");
            $baseUrl = $this->getConfig('base_url');
            foreach ($imgData as $item){
                //移动原图片
                $image  = '.'.$item;
                $ImgName = rand(100,999).time();
                $imgUrl = './images/Store/'.$add_store_id.'/';
                $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);

                if($newImgName){
                    $newImgName = str_replace("./","$baseUrl/",$newImgName);
                    //替换pro_mes
                    $storeModel->store_info = str_replace($item,$newImgName,$storeModel->store_info);
                    //删除原图
                    unlink($image);
                }
            }
            $upWhere['store_id'] = $add_store_id;
            $storeModel->allowField(true)->save($storeModel,$upWhere);

            //获取图片
            $file=request()->file('store_img');
            if (!empty($file)){
                //获取店铺分类id最后一个
                $store_arr=explode(',',$cagegorystr);
                $store_category_id=$store_arr[count($store_arr)-1];
                $imgUrl='/images/Store/'.$store_category_id.'/'.$add_store_id.'/detail/';
                $imgname=$this->imgName();
                $result=$uploadService->upload($file,$imgUrl,$imgname);
                $storeobj=$storeModel->where('store_id='.$add_store_id)->find();
                $storeobj->store_img=$result;
                if(!$storeobj->save()){
                    $this->error("图片上传失败,店铺添加成功",'/admin/Store/storelist');
                }
            }
            //获取轮播图
            $i = 0;
            $info = array();
            $myFile = $_FILES['myFile'];
            if (!empty($myFile)  && !empty($myFile['name'][0])) {
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
                    $imgUrl = '/images/Store/' . $add_store_id . '/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $store['store_banner_img'] = $result;
                    $imgWhere['store_id'] = $add_store_id;
                    $result = $storeModel->update($store, $imgWhere);
                    if (!$result) {
                        $this->error("多图片上传失败,店铺添加成功", '/admin/Store/storelist');
                    }
                }
            }
                $this->success("添加成功",'/admin/Store/storelist');
        }else{
            //获取行业列表
            $navlist=$navmodel->where('disabled=1')->select();
            //获取分类列表
            $categorylist=$categorymodel->where('disabled=1 and grade =1 ')->select();
            //获取省份
            $provincelist=$regionmodel->where('level =1')->select();
            $storeinfo= array(
                'disabled'=>1,
                'is_reserve'=>1
            );
            //清除上传图片session
            Session::delete('uploadimg');
            $this->assign('storeinfo',$storeinfo);
            $this->assign('navlist',$navlist);
            $this->assign('categorylist',$categorylist);
            $this->assign('provincelist',$provincelist);
            // 模板输出
            return view("Store/storeinfo");
        }
    }
    //修改店铺
    public function storeedit(StoreModel $storeModel,CategoryModel $categorymodel,NavModel $navmodel,RegionModel $regionmodel,UploadService $uploadService,Request $request,Geohash $geohash,StoreCloseModel $storeCloseModel,StoreCreditLogModel $creditLogModel,StoreAuditModel $storeAuditModel){
        $store_info = $request->param();
        $store_id=$store_info['storeid'];
        $is_pass=$store_info['is_pass'];
        $storeInfo=$storeModel->where('store_id='.$store_id)->find();
        //如果是提交
        if(!empty($store_info['is_ajax'])){
            if ($is_pass==1){
                $linkurl='/admin/Store/storelist';
            }else{
                $linkurl='/admin/Store/storereviewlist';
            }
            $upWhere['store_id'] = $store_id;
            if (!empty($store_info['category_l1'])){
                //获取等级分类
                $cagegorystr='';
                $cagegorystr.=$store_info['category_l1'].',';
            }
            if (!empty($store_info['category_l2'])){
                $cagegorystr.=$store_info['category_l2'].',';
            }
            if (!empty($store_info['category_l3'])){
                $cagegorystr.=$store_info['category_l3'].',';
            }
            if (isset($cagegorystr)){
                $cagegorystr=substr($cagegorystr,0,-1);
                $store_info['category_id']=$cagegorystr;
            }
            //获取经纬度
            if (!empty($store_info['latlng'])){
                $latlng=$store_info['latlng'];
                $latlngarr=explode(',',$latlng);
                $store_info['lat']=$latlngarr[0];
                $store_info['lng']=$latlngarr[1];
                $geohashstr=$geohash->encode($latlngarr[0],$latlngarr[1]);
                $store_info['geohash']=$geohashstr;
            }

            $store_info['admin_id'] = Session::get('admin_user_id');

            $store_info['store_info'] = $store_info['editorValue'];
            $imgData = Session::get("uploadimg");
            foreach ($imgData as $item) {
                //移动原图片
                $image = '.' . $item;
                $ImgName = rand(100, 999) . time();
                $imgUrl = './images/Store/' . $store_id . '/';
                $newImgName = $uploadService->uploadImg($image, $imgUrl, $ImgName);
                if ($newImgName) {
                    $newImgName = str_replace("./", "/", $newImgName);
                    //替换pro_mes
                    $store_info['store_info'] = str_replace($item, $newImgName, $store_info['store_info']);
                    //删除原图
                    unlink($image);
                }
            }
            $upStore = $storeModel->update($store_info,$upWhere);
            if(!$upStore){
                $this->error("修改失败",$linkurl);
            }
            if ($store_info['is_close'] == 1) {
                $storeClose = [
                    'store_id'=>$storeInfo['store_id'],
                    'category_id'=>$storeInfo['category_id'],
                    'nav_id'=>$storeInfo['nav_id'],
                    'store_name'=>$storeInfo['store_name'],
                    'store_desc'=>$storeInfo['store_desc'],
                    'store_phone'=>$storeInfo['store_phone'],
                    'store_address'=>$storeInfo['store_address'],
                    'lng'=>$storeInfo['lng'],
                    'lat'=>$storeInfo['lat'],
                    'geohash'=>$storeInfo['geohash'],
                    'province'=>$storeInfo['province'],
                    'city'=>$storeInfo['city'],
                    'district'=>$storeInfo['district'],
                    'close_reason'=>'系统关店'
                ];
                $storeCloseLog = $storeCloseModel->create($storeClose);
                if ($storeCloseLog) {
                    $this->setAdminUserLog("添加","关店审核:store_id为$store_id","new_store",$store_id);
                    $storeVoucher = new StoreVoucherModel();
                    if ($storeVoucher->update(['disabled' => 0],$upWhere)) {
                         $this->setAdminUserLog("编辑","修改优惠券状态:store_id为$store_id","new_store",$store_id);

                    }

                }
            }
            $this->setAdminUserLog("编辑","修改店铺:id为$store_id","new_store",$store_id);

            if(!$creditLogModel->create($store_info)){
                $this->error("修改信誉积分失败",$linkurl);
            }
            if($storeModel->update(['store_credit'=>$store_info['credit_now']],$upWhere)){
                $this->setAdminUserLog("编辑","修改店铺信誉积分:id为$store_id","new_store",$store_id);
            }
            //获取图片
            $file=request()->file('store_img');
            if (!empty($file)){
                $oldimg=$store_info['store_img'];
                //获取店铺分类id最后一个
                $store_arr=explode(',',$cagegorystr);
                $store_category_id=$store_arr[count($store_arr)-1];
                $imgUrl='/images/Store/'.$store_category_id.'/'.$store_id.'/detail/';
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
                    $imgUrl = '/images/Store/' . $store_id . '/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $store['store_banner_img'] = $result;
                    $imgWhere['store_id'] = $store_id;
                    $result = $storeModel->update($store, $imgWhere);
                    if (!$result) {
                        $this->error("图片上传失败,店铺添加成功", $linkurl);
                    }
                    //-- 删除老图片
                    $oldimgArr=$storeInfo->store_banner_img;
                    foreach ($oldimgArr as $v){
                        $uploadService->delimage($v);
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

            if (!empty($categoryarr) && count($categoryarr) == 1){
                $category_l1=$categoryarr[0];
                if ($category_l1 != null) {
                    $categorylist_2=$categorymodel->field(['category_id','category_name'])->where('parent_id = '.$category_l1)->select();
                }
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

            $storeInfo->store_info = htmlspecialchars_decode($storeInfo->store_info);
            //清除上传图片session
            Session::delete('uploadimg');

            //获取验证信息
            $storeAuditInfo = $storeAuditModel->where('store_id = '.$store_id)->find();
            if (!empty($storeAuditInfo)){
                $audit_identity_face=$storeAuditInfo->audit_identity_face;
                $audit_identity_coin=$storeAuditInfo->audit_identity_coin;
                $audit_license=$storeAuditInfo->audit_license;
                $temp_license=$storeAuditInfo->temp_license;
                $storeAuditInfo->identity_face_original=substr($audit_identity_face,6);
                $storeAuditInfo->identity_coin_original=substr($audit_identity_coin,6);
                $storeAuditInfo->license_original=substr($audit_license,6);
                $storeAuditInfo->license_temp = substr($temp_license,6);
            }
            $this->assign('bannerImage',$storeInfo->store_banner_img);
            $this->assign('navlist',$navlist);
            $this->assign('storeAuditInfo',$storeAuditInfo);
            $this->assign('categorylist',$categorylist);
            $this->assign('provincelist',$provincelist);
            $this->assign('categorylist2',$categorylist_2);
            $this->assign('categorylist3',$categorylist_3);
            $this->assign('storeinfo',$storeInfo);
            $this->assign('is_kind',1);
            $this->assign('citylist',$citylist);
            $this->assign('districtlist',$districtlist);
            $this->assign('keywordarr',$keywordarr);
            $this->assign('is_pass',$is_pass);
            $this->assign('is_update',1);
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
    //查看店铺信息(开店审核)
    public function storeshow(StoreModel $storemodel,UploadService $uploadService,StoreReportModel $storereportmodel,CategoryModel $categorymodel,NavModel $navmodel,Request $request,StoreAuditModel $storeauditmodel,StoreUserActionModel $storeUserActionModel,StoreUserModel $storeusermodel){
        $param = $request->param();
        $store_id = $param['store_id'];
        $admin_id = $param['admin_id'];
        $is_pass = $param['is_pass'];
        $storeinfo = $storemodel->where("store_id =$store_id")->find();
        //获取行业
        $navname=$navmodel->field('nav_name')->where('nav_id = '.$storeinfo->nav_id)->find();
        //获取分类
        $category_id=$storeinfo->category_id;
        $categoryarr=explode(',',$category_id);
        $categorystr='';

        if (!empty($categoryarr)) {
            foreach ($categoryarr as $value){
                if (!empty($value)) {
                    $categoryinfo=$categorymodel->field('category_name')->where('category_id='.$value)->find();
                    $categorystr.=$categoryinfo->category_name.' ';
                }
            }
        }
        $imgarr=$storeinfo->audit_img;
        if (!empty($imgarr)) {
            foreach ($imgarr as $value){
                $imageurl=substr($value,6);
                $imageinfo[]=array(
                    'imgurl'=>$imageurl,
                    'smallimgurl'=>$value
                );
            }
        }
        //获取report信息
        $reportinfo = $storereportmodel->where("store_id = $store_id")->find();
        //获取验证信息
        $storeAuditInfo = $storeauditmodel->where("store_id = $store_id and admin_id=$admin_id")->find();

        if (!empty($storeAuditInfo)){
            $audit_identity_face=$storeAuditInfo->audit_identity_face;
            $audit_identity_coin=$storeAuditInfo->audit_identity_coin;
            $audit_license=$storeAuditInfo->audit_license;
            $contract_image=$storeAuditInfo->contract_image;

            $storeAuditInfo->identity_face_original=substr($audit_identity_face,6);
            $storeAuditInfo->identity_coin_original=substr($audit_identity_coin,6);
            $storeAuditInfo->license_original=substr($audit_license,6);
            $storeAuditInfo->contract_original=substr($contract_image,6);
        }else{
            $this->error('未上传审核资料');
        }
        $this->assign('storeinfo',$storeinfo);
        $this->assign('navname',$navname);
        $this->assign('categorystr',$categorystr);
        $this->assign('ispass',$is_pass);
        $this->assign('reportinfo',$reportinfo);
        $this->assign('imageinfo',$imageinfo);
        $this->assign('storeAuditInfo',$storeAuditInfo);
        //如果不是是提交
        if(empty($param['is_ajax'])){
            // 模板输出
            return view("Store/storeshow");
        }
        else{
            $storeAuditInfo->audit_state = $param['audit_state'];
            $file = $_FILES['myfile'];
            if ($file) {
                $path="file/Store/";
                $imgName = $this->imgName();
                $fileUrl = $uploadService->uploadFile($file,$path,$imgName);
                $param['contract_image'] = $fileUrl;
            }
            if (!empty($param['audit_reason'])){
                $storeAuditInfo->audit_reason=$param['audit_reason'];
            }
            $audit_state = array(
                'audit_state'=>$param['audit_state'],
                'audit_reason'=>$param['audit_reason'],
                'contract_time'=>$param['contract_time'],
                'contract_end_time'=>$param['contract_end_time'],
                'contract_number'=>$param['contract_number'],
                'contract_image'=>$param['contract_image']
            );
            $resulttow=$storeAuditInfo->update($audit_state,['store_id'=>$store_id]);
            $result = $storemodel->update(['store_type'=>1],['store_id'=>$store_id]);
            if($resulttow && $result){
                //-- 绑定店铺主和店铺的关系
                if($storeusermodel->update(['store_id'=>$store_id,'is_boss'=>1],['admin_id'=>$admin_id])){
                    //-- 给店铺主设置最高权限
                    $store_user_action = [
                        'admin_user_id'=>$storeAuditInfo['admin_id'],
                        'admin_action_list'=>'all'
                    ];
                    $storeUserActionModel->where('admin_user_id',$admin_id)->delete();
                    $storeUser = $storeUserActionModel->create($store_user_action);
                    if($storeUser){
                        $storeUserId = $storeUserActionModel->getLastInsID();
                        $this->setAdminUserLog("添加","添加用户权限：id为" . $storeUserId ,'Store',$storeUserId);
                    }
                    $this->success("审核成功",'/admin/Store/storereviewlist');
                }
            }else{
                $this->error("审核失败",'/admin/Store/storereviewlist');
            }
        }
    }

    //店铺信誉积分编辑
    public function storecredit(StoreCreditLogModel $creditlogmodel,StoreModel $storemodel,Request $request){
        $creditlogmodel->data($request->param());
        if(empty($creditlogmodel->store_id)){
            $this->error("该店铺id不存在",'/admin/Store/storelist');
        }
        $store_id=$creditlogmodel->store_id;
        $store_info = $storemodel->field(['store_id','store_credit'])->where(["store_id"=>$store_id])->find();
        if(empty($store_info)){
            $this->error("该店铺信息不存在",'/admin/Store/storelist');
        }
        //如果是提交
        if(!empty($creditlogmodel->is_ajax)){
            if(!$creditlogmodel->allowField(true)->save($creditlogmodel)){
                $this->error("修改信誉积分失败",'/admin/Store/storecredit');
            }
            if($store_info->save(['store_credit'=>$creditlogmodel->credit_now])){
                $this->setAdminUserLog("编辑","修改店铺信誉积分:id为$store_id","new_store",$store_id);
                $this->success("修改成功",'/admin/Store/storelist');
            }
        }else{
            $this->assign('reportinfo',$store_info);
            $this->assign('storeid',$store_id);
            // 模板输出
            return view("Store/storecredit");
        }
    }
    //店铺信誉积分列表
    public function storecreditlog(StoreCreditLogModel $creditlogmodel,Request $request){
        $creditlogmodel->data($request->param());
        if(empty($creditlogmodel->store_id)){
            $this->error("该店铺id不存在",'/admin/Store/storelist');
        }
        //获取分页数
        if (!empty($creditlogmodel->show_count)){
            $show_count = $creditlogmodel->show_count;
        }else{
            $show_count = 10;
        }
        $creditloglist=$creditlogmodel->where('store_id='.$creditlogmodel->store_id)->order('id','desc')->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$creditloglist->appends($parmas)->render();
        $this->assign('creditloglist',$creditloglist);
        $this->assign('page',$page);
        $this->assign('store_id',$creditlogmodel->store_id);
        $this->assign('show_count', $show_count);
        // 模板输出
        return view("Store/storecreditlog");
    }

    //店铺分类列表
    public function storecategorylist(StoreCategoryModel $storecategorymodel,Request $request){
        $storecategorymodel->data($request->param());
        //获取分页数
        if (!empty($storecategorymodel->show_count)){
            $show_count = $storecategorymodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($storecategorymodel->keywords)){
            $keywords = $storecategorymodel->keywords;
            $where .= " and (category_name like '%" . $keywords . "%' or category_desc like '%".$keywords."%' or category_keywords like '%".$keywords."%')";
        }
        //排序条件
        if(!empty($storecategorymodel->orderBy)){
            $orderBy = $storecategorymodel->orderBy;
        }else{
            $orderBy = 'store_category_id';
        }
        if(!empty($storecategorymodel->orderByUpOrDown)){
            $orderByUpOrDown = $storecategorymodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $categorylist=$storecategorymodel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$categorylist->appends($parmas)->render();
        //获取券总量
        $this->assign('categorylist',$categorylist);
        $this->assign('page',$page);
        $this->assign('where', $storecategorymodel->toArray());
        $this->assign('pronum',$categorylist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/storecategorylist");
    }
    //添加店铺分类
    public function storecategoryadd(StoreCategoryModel $storecategorymodel,UploadService $uploadservice,Request $request){
        $storecategorymodel->data($request->param());
        //如果是提交
        if(!empty($storecategorymodel->is_ajax)){
            if(!$storecategorymodel->allowField(true)->save($storecategorymodel)){
                $this->error("添加失败",'/admin/Store/storecategorylist');
            }
            $add_category_id=$storecategorymodel->getLastInsID();
            $this->setAdminUserLog("新增","添加店铺分类:id为$add_category_id","new_store_category",$add_category_id);
            //获取图片
            $file=request()->file('category_img');
            if (!empty($file)){
                $imgUrl='/images/storecategory/detail/'.$add_category_id.'/';
                $imgname=$this->imgName();
                $result=$uploadservice->upload($file,$imgUrl,$imgname);
                $storeobj=$storecategorymodel->where('store_category_id='.$add_category_id)->find();
                $storeobj->category_img=$result;
                if($storeobj->save()){
                    $this->success("添加成功",'/admin/Store/storecategorylist');
                }else{
                    $this->error("图片上传失败,分类添加成功",'/admin/Store/storecategorylist');
                }
            }else{
                $this->success("添加成功",'/admin/Store/storecategorylist');
            }
        }else{
            //获取分类列表
            $categorylist=$storecategorymodel->field(['store_category_id','category_name','grade'])->where('disabled =1 and grade = 1')->select()->toArray();
            if (!empty($categorylist)){
                foreach ($categorylist as $key =>$value){
                    $childcategory=$storecategorymodel->getchildCategory($value);
                    $categorylist[$key]['child']=$childcategory;
                }
            }
            $this->assign('categorylist',$categorylist);
            $this->assign('is_type',1);
            // 模板输出
            return view("Store/storecategoryinfo");
        }
    }

    //修改店铺分类
    public function storecategoryedit(StoreCategoryModel $storecategorymodel,UploadService $uploadservice,Request $request){
        $storecategorymodel->data($request->param());
        if(empty($storecategorymodel->category_id)){
            $this->error("该分类id不存在",'/admin/Store/storecategorylist');
        }
        $category_id=$storecategorymodel->category_id;
        $category_info = $storecategorymodel->where(["store_category_id"=>$category_id])->find();
        if(empty($category_info)){
            $this->error("该分类信息不存在",'/admin/Store/storecategorylist');
        }
        //如果是提交
        if(!empty($storecategorymodel->is_ajax)){
            $upWhere['store_category_id'] = $category_id;
            if(!$storecategorymodel->allowField(true)->save($storecategorymodel,$upWhere)){
                $this->error("添加失败",'/admin/Store/storecategorylist');
            }
            $this->setAdminUserLog("编辑","修改店铺分类:id为$category_id","new_store_category",$category_id);
            //获取图片
            $file=request()->file('category_img');
            if (!empty($file)){
                $oldimg=$category_info->category_img;
                $imgUrl='/images/storecategory/detail/'.$category_id.'/';
                $imgname=$this->imgName();
                $result=$uploadservice->upload($file,$imgUrl,$imgname);
                $storeobj=$storecategorymodel->where('store_category_id='.$category_id)->find();
                $storeobj->category_img=$result;
                if($storeobj->save()){
                    $uploadservice->delimage($oldimg);
                    $this->success("修改成功",'/admin/Store/storecategorylist');
                }else{
                    $this->error("图片上传失败,分类修改成功",'/admin/Store/storecategorylist');
                }
            }else{
                $this->success("修改成功",'/admin/Store/storecategorylist');
            }
        }else{
            //获取分类列表
            $categorylist=$storecategorymodel->field(['store_category_id','category_name','grade'])->where('disabled =1 and grade = 1')->select()->toArray();
            if (!empty($categorylist)){
                foreach ($categorylist as $key =>$value){
                    $childcategory=$storecategorymodel->getchildCategory($value);
                    $categorylist[$key]['child']=$childcategory;
                }
            }
            $this->assign('categorylist',$categorylist);
            $this->assign('is_type',2);
            $this->assign('categoryinfo',$category_info);
            $this->assign('categoryid',$category_id);
            // 模板输出
            return view("Store/storecategoryinfo");
        }
    }
    //删除店铺分类
    public function storecategorydel(StoreCategoryModel $storecategorymodel,Request $request){
        $storecategorymodel->data($request->param());
        if(empty($storecategorymodel->category_id)){
            $this->error("该分类id不存在");
        }
        $category_id=$storecategorymodel->category_id;
        $category_info = $storecategorymodel->where(["store_category_id"=>$category_id])->find();
        if(empty($category_info)){
            $this->error("该分类信息不存在");
        }
        $categorylist=$storecategorymodel->where('parent_id = '.$category_id)->select();
        if (!empty($categorylist->toArray())){
            $this->error("该行业下有子分类 ,删除失败");
        }
        if (!$category_info->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除店铺分类:id为$category_id","new_store_category",$category_id);
        $this->success('删除成功');
    }

    /*
     * 店铺审核模块
     * */
    //店铺审核列表
    public function storereviewlist(StoreAuditModel $storeauditmodel,CategoryModel $categorymodel,NavModel $navmodel,Request $request){
        $storeauditmodel->data($request->param());
        $storefield=array('a.store_id','store_name','nav_name','s.audit_state','store_desc','a.disabled','us.user_name','us.mobile','us.admin_id');
        //获取行业列表
        $navlist=$navmodel->where('disabled=1')->select();
        //获取分类列表
        $categorylist=$categorymodel->where('disabled=1')->select();
        //获取分页数
        if (!empty($storeauditmodel->show_count)){
            $show_count = $storeauditmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='s.audit_state != 1 and s.audit_state is not null ';
        if (!empty($storeauditmodel->navshow)){
            $where .= " and a.nav_id = $storeauditmodel->navshow ";
        }
        if (!empty($storeauditmodel->categoryshow)){
            $where .= " and instr(category_id,$storeauditmodel->categoryshow) ";
        }
        if (!empty($storeauditmodel->audit_state)){
            $audit_state=$storeauditmodel->audit_state-1;
            $where .= " and a.audit_state = $audit_state ";
        }
        if(!empty($storeauditmodel->keywords)){
            $keywords = $storeauditmodel->keywords;
            $where .= " and store_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($storeauditmodel->orderBy)){
            $orderBy = $storeauditmodel->orderBy;
        }else{
            $orderBy = 'a.store_id';
        }
        if(!empty($storeauditmodel->orderByUpOrDown)){
            $orderByUpOrDown = $storeauditmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storelist=$storeauditmodel
            ->alias('s')
            ->join('new_store a','a.store_id = s.store_id','left')
            ->join('new_nav u','a.nav_id = u.nav_id','left')
            ->join('store_user us','us.admin_id=s.admin_id','left')
            ->field($storefield)
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$storelist->appends($parmas)->render();
        $this->assign('navlist',$navlist);
        $this->assign('categorylist',$categorylist);
        $this->assign('storelist',$storelist);
        $this->assign('page',$page);
        $this->assign('where', $storeauditmodel->toArray());
        $this->assign('pronum',$storelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/storereviewlist");
    }
    //店铺关店列表
    public function storeCloseList(StoreCloseModel $storeCloseModel,CategoryModel $categorymodel,NavModel $navmodel,Request $request){
        $storeCloseModel->data($request->param());
        $storefield=array('a.id','a.store_id','store_name','u.nav_name','close_state','store_desc');
        //获取行业列表
        $navlist=$navmodel->where('disabled=1')->select();
        //获取分类列表
        $categorylist=$categorymodel->where('disabled=1')->select();
        //获取分页数
        if (!empty($storeCloseModel->show_count)){
            $show_count = $storeCloseModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1 = 1';
        if (!empty($storeCloseModel->navshow)){
            $where .= " and a.nav_id = $storeCloseModel->navshow ";
        }
        if (!empty($storeCloseModel->categoryshow)){
            $where .= " and instr(category_id,$storeCloseModel->categoryshow) ";
        }
        if (!empty($storeCloseModel->audit_state)){
            $audit_state=$storeCloseModel->audit_state-1;
            $where .= " and a.close_state = $audit_state ";
        }
        if(!empty($storeCloseModel->keywords)){
            $keywords = $storeCloseModel->keywords;
            $where .= " and store_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($storeCloseModel->orderBy)){
            $orderBy = $storeCloseModel->orderBy;
        }else{
            $orderBy = 'a.id';
        }
        if(!empty($storeCloseModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeCloseModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $navsql=$navmodel->buildSql();
        $storelist=$storeCloseModel
            ->alias('a')
            ->join([$navsql=> 'u'],'a.nav_id = u.nav_id','LEFT')
            ->field($storefield)
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$storelist->appends($parmas)->render();
        $this->assign('navlist',$navlist);
        $this->assign('categorylist',$categorylist);
        $this->assign('storelist',$storelist);
        $this->assign('page',$page);
        $this->assign('where', $storeCloseModel->toArray());
        $this->assign('pronum',$storelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/store_close_list");
    }
    //店铺关店审核
    public function storeCloseShow(Request $request,StoreModel $storeModel,StoreCloseModel $storeCloseModel,StoreUserModel $storeUserModel)
    {
        $store = $request->param();
        $close_id = $store['id'];
        $storeInfo = $storeCloseModel
            ->where(['id'=>$close_id])
            ->find();
        if(!empty($storeInfo)){
            $storeInfo = $storeInfo->toArray();
        }
        $this->assign('close_img',$storeInfo['close_img']);
        $this->assign('storeInfo',$storeInfo);
        //如果不是是提交
        if(empty($store['is_ajax'])){
            // 模板输出
            return view("Store/store_close_show");
        }else{
            if ($store['close_state'] == 1) {
                $store['reply_reason'] = '';
                //修改原店铺的信息，关店
                $storeWhere = ['store_id'=>$storeInfo['store_id']];
                $storeData = ['disabled'=>0,'audit_state'=>2,'is_close'=>1];
                $storeResult = $storeModel->update($storeData,$storeWhere);
                if($storeResult){
                    $this->setAdminUserLog("编辑","修改原店铺的信息，关店：id为" . $storeInfo['store_id'] ,'Store',$storeInfo['store_id']);
                    $store_user = ['disabled'=>0];
                    $storeUser = $storeUserModel->update($store_user,$storeWhere);
                    if($storeUser){
                        $this->setAdminUserLog("注销","注销店铺主的信息：store_id为" . $storeInfo['store_id'] ,'Store',$storeInfo['store_id']);
                    } else {
                        $this->error("注销失败");
                    }
                }else{
                    $this->error("编辑失败");
                }
            }
            $upWhere['id'] = $close_id;
            $result = $storeCloseModel->update($store,$upWhere);
            if($result){
                $this->setAdminUserLog("审核","审核店铺信息：id为" . $store['id'] ,'Store',$store['id']);
                $this->success("审核成功","",$close_id);
            }else{
                $this->error("审核失败");
            }
        }
    }

    /**
     * 店铺主列表
     *
     * @param StoreAuditModel $storeAuditModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/23
     */
    public function storeUserList(StoreAuditModel $storeAuditModel,StoreUserModel $storeUserModel,Request $request){
        $storeAuditModel->data($request->param());
        //获取分页数
        if (!empty($storeAuditModel->show_count)){
            $show_count = $storeAuditModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = 'is_boss=1 and b.audit_state=1 ';
        if(!empty($storeAuditModel->keywords)){
            $keywords = $storeAuditModel->keywords;
            $where .= " and a.user_name like '%" . $keywords . "%'";
        }
        //排序条件
        if(!empty($storeAuditModel->orderBy)){
            $orderBy = $storeAuditModel->orderBy;
        }else{
            $orderBy = 'a.admin_id';
        }
        if(!empty($storeAuditModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeAuditModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storeAdminList=$storeUserModel
            ->alias('a')
            ->field("a.admin_id,a.user_name,a.mobile,a.disabled,c.store_name,b.audit_state,c.store_id")
            ->join('new_store_audit b','a.admin_id=b.admin_id','left')
            ->join('new_store c','a.store_id=c.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        //分页带参数
        $parmas = request()->param();
        $page = $storeAdminList->appends($parmas)->render();
        $this->assign('storeAdminList',$storeAdminList);
        $this->assign('page',$page);
        $this->assign('where', $storeAuditModel->toArray());
        $this->assign('pronum',$storeAdminList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/store_user_list");
    }

    /**
     * 查看店铺主信息
     *
     * @param Request              $request
     * @param StoreAuditModel      $storeAuditModel
     * @param StoreModel           $storeModel
     * @param StoreUserActionModel $storeUserActionModel
     * @param StoreUserModel       $storeUserModel
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/23
     */
    public function storeUserShow(StoreModel $storemodel,StoreReportModel $storereportmodel,CategoryModel $categorymodel,NavModel $navmodel,Request $request,StoreAuditModel $storeauditmodel)
    {
        $storemodel->data($request->param());
        $store_id=$storemodel->store_id;
        $storeinfo=$storemodel->where('store_id ='.$store_id)->find();
        //获取行业
        $navname=$navmodel->field('nav_name')->where('nav_id = '.$storeinfo->nav_id)->find();
        //获取分类
        $category_id=$storeinfo->category_id;
        $categoryarr=explode(',',$category_id);
        $categorystr='';
        if (!empty($categoryarr)) {
            foreach ($categoryarr as $value){
                if (!empty($value)) {
                    $categoryinfo=$categorymodel->field('category_name')->where('category_id='.$value)->find();
                    $categorystr.=$categoryinfo->category_name.' ';
                }
            }
        }
        $imgarr=$storeinfo->audit_img;
        if (!empty($imgarr)) {
            foreach ($imgarr as $value){
                $imageurl=substr($value,6);
                $imageinfo[]=array(
                    'imgurl'=>$imageurl,
                    'smallimgurl'=>$value
                );
            }
        }
        //获取report信息
        $reportinfo = $storereportmodel->where('store_id = '.$store_id)->find();
        //获取验证信息
        $storeauditinfo = $storeauditmodel->where('store_id = '.$store_id)->find();

        $audit_identity_face=$storeauditinfo->audit_identity_face;
        $audit_identity_coin=$storeauditinfo->audit_identity_coin;
        $audit_license=$storeauditinfo->audit_license;
        $storeauditinfo->identity_face_original=substr($audit_identity_face,6);
        $storeauditinfo->identity_coin_original=substr($audit_identity_coin,6);
        $storeauditinfo->license_original=substr($audit_license,6);

        $this->assign('storeinfo',$storeinfo);
        $this->assign('navname',$navname);
        $this->assign('categorystr',$categorystr);
        $this->assign('reportinfo',$reportinfo);
        $this->assign('imageinfo',$imageinfo);
        $this->assign('storeauditinfo',$storeauditinfo);
        // 模板输出
        return view("Store/store_user_show");
    }
    //编辑店铺主
    public function storeUserEdit(StoreUserModel $storeUserModel,Request $request){
        $storeUser = $request->param();
        //如果是提交
        if(!empty($storeUser['is_ajax'])){
            $upWhere['admin_id'] = $storeUser['admin_id'];
            $result = $storeUserModel->update($storeUser,$upWhere);
            if($result){
                $this->setAdminUserLog("编辑","编辑店铺主：id为" . $storeUser['admin_id'] );
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }
    }
    //查看业务员
    public function storeUserMember(StoreUserModel $storeUserModel,Request $request){
        $storeUserModel->data($request->param());
        if (!empty($storeUserModel->show_count)){
            $show_count = $storeUserModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 and is_boss = 2";
        $store_id = 0;
        if(!empty($storeUserModel->store_id)){
            $store_id = $storeUserModel->store_id;
            $where .= " and store_id = '$store_id'";
        }
        if(!empty($storeUserModel->admin_id)){
            $shop_user_id = $storeUserModel->admin_id;
            $where .= "  and admin_id <> '$shop_user_id'";
        }
        if(!empty($storeUserModel->keywords)){
            $keywords = $storeUserModel->keywords;
            $where .= " and (admin_id like '%" . $keywords . "%' or user_name like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($storeUserModel->orderBy)){
            $orderBy = $storeUserModel->orderBy;
        }else{
            $orderBy = 'admin_id';
        }
        if(!empty($storeUserModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeUserModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $admin_user_list = $storeUserModel
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $admin_user_list->render();
        //权限按钮
        $action_code_list = $this->getChileAction('storeUserMember');
        // 模板变量赋值
        $this->assign('admin_user_list', $admin_user_list);
        $this->assign('store_id', $store_id);
        $this->assign('where', $storeUserModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_user_member_list");
    }
    //添加业务员
    public function storeUserMemberAdd(StoreUserModel $storeUserModel, Request $request)
    {
        $storeUser = $request->param();
        //如果是提交
        if(!empty($storeUser['is_ajax'])){
            $admin_user_info = $storeUserModel->where(["mobile"=>$storeUser['mobile']])->find();
            if(!empty($admin_user_info)){
                $this->error("用户手机号已存在");
            }
            $storeUser['password'] = md5(md5($storeUser['password']));
            $storeUser['is_boss'] = 2;
            $result = $storeUserModel->create($storeUser);
            if($result){
                $admin_id = $storeUserModel->getLastInsID();
                $this->setAdminUserLog("新增","添加业务员：" . $admin_id . "-" . $storeUser['user_name'],'storeUserMemberAdd',$admin_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $this->assign('store_id', $storeUser['store_id']);
            // 模板输出
            return view("Store/store_user_member_info");
        }
    }
    //编辑业务员
    public function storeUserMemberEdit(StoreUserModel $storeUserModel, Request $request){
        $storeUser = $request->param();
        if(empty($storeUser['admin_id'])){
            $this->error("用户id不能为空");
        }
        //如果是提交
        if(!empty($storeUser['is_ajax'])){
            if(!empty($storeUser['mobile'])){
                $admin_user_info = $storeUserModel->where(["mobile"=>$storeUser['mobile']])->where("admin_id","neq",$storeUser['admin_id'])->find();
                if(!empty($admin_user_info)){
                    $this->error("用户名已存在");
                }
            }
            $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUser['admin_id']])->find();
            if(!empty($admin_user_info)){
                $upWhere['user_id'] = $storeUser['admin_id'];
                if(!empty($storeUser['repassword'])){
                    $storeUser['password'] = md5(md5(123123123));
                }
                $result = $storeUserModel->update($storeUser,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑管理员用户：" . $storeUser['admin_id'],'storeUserMemberEdit',$storeUser['admin_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("用户不存在，修改失败");
            }
        }else{
            //获取管理员用户信息
            $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUser['admin_id']])->find();
            if(!empty($admin_user_info)){
                $admin_user_info = $admin_user_info->toArray();
            }
            $this->assign('store_id', $admin_user_info['store_id']);
            $this->assign('admin_user_info', $admin_user_info);
            // 模板输出
            return view("Store/store_user_member_info");
        }
    }
    //删除业务员
    public function storeUserDel(StoreUserModel $storeUserModel, Request $request){
        $storeUserModel->data($request->param());
        $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
        if(!empty($admin_user_info)){
            $result = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除业务员：" . $storeUserModel->admin_id,'storeUserDel',$storeUserModel->admin_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("用户不存在，删除失败");
        }
    }

    //编辑业务员权限
    public function storeUserRuleEdit(StoreUserModel $storeUserModel, Request $request, StoreUserActionModel $userActionModel, storeActionModel $actionModel){
        $storeUserModel->data($request->param());
        if(empty($storeUserModel->admin_id)){
            $this->error("用户id不能为空");
        }
        $admin_user_info = $storeUserModel->where(["admin_id"=>$storeUserModel->admin_id])->find();
        if(empty($admin_user_info)){
            $this->error("用户不存在");
        }
        //如果是提交
        if(!empty($storeUserModel->is_ajax)){
            $upWhere['admin_user_id'] = $storeUserModel->admin_id;
            $userActionModel->admin_user_id = $storeUserModel->admin_id;
            $user_action = $userActionModel->where($upWhere)->find();
            $admin_action_list = explode(",",$storeUserModel->admin_action_list);
            $userActionModel->admin_action_list = serialize($admin_action_list);
            if(!empty($user_action)){
                $result = $userActionModel->allowField(true)->save($userActionModel,$upWhere);
            }else{
                $result = $userActionModel->allowField(true)->save($userActionModel);
            }
            if($result){
                $this->setAdminUserLog("编辑","编辑业务员权限：" . $storeUserModel->admin_id . "-" . $userActionModel->admin_action_list,$storeUserModel->table,$storeUserModel->admin_id);
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }

        }else{
            //获取用户的权限栏目
            $user_action_list = $userActionModel->where(['admin_user_id'=>$storeUserModel->admin_id])->find();
            if(empty($user_action_list->admin_action_list)){
                $user_action_list = array();
            }else{
                if($user_action_list->admin_action_list == 'all'){
                    $user_action_list = "all";
                }else{
                    $user_action_list = unserialize($user_action_list->admin_action_list);
                }
            }

            //获取所有功能方法列表
            $action_list = $actionModel->where(['disabled'=>1])->select();
            $leftMenu = empty($action_list)?array():$action_list->toArray();
            //获取侧边栏
            $allMenuNew = array();
            foreach ($leftMenu as $item){
                if($item['parent_id'] == 0){
                    array_push($allMenuNew, $item);
                }
            }

            //一级权限列表排序
            foreach ($allMenuNew as $key=>$value){
                $id[$key] = $value['action_id'];
                $sort[$key] = $value['sort'];
            }
            array_multisort($sort,SORT_NUMERIC,SORT_ASC,$id,SORT_STRING,SORT_ASC,$allMenuNew);
            foreach ($allMenuNew as $key=>$first){
                $allMenuNew[$key]['children'] = array();
                foreach ($leftMenu as $i){
                    if($i['parent_id'] == $first['action_id']){
                        array_push($allMenuNew[$key]['children'], $i);
                    }
                }
            }

            //对子级权限进行排序
            foreach ($allMenuNew as $k=>$item){
                $item['isAll'] = 0;
                $child = 0;
                $child_have = 0;
                foreach ($item['children'] as $key=>$value){
                    $child++;
                    $id1[$key] = $value['action_id'];
                    $sort1[$key] = $value['sort'];
                    if($user_action_list == 'all'){
                        $item['children'][$key]['isHave'] = 1;
                    }else{
                        if(in_array($value['action_id'],$user_action_list)){
                            $item['children'][$key]['isHave'] = 1;
                            $child_have ++ ;
                        }
                    }
                }
                if($child >= $child_have && $child_have != 0){
                    $item['isAll'] = 1;
                }
                array_multisort($sort1,SORT_NUMERIC,SORT_ASC,$id1,SORT_STRING,SORT_ASC,$item['children']);
                $allMenuNew[$k] = $item;

            }
            $this->assign('allMenunew',$allMenuNew);
            $this->assign("admin_user_id",$storeUserModel->admin_id);
            // 模板输出
            return view("Store/store_user_rule_info");
        }
    }

    //店铺人气值配置
    public function storeHotConfig(StoreHotConfigModel $storeConfigModel, Request $request) {
        $storeConfigModel->data($request->param());
        if (!empty($storeConfigModel->show_count)){
            $show_count = $storeConfigModel->show_count;
        }else{
            $show_count = 10;
        }
        //排序条件
        if(!empty($storeConfigModel->orderBy)){
            $orderBy = $storeConfigModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeConfigModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeConfigModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $storeConfig = $storeConfigModel
            ->alias('a')
            ->join('new_nav b','a.nav_id = b.nav_id','LEFT')
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $storeConfig->render();
        //权限按钮
        $action_code_list = $this->getChileAction('storehotconfig');
        // 模板变量赋值
        $this->assign('storeConfig', $storeConfig);
        $this->assign('where', $storeConfigModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_config_list");
    }
    //店铺人气值添加
    public function storeHotConfigAdd(StoreHotConfigModel $storeConfigModel,NavModel $navModel, Request $request)
    {
        $storeConfigInfo = $request->param();
        $navList = $navModel->where('disabled=1')->select();
        $this->assign('navList', $navList);
        //如果是提交
        if(!empty($storeConfigInfo['is_ajax'])){
            //-- 查询重复
            $storeHot = $storeConfigModel->where('nav_id',$storeConfigInfo['nav_id'])->find();
            if(!empty($storeHot)){
                $this->error("该分类已经添加,请使用编辑功能");
            }
            $result = $storeConfigModel->create($storeConfigInfo);
            if($result){
                $id = $storeConfigModel->getLastInsID();
                $this->setAdminUserLog("新增","添加店铺人气值配置：id为" . $id ,'Store');
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Store/store_config_info");
        }
    }
    //店铺人气值编辑
    public function storeHotConfigEdit(StoreHotConfigModel $storeConfigModel,NavModel $navModel, Request $request)
    {
        $storeConfigInfo = $request->param();
        $navList = $navModel->where('disabled=1')->select();
        $this->assign('navList', $navList);
        //如果是提交
        if(!empty($storeConfigInfo['is_ajax'])){
            $storeConfig = $storeConfigModel->where(["id"=>$storeConfigInfo['id']])->find();
            if(!empty($storeConfig)){
                $upWhere['id'] = $storeConfigInfo['id'];
                $result = $storeConfigModel->update($storeConfigInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑店铺人气值：id为" . $storeConfigInfo['id'] ,'Store',$storeConfigInfo['id']);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("店铺人气值不存在，修改失败");
            }
        }else{
            //获取店铺人气值
            $storeConfig = $storeConfigModel->where(["id"=>$storeConfigInfo['id']])->find();
            if(!empty($storeConfig)){
                $storeConfig = $storeConfig->toArray();
            }
            $this->assign('storeConfig', $storeConfig);
            // 模板输出
            return view("Store/store_config_info");
        }
    }

    public function storeHotConfigDel(StoreHotConfigModel $storeConfigModel,Request $request){
        $storeConfigModel->data($request->param());
        if(empty($storeConfigModel->id)){
            $this->error("会员等级id不能为空");
        }
        $id=$storeConfigModel->id;
        //单个删除
        $rank = $storeConfigModel->where(["id"=>$id])->find();
        if(!empty($rank)){
            $result = $storeConfigModel->where(["id"=>$id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除店铺人气值：id为" . $id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("店铺人气值不存在，删除失败");
        }
    }

    /*
     * 店铺评价模块
     * */
    //店铺评价列表
    public function storecommentlist(StoreCommentModel $storecommentmodel,StoreModel $storemodel,UserModel $usermodel,Request $request){
        $commentfield=array('store_comment_id','store_name','user_name','order_id','comment_img','comment_cont','a.comment_num','price','a.disabled');
        $storecommentmodel->data($request->param());
        $where='1= 1';
        if(!empty($storecommentmodel->keywords)){
            $keywords = $storecommentmodel->keywords;
            $where .= " and store_name like '%" . $keywords . "%'";
        }
        if (!empty($storecommentmodel->has_img)) {
            $hsa_img = $storecommentmodel->has_img-1;
            $where .= " and has_img = " . $hsa_img;
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
        $storesql=$storemodel->buildSql();
        $usersql=$usermodel->buildSql();
        $commentlist = $storecommentmodel
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
    //店铺评论审核
    public function storeCommentEdit(StoreCommentModel $storeCommentModel, Request $request)
    {
        $storeComment = $request->param();
        //如果是提交
        $comment = $storeCommentModel->where(["store_comment_id"=>$storeComment['store_comment_id']])->find();
        if(!empty($comment)){
            $upWhere['store_comment_id'] = $storeComment['store_comment_id'];
            $result = $storeCommentModel->update($storeComment,$upWhere);
            if($result){
                $this->setAdminUserLog("审核","审核评论：id为" . $storeComment['store_comment_id'] );
                $this->success("审核成功");
            }else{
                $this->error("审核失败");
            }
        }else{
            $this->error("评论不存在，审核失败");
        }
    }

    /*
     * 规则设置
     * */
    //补贴规则
    public function discountRule(StoreDiscountRuleModel $storeDiscountRuleModel,Request $request,NavModel $navmodel){
        $storeDiscountRuleModel->data($request->param());
        //获取分页数
        if (!empty($storeDiscountRuleModel->show_count)){
            $show_count = $storeDiscountRuleModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($storeDiscountRuleModel->keywords)){
            $keywords = $storeDiscountRuleModel->keywords;
            $where .= " and (discount_name like '%" . $keywords . "%' or discount_desc like '%".$keywords."%')";
        }
        if(!empty($storeDiscountRuleModel->navshow)){
            $discount_range = $storeDiscountRuleModel->navshow-1;
            $where .= " and discount_range =".$discount_range;
        }
        if(!empty($storeDiscountRuleModel->categoryshow)){
            $rule_type = $storeDiscountRuleModel->categoryshow;
            $where .= " and discount_type =".$rule_type;
        }
        //排序条件
        if(!empty($storeDiscountRuleModel->orderBy)){
            $orderBy = $storeDiscountRuleModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeDiscountRuleModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeDiscountRuleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $discountRuleList=$storeDiscountRuleModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        foreach ($discountRuleList as $key => $val){
            $getarr=array();
            $getlist='';
            if ($val['discount_range']==0){
                $getlist=$navmodel->field('nav_name as name')->where("nav_id in (".$val['discount_range_info'].")")->select()->toArray();
            }elseif($val['discount_range']==1){
                $discountRuleList[$key]->discount_range_info='店铺id：'.$val['discount_range_info'];
            }
            if(is_array($getlist)){
                foreach ($getlist as $value){
                    $getarr[]=$value['name'];
                }
                $discountRuleList[$key]->discount_range_info=implode(',',$getarr);
            }
        }
        //分页带参数
        $parmas = request()->param();
        $page=$discountRuleList->appends($parmas)->render();
        //获取券总量
        $this->assign('discountRuleList',$discountRuleList);
        $this->assign('page',$page);
        $this->assign('where', $storeDiscountRuleModel->toArray());
        $this->assign('pronum',$discountRuleList->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/discount_rule");
    }
    //新增补贴规则
    public function discountRuleAdd(StoreDiscountRuleModel $storeDiscountRuleModel,Request $request){
        $discountRule = $request->param();
        //如果是提交
        if(!empty($discountRule['is_ajax'])){
            $discountRule['disabled'] = 1;
            if(!empty($discountRule['stintid'])){
                $use_scope_info=$discountRule['stintid'];
                $discountRule['discount_range_info']=$use_scope_info;
            }
            if ($discountRule['discount_range']==2){
                $min_credit=$discountRule['min_credit'];
                $max_credit=$discountRule['max_credit'];
                $discount_range_info=$min_credit.','.$max_credit;
                $discountRule['discount_range_info']=$discount_range_info;
            }
            if(!$storeDiscountRuleModel->create($discountRule)){
                $this->error("添加失败",'/admin/Store/discountRule');
            }
            $add_store_id=$storeDiscountRuleModel->getLastInsID();
            $this->setAdminUserLog("新增","添加补贴规则:id为$add_store_id","new_store_discount_rule",$add_store_id);
            $this->success('添加成功','admin/Store/discountRule');
        }else{
            // 模板输出
            return view("Store/discount_rule_info");
        }
    }
    //编辑补贴规则
    public function discountRuleEdit(StoreDiscountRuleModel $storeDiscountRuleModel,Request $request,NavModel $navmodel,StoreModel $storemodel){
        $discountRule = $request->param();
        if (empty($discountRule['id'])){
            $this->error("该规则id不存在",'/admin/Store/discountRule');
        }
        $id = $discountRule['id'];
        $storeDiscountRule = $storeDiscountRuleModel->where('id='.$id)->find();
        //如果是提交
        if(!empty($discountRule['is_ajax'])){
            $upWhere['id'] = $id;
            if(!empty($discountRule['stintid'])){
                $use_scope_info=$discountRule['stintid'];
                $discountRule['discount_range_info']=$use_scope_info;
            }
            if(!$storeDiscountRuleModel->update($discountRule,$upWhere)){
                $this->error("修改失败",'/admin/Store/discountRule');
            }
            $this->setAdminUserLog("编辑","修改补贴规则:id为$id","new_store_discount_rule",$id);
            $this->success('修改成功','admin/Store/discountRule');
        }else{
            $creditarr=array();
            $discount_range=$storeDiscountRule->discount_range;
            $discount_range_info=$storeDiscountRule->discount_range_info;
            if ($discount_range == 0){
                //行业
                $where='disabled =1 and ';
                $where.='nav_id in ('.$discount_range_info.')';
                $list=$navmodel->field(['nav_id as id','nav_name as name'])->where($where)->select();
            }elseif($discount_range == 1){
                //店铺
                $where='1 =1 and ';
                $where.='store_id in ('.$discount_range_info.') and audit_state = 1 ';
                $list=$storemodel->field(['store_id as id','store_name as name'])->where($where)->select();
            }
            $this->assign('discountRuleInfo',$storeDiscountRule);
            $this->assign('scopelist',$list);
            $this->assign('creditarr',$creditarr);
            $this->assign('istype',2);
            $this->assign('id',$id);
            // 模板输出
            return view("Store/discount_rule_info");
        }
    }
    //删除补贴规则
    public function discountRuleDel(StoreDiscountRuleModel $storeDiscountRuleModel,Request $request){
        $storeDiscountRuleModel->data($request->param());
        if (empty($storeDiscountRuleModel->id)){
            $this->error("该规则id不存在",'/admin/Store/discountRule');
        }
        $id=$storeDiscountRuleModel->id;
        $rebateruleinfo=$storeDiscountRuleModel->where('id='.$id)->find();
        if ($rebateruleinfo->delete()){
            $this->setAdminUserLog("删除","删除补贴规则:id为$id","new_store_discount_rule",$id);
            $this->success("删除成功",'/admin/Store/discountRule');
        }else{
            $this->error("删除失败",'/admin/Store/discountRule');
        }
    }


    //返利规则
    public function rebaterule(StoreRebateRuleModel $rebaterulemodel,NavModel $navmodel,CategoryModel $categorymodel,Request $request){
        $rebaterulemodel->data($request->param());
        //获取分页数
        if (!empty($rebaterulemodel->show_count)){
            $show_count = $rebaterulemodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($rebaterulemodel->keywords)){
            $keywords = $rebaterulemodel->keywords;
            $where .= " and (rule_name like '%" . $keywords . "%' or rule_desc like '%".$keywords."%')";
        }
        if(!empty($rebaterulemodel->navshow)){
            $rule_range = $rebaterulemodel->navshow-1;
            $where .= " and rule_range =".$rule_range;
        }
        if(!empty($rebaterulemodel->categoryshow)){
            $rule_type = $rebaterulemodel->categoryshow-1;
            $where .= " and rule_type =".$rule_type;
        }
        //排序条件
        if(!empty($rebaterulemodel->orderBy)){
            $orderBy = $rebaterulemodel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($rebaterulemodel->orderByUpOrDown)){
            $orderByUpOrDown = $rebaterulemodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $rebaterulelist=$rebaterulemodel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        foreach ($rebaterulelist as $key => $val){
            $getarr=array();
            $getlist='';
            if ($val['rule_range']==0){
                $getlist=$navmodel->field('nav_name as name')->where("nav_id in (".$val['rule_range_info'].")")->select()->toArray();
            }elseif($val['rule_range']==1){
                $getlist=$categorymodel->field('category_name as name')->where("category_id in (".$val['rule_range_info'].")")->select()->toArray();
            }elseif($val['rule_range']==2){
                $rebaterulelist[$key]->rule_range_info=str_replace(',',' - ',$val['rule_range_info']);
            }elseif($val['rule_range']==3){
                $rebaterulelist[$key]->rule_range_info='店铺id：'.$val['rule_range_info'];
            }
            if(is_array($getlist)){
                foreach ($getlist as $value){
                    $getarr[]=$value['name'];
                }
                $rebaterulelist[$key]->rule_range_info=implode(',',$getarr);
            }
        }
        //分页带参数
        $parmas = request()->param();
        $page=$rebaterulelist->appends($parmas)->render();
        //获取券总量
        $this->assign('rebaterulelist',$rebaterulelist);
        $this->assign('page',$page);
        $this->assign('where', $rebaterulemodel->toArray());
        $this->assign('pronum',$rebaterulelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/rebaterule");
    }
    //新增返利规则
    public function rebateruleadd(StoreRebateRuleModel $rebaterulemodel,Request $request){
        $rebaterulemodel->data($request->param());
        //如果是提交
        if(!empty($rebaterulemodel->is_ajax)){
            $rebaterulemodel->disabled=1;
            if(!empty($rebaterulemodel->stintid)){
                $use_scope_info=$rebaterulemodel->stintid;
                $rebaterulemodel->rule_range_info=$use_scope_info;
            }
            if ($rebaterulemodel->rule_range==2){
                $min_credit=$rebaterulemodel->min_credit;
                $max_credit=$rebaterulemodel->max_credit;
                $rule_range_info=$min_credit.','.$max_credit;
                $rebaterulemodel->rule_range_info=$rule_range_info;
            }
            if(!$rebaterulemodel->allowField(true)->save($rebaterulemodel)){
                $this->error("添加失败",'/admin/Store/rebaterule');
            }
            $add_store_id=$rebaterulemodel->getLastInsID();
            $this->setAdminUserLog("新增","添加返利规则:id为$add_store_id","new_store_rebate_rule",$add_store_id);
            $this->success('添加成功','admin/Store/rebaterule');
        }else{
            // 模板输出
            return view("Store/rebateruleinfo");
        }
    }
    //编辑返利规则
    public function rebateruleedit(StoreRebateRuleModel $rebaterulemodel,Request $request,NavModel $navmodel,CategoryModel $categorymodel,StoreModel $storemodel){
        $rebaterulemodel->data($request->param());
        if (empty($rebaterulemodel->id)){
            $this->error("该规则id不存在",'/admin/Store/rebaterule');
        }
        $id=$rebaterulemodel->id;
        $rebateruleinfo=$rebaterulemodel->where('id='.$id)->find();
        //如果是提交
        if(!empty($rebaterulemodel->is_ajax)){
            $upWhere['id'] = $id;
            if(!empty($rebaterulemodel->stintid)){
                $use_scope_info=$rebaterulemodel->stintid;
                $rebaterulemodel->rule_range_info=$use_scope_info;
            }

            if (!empty($rebaterulemodel->rule_range)&&$rebaterulemodel->rule_range==2){
                $min_credit=$rebaterulemodel->min_credit;
                $max_credit=$rebaterulemodel->max_credit;
                $rule_range_info=$min_credit.','.$max_credit;
                $rebaterulemodel->rule_range_info=$rule_range_info;
            }
            if(!$rebaterulemodel->allowField(true)->save($rebaterulemodel,$upWhere)){
                $this->error("修改失败",'/admin/Store/rebaterule');
            }
            $this->setAdminUserLog("编辑","修改返利规则:id为$id","new_store_rebate_rule",$id);
            $this->success('修改成功','admin/Store/rebaterule');
        }else{
            $creditarr=array();
            $rule_range=$rebateruleinfo->rule_range;
            $rule_range_info=$rebateruleinfo->rule_range_info;
            if ($rule_range==0){
                //行业
                $where='disabled =1 and ';
                $where.='nav_id in ('.$rule_range_info.')';
                $list=$navmodel->field(['nav_id as id','nav_name as name'])->where($where)->select();
            }elseif($rule_range==1){
                //分类
                $where='disabled =1 and ';
                $where.='category_id in ('.$rule_range_info.')';
                $list=$categorymodel->field(['category_id as id','category_name as name'])->where($where)->select();
            }elseif ($rule_range==2){
                //积分
                $creditarr=explode(',',$rule_range_info);
            }elseif($rule_range==3){
                //店铺
                $where='1=1 and ';
                $where.='store_id in ('.$rule_range_info.') and audit_state = 1 ';
                $list=$storemodel->field(['store_id as id','store_name as name'])->where($where)->select();
            }
            $this->assign('rebateruleinfo',$rebateruleinfo);
            $this->assign('scopelist',$list);
            $this->assign('creditarr',$creditarr);
            $this->assign('istype',2);
            $this->assign('id',$id);
            // 模板输出
            return view("Store/rebateruleinfo");
        }
    }
    //删除返利规则
    public function rebateruledel(StoreRebateRuleModel $rebaterulemodel,Request $request){
        $rebaterulemodel->data($request->param());
        if (empty($rebaterulemodel->id)){
            $this->error("该规则id不存在",'/admin/Store/rebaterule');
        }
        $id=$rebaterulemodel->id;
        $rebateruleinfo=$rebaterulemodel->where('id='.$id)->find();
        if ($rebateruleinfo->delete()){
            $this->setAdminUserLog("删除","删除返利规则:id为$id","new_store_rebate_rule",$id);
            $this->success("删除成功",'/admin/Store/rebaterule');
        }else{
            $this->error("删除失败",'/admin/Store/rebaterule');
        }
    }

    //提现规则列表
    public function withdrawrule(StoreClearRuleModel $clearrulemodel,NavModel $navmodel,CategoryModel $categorymodel,Request $request){
        $clearrulemodel->data($request->param());
        //获取分页数
        if (!empty($clearrulemodel->show_count)){
            $show_count = $clearrulemodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($clearrulemodel->keywords)){
            $keywords = $clearrulemodel->keywords;
            $where .= " and (rule_name like '%" . $keywords . "%' or rule_desc like '%".$keywords."%')";
        }
        if(!empty($clearrulemodel->navshow)){
            $rule_range = $clearrulemodel->navshow-1;
            $where .= " and rule_range =".$rule_range;
        }
        //排序条件
        if(!empty($clearrulemodel->orderBy)){
            $orderBy = $clearrulemodel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($clearrulemodel->orderByUpOrDown)){
            $orderByUpOrDown = $clearrulemodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $rulelist=$clearrulemodel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        foreach ($rulelist as $key => $val){
            $getarr=array();
            $getlist='';
            if ($val['rule_range']==0){
                $getlist=$navmodel->field('nav_name as name')->where("nav_id in (".$val['rule_range_info'].")")->select()->toArray();
            }elseif($val['rule_range']==1){
                $rulelist[$key]->rule_range_info=str_replace(',',' - ',$val['rule_range_info']);
            }elseif($val['rule_range']==2){
                $rulelist[$key]->rule_range_info='店铺id：'.$val['rule_range_info'];
            }
            if(is_array($getlist)){
                foreach ($getlist as $value){
                    $getarr[]=$value['name'];
                }
                $rulelist[$key]->rule_range_info=implode(',',$getarr);
            }
        }
        //分页带参数
        $parmas = request()->param();
        $page=$rulelist->appends($parmas)->render();
        //获取券总量
        $this->assign('rebaterulelist',$rulelist);
        $this->assign('page',$page);
        $this->assign('where', $clearrulemodel->toArray());
        $this->assign('pronum',$rulelist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/withdrawrule");
    }
    //新增提现规则
    public function withdrawruleadd(StoreClearRuleModel $clearrulemodel,Request $request){
        $clearrulemodel->data($request->param());
        //如果是提交
        if(!empty($clearrulemodel->is_ajax)){
            $clearrulemodel->disabled=1;
            if(!empty($clearrulemodel->stintid)){
                $use_scope_info=$clearrulemodel->stintid;
                $clearrulemodel->rule_range_info=$use_scope_info;
            }
            if ($clearrulemodel->rule_range==2){
                $min_credit=$clearrulemodel->min_credit;
                $max_credit=$clearrulemodel->max_credit;
                $rule_range_info=$min_credit.','.$max_credit;
                $clearrulemodel->rule_range_info=$rule_range_info;
            }
            if(!$clearrulemodel->allowField(true)->save($clearrulemodel)){
                $this->error("添加失败",'/admin/Store/withdrawrule');
            }
            $add_store_id=$clearrulemodel->getLastInsID();
            $this->setAdminUserLog("新增","添加提现规则:id为$add_store_id","new_store_clear_rule",$add_store_id);
            $this->success('添加成功','admin/Store/withdrawrule');
        }else{
            // 模板输出
            return view("Store/withdrawruleinfo");
        }
    }
    //编辑提现规则
    public function withdrawruleedit(StoreClearRuleModel $clearrulemodel,Request $request,NavModel $navmodel,CategoryModel $categorymodel,StoreModel $storemodel){
        $clearrulemodel->data($request->param());
        if (empty($clearrulemodel->id)){
            $this->error("该规则id不存在",'/admin/Store/rebaterule');
        }
        $id=$clearrulemodel->id;
        $withdrawruleinfo=$clearrulemodel->where('id='.$id)->find();
        //如果是提交
        if(!empty($clearrulemodel->is_ajax)){
            $upWhere['id'] = $id;
            if(!empty($clearrulemodel->stintid)){
                $use_scope_info=$clearrulemodel->stintid;
                $clearrulemodel->rule_range_info=$use_scope_info;
            }

            if (!empty($clearrulemodel->rule_range)&&$clearrulemodel->rule_range==2){
                $min_credit=$clearrulemodel->min_credit;
                $max_credit=$clearrulemodel->max_credit;
                $rule_range_info=$min_credit.','.$max_credit;
                $clearrulemodel->rule_range_info=$rule_range_info;
            }
            if(!$clearrulemodel->allowField(true)->save($clearrulemodel,$upWhere)){
                $this->error("修改失败",'/admin/Store/withdrawrule');
            }
            $this->setAdminUserLog("编辑","修改提现规则:id为$id","new_store_clear_rule",$id);
            $this->success('修改成功','admin/Store/withdrawrule');
        }else{
            $creditarr=array();
            $rule_range=$withdrawruleinfo->rule_range;
            $rule_range_info=$withdrawruleinfo->rule_range_info;
            if ($rule_range==0){
                //行业
                $where='disabled =1 and ';
                $where.='nav_id in ('.$rule_range_info.')';
                $list=$navmodel->field(['nav_id as id','nav_name as name'])->where($where)->select();
            }elseif($rule_range==3){
                //分类
                $where='disabled =1 and ';
                $where.='category_id in ('.$rule_range_info.')';
                $list=$categorymodel->field(['category_id as id','category_name as name'])->where($where)->select();
            }elseif ($rule_range==2){
                //积分
                $creditarr=explode(',',$rule_range_info);
            }elseif($rule_range==1){
                //店铺
                $where='1 =1 and ';
                $where.='store_id in ('.$rule_range_info.') and audit_state = 1 ';
                $list=$storemodel->field(['store_id as id','store_name as name'])->where($where)->select();
            }

            $this->assign('withdrawruleinfo',$withdrawruleinfo);
            $this->assign('scopelist',$list);
            $this->assign('creditarr',$creditarr);
            $this->assign('istype',2);
            $this->assign('id',$id);
            // 模板输出
            return view("Store/withdrawruleinfo");
        }
    }
    //删除提现规则
    public function withdrawruledel(StoreClearRuleModel $clearrulemodel,Request $request){
        $clearrulemodel->data($request->param());
        if (empty($clearrulemodel->id)){
            $this->error("该规则id不存在",'/admin/Store/withdrawrule');
        }
        $id=$clearrulemodel->id;
        $rebateruleinfo=$clearrulemodel->where('id='.$id)->find();
        if ($rebateruleinfo->delete()){
            $this->setAdminUserLog("删除","删除提现规则:id为$id","new_store_clear_rule",$id);
            $this->success("删除成功",'/admin/Store/withdrawrule');
        }else{
            $this->error("删除失败",'/admin/Store/withdrawrule');
        }
    }
    /*
     * 筛选条件设置模块
     * */
    //价格区间列表
    public function priceintervallist(PriceIntervalModel $priceintervalmodel,Request $request){
        $priceintervalmodel->data($request->param());
        //获取分页数
        if (!empty($priceintervalmodel->show_count)){
            $show_count = $priceintervalmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($priceintervalmodel->keywords)){
            $keywords = $priceintervalmodel->keywords;
            $where .= " and (price_desc like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($priceintervalmodel->orderBy)){
            $orderBy = $priceintervalmodel->orderBy;
        }else{
            $orderBy = 'price_interval_id';
        }
        if(!empty($priceintervalmodel->orderByUpOrDown)){
            $orderByUpOrDown = $priceintervalmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $intervallist=$priceintervalmodel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$intervallist->appends($parmas)->render();
        $this->assign('intervallist',$intervallist);
        $this->assign('page',$page);
        $this->assign('where', $priceintervalmodel->toArray());
        $this->assign('pronum',$intervallist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/priceintervallist");
    }
    //添加价格区间
    public function priceintervaladd(PriceIntervalModel $priceintervalmodel,Request $request){
        $priceintervalmodel->data($request->param());
        //如果是提交
        if(!empty($priceintervalmodel->is_ajax)){
            $priceintervalmodel->disabled=1;
            if(!$priceintervalmodel->allowField(true)->save($priceintervalmodel)){
                $this->error("添加失败",'/admin/Store/priceintervallist');
            }
            $add_priceinterval_id=$priceintervalmodel->getLastInsID();
            $this->setAdminUserLog("新增","添加价格筛选条件:id为$add_priceinterval_id","new_price_interval",$add_priceinterval_id);
            $this->success('添加成功','admin/Store/priceintervallist');
        }else{
            // 模板输出
            return view("Store/priceintervalinfo");
        }
    }
    //编辑价格区间
    public function priceintervaledit(PriceIntervalModel $priceintervalmodel,Request $request){
        $priceintervalmodel->data($request->param());
        if (empty($priceintervalmodel->id)){
            $this->error("该价格筛选条件id不存在",'/admin/Store/priceintervallist');
        }
        $id=$priceintervalmodel->id;
        $priceintervalinfo=$priceintervalmodel->where('price_interval_id='.$id)->find();
        if (empty($priceintervalinfo)){
            $this->error("找不到该价格筛选条件",'/admin/Store/priceintervallist');
        }
        //如果是提交
        if(!empty($priceintervalmodel->is_ajax)){
            $upWhere['price_interval_id']=$id;
            if(!$priceintervalmodel->allowField(true)->save($priceintervalmodel,$upWhere)){
                $this->error("编辑失败");
            }
            $this->setAdminUserLog("编辑","编辑价格筛选条件:id为$id","new_price_interval",$id);
            $this->success('编辑成功','admin/Store/priceintervallist');
        }else{
            $this->assign('priceintervalinfo',$priceintervalinfo);
            $this->assign('istype',2);
            $this->assign('id',$id);
            // 模板输出
            return view("Store/priceintervalinfo");
        }
    }
    //删除价格区间
    public function priceintervaldel(PriceIntervalModel $priceintervalmodel,Request $request){
        $priceintervalmodel->data($request->param());
        if (empty($priceintervalmodel->id)){
            $this->error("该价格筛选条件id不存在");
        }
        $id=$priceintervalmodel->id;
        $priceintervalinfo=$priceintervalmodel->where('price_interval_id='.$id)->find();
        if (empty($priceintervalinfo)){
            $this->error("找不到该价格筛选条件");
        }
        if (!$priceintervalinfo->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除价格筛选条件:id为$id","new_price_interval",$id);
        $this->success("删除成功");
    }

    //距离区间列表
    public function distanceintervallist(DistanceIntervalModel $distanceintervalmodel,Request $request){
        $distanceintervalmodel->data($request->param());
        //获取分页数
        if (!empty($distanceintervalmodel->show_count)){
            $show_count = $distanceintervalmodel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($distanceintervalmodel->keywords)){
            $keywords = $distanceintervalmodel->keywords;
            $where .= " and (distance_desc like '%" . $keywords . "%')";
        }
        //排序条件
        if(!empty($distanceintervalmodel->orderBy)){
            $orderBy = $distanceintervalmodel->orderBy;
        }else{
            $orderBy = 'distance_interval_id';
        }
        if(!empty($distanceintervalmodel->orderByUpOrDown)){
            $orderByUpOrDown = $distanceintervalmodel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $intervallist=$distanceintervalmodel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        //分页带参数
        $parmas = request()->param();
        $page=$intervallist->appends($parmas)->render();
        $this->assign('intervallist',$intervallist);
        $this->assign('page',$page);
        $this->assign('where', $distanceintervalmodel->toArray());
        $this->assign('pronum',$intervallist->toArray()['total']);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("Store/distanceintervallist");
    }
    //添加距离区间
    public function distanceintervaladd(DistanceIntervalModel $distanceintervalmodel,Request $request){
        $distanceintervalmodel->data($request->param());
        //如果是提交
        if(!empty($distanceintervalmodel->is_ajax)){
            $distanceintervalmodel->disabled=1;
            if(!$distanceintervalmodel->allowField(true)->save($distanceintervalmodel)){
                $this->error("添加失败",'/admin/Store/distanceintervallist');
            }
            $add_distanceinterval_id=$distanceintervalmodel->getLastInsID();
            $this->setAdminUserLog("新增","添加距离筛选条件:id为$add_distanceinterval_id","new_distance_interval",$add_distanceinterval_id);
            $this->success('添加成功','admin/Store/distanceintervallist');
        }else{
            // 模板输出
            return view("Store/distanceintervalinfo");
        }
    }
    //编辑距离区间
    public function distanceintervaledit(DistanceIntervalModel $distanceintervalmodel,Request $request){
        $distanceintervalmodel->data($request->param());
        if (empty($distanceintervalmodel->id)){
            $this->error("该距离筛选条件id不存在",'/admin/Store/distanceintervallist');
        }
        $id=$distanceintervalmodel->id;
        $distanceintervalinfo=$distanceintervalmodel->where('distance_interval_id='.$id)->find();
        if (empty($distanceintervalinfo)){
            $this->error("找不到该距离筛选条件",'/admin/Store/distanceintervallist');
        }
        //如果是提交
        if(!empty($distanceintervalmodel->is_ajax)){
            $upWhere['distance_interval_id']=$id;
            if(!$distanceintervalmodel->allowField(true)->save($distanceintervalmodel,$upWhere)){
                $this->error("编辑失败");
            }
            $this->setAdminUserLog("编辑","编辑距离筛选条件:id为$id","new_distance_interval",$id);
            $this->success('编辑成功','admin/Store/distanceintervallist');
        }else{
            $this->assign('distanceintervalinfo',$distanceintervalinfo);
            $this->assign('istype',2);
            $this->assign('id',$id);
            // 模板输出
            return view("Store/distanceintervalinfo");
        }
    }
    //删除距离区间
    public function distanceintervaldel(DistanceIntervalModel $distanceintervalmodel,Request $request){
        $distanceintervalmodel->data($request->param());
        if (empty($distanceintervalmodel->id)){
            $this->error("该距离筛选条件id不存在");
        }
        $id=$distanceintervalmodel->id;
        $distanceintervalinfo=$distanceintervalmodel->where('distance_interval_id='.$id)->find();
        if (empty($distanceintervalinfo)){
            $this->error("找不到该筛选条件");
        }
        if (!$distanceintervalinfo->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除距离筛选条件:id为$id","new_distance_interval",$id);
        $this->success("删除成功");
    }

    /**
     * 店铺预约取消原因列表
     *
     * @param StoreReserveReasonModel $storeReserveReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function reserveReasonList(StoreReserveReasonModel $storeReserveReasonModel,Request $request)
    {
        $storeReserveReasonModel->data($request->param());
        if (!empty($storeReserveReasonModel->show_count)){
            $show_count = $storeReserveReasonModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($storeReserveReasonModel->orderBy)){
            $orderBy = $storeReserveReasonModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeReserveReasonModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeReserveReasonModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $ReserveReasonList = $storeReserveReasonModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $ReserveReasonList->render();
        // 模板变量赋值
        $this->assign('reserveReasonList', $ReserveReasonList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('reserveReasonList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_reserve_reason_list");
    }

    /**
     * 添加店铺预约取消原因
     *
     * @param StoreReserveReasonModel $storeReserveReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function reserveReasonAdd(StoreReserveReasonModel $storeReserveReasonModel, Request $request)
    {
        $reserveReasonInfo= $request->param();
        //如果是提交
        if(!empty($reserveReasonInfo['is_ajax'])){
            $result = $storeReserveReasonModel->create($reserveReasonInfo);
            if($result){
                $reserve_id = $storeReserveReasonModel->getLastInsID();
                $this->setAdminUserLog("新增","添加店铺预约取消原因：id为" . $reserve_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Store/store_reserve_reason_info");
        }
    }

    /**
     * 编辑店铺预约取消原因
     *
     * @param StoreReserveReasonModel $reserveReasonModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function reserveReasonEdit(StoreReserveReasonModel $reserveReasonModel, Request $request){
        $reserveReasonInfo= $request->param();
        //如果是提交
        if(!empty($reserveReasonInfo['is_ajax'])){
            $reserveReason = $reserveReasonModel->where(["id"=>$reserveReasonInfo['id']])->find();
            if(!empty($reserveReason)){
                $upWhere['id'] = $reserveReasonInfo['id'];
                $result = $reserveReasonModel->update($reserveReasonInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑店铺预约取消原因：id为" . $reserveReasonInfo['id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("店铺预约取消原因不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $reserveReason = $reserveReasonModel->where(["id"=>$reserveReasonInfo['id']])->find();
            if(!empty($reserveReason)){
                $reserveReason = $reserveReason->toArray();
            }
            $this->assign('reserveReason', $reserveReason);
            // 模板输出
            return view("Store/store_reserve_reason_info");
        }
    }

    /**
     * 删除店铺预约取消原因
     *
     * @param StoreReserveReasonModel $reserveReasonModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function reserveReasonDel(StoreReserveReasonModel $reserveReasonModel, Request $request){
        $reserveReasonInfo= $request->param();
        $reserveReason = $reserveReasonModel->where(["id"=>$reserveReasonInfo['id']])->find();
        if(!empty($reserveReason)){
            $result = $reserveReasonModel->where(["id"=>$reserveReasonInfo['id']])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除店铺预约取消原因：id为" . $reserveReasonInfo['id']);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("店铺预约取消原因不存在，删除失败");
        }
    }

    /**
     * 权益保障列表
     *
     * @param StoreProtectModel $storeProtectModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function protectList(StoreProtectModel $storeProtectModel, Request $request)
    {
        $storeProtectModel->data($request->param());
        if (!empty($storeProtectModel->show_count)){
            $show_count = $storeProtectModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($storeProtectModel->orderBy)){
            $orderBy = $storeProtectModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($storeProtectModel->orderByUpOrDown)){
            $orderByUpOrDown = $storeProtectModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $protectList = $storeProtectModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $protectList->render();
        // 模板变量赋值
        $this->assign('protectList', $protectList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('protectList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_protect_list");
    }

    /**
     * 添加权益保障
     *
     * @param StoreProtectModel $storeProtectModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function protectAdd(StoreProtectModel $storeProtectModel, Request $request)
    {
        $storeProtectInfo= $request->param();
        //如果是提交
        if(!empty($storeProtectInfo['is_ajax'])){
            $result = $storeProtectModel->create($storeProtectInfo);
            if($result){
                $protect_id = $storeProtectModel->getLastInsID();
                $this->setAdminUserLog("新增","添加权益保障：id为" . $protect_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Store/store_protect_info");
        }
    }

    /**
     * 编辑权益保障
     *
     * @param StoreProtectModel $storeProtectModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function protectEdit(StoreProtectModel $storeProtectModel, Request $request){
        $protectInfo= $request->param();
        //如果是提交
        if(!empty($protectInfo['is_ajax'])){
            $protect = $storeProtectModel->where(["id"=>$protectInfo['id']])->find();
            if(!empty($protect)){
                $upWhere['id'] = $protectInfo['id'];
                $result = $storeProtectModel->update($protectInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑权益保障：id为" . $protectInfo['id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("权益保障不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $protect = $storeProtectModel->where(["id"=>$protectInfo['id']])->find();
            if(!empty($protect)){
                $protect = $protect->toArray();
            }
            $this->assign('protect', $protect);
            // 模板输出
            return view("Store/store_protect_info");
        }
    }

    /**
     * 删除权益保障
     *
     * @param StoreProtectModel $storeProtectModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/11
     */
    public function protectDel(StoreProtectModel $storeProtectModel, Request $request){
        $protectInfo= $request->param();
        $protect = $storeProtectModel->where(["id"=>$protectInfo['id']])->find();
        if(!empty($protect)){
            $result = $storeProtectModel->where(["id"=>$protectInfo['id']])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除权益保障：id为" . $protectInfo['id']);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("权益保障不存在，删除失败");
        }
    }
    //店铺消息推送
    public function storePushMessage(StorePushMessageModel $storePushMessageModel, Request $request)
    {
        $storePushMessageModel->data($request->param());
        if (!empty($storePushMessageModel->show_count)){
            $show_count = $storePushMessageModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if (!empty($storePushMessageModel->clear_state)){
            $clear_state = $storePushMessageModel->clear_state-1;
            $where .= " and b.message_state = $clear_state ";
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
        $storeClearList = $storePushMessageModel
            ->field('b.*,s.store_name')
            ->alias('b')
            ->join('new_store s','b.store_id = s.store_id','left')
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
        $this->assign('where', $storePushMessageModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('protectList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_push_message");
    }
    //预约记录表
    public function reserveList(storeReserveModel $StoreReserveModel, Request $request){
        $StoreReserveModel->data($request->param());
        if (!empty($StoreReserveModel->show_count)){
            $show_count = $StoreReserveModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($StoreReserveModel->orderBy)){
            $orderBy = $StoreReserveModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($StoreReserveModel->orderByUpOrDown)){
            $orderByUpOrDown = $StoreReserveModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $where = '1 = 1';

        if(!empty($StoreReserveModel->store_name)){
            $store_name = $StoreReserveModel->store_name;
            $where .= " and s.store_name like '%" . $store_name . "%' ";
        }
        if(!empty($StoreReserveModel->reserve_state)){
            $where .= " and b.reserve_state = '" . $StoreReserveModel->reserve_state . " ' ";
        }
        $reserveList = $StoreReserveModel
            ->field('b.*,s.store_name')
            ->alias('b')
            ->join('new_store s','s.store_id = b.store_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $reserveList->appends($request->param())->render();

        // 模板变量赋值
        $this->assign('reserveList', $reserveList);
        $this->assign('orderBy', $orderBy);
        $this->assign('where', $StoreReserveModel->toArray());
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('reservelist');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Store/store_reserve_list");
    }

    /**
     * 店铺列表
     *
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/31
     */
    public function getStore(Request $request)
    {
        $storeModel = new StoreModel();
        $bannerInfo = $request->param();
        $store_list = array();
        $where = " disabled = 1 ";
        if(!empty($bannerInfo['store_name'])){
            $store_name = $bannerInfo['store_name'];
            $where .= " and store_name like '%" . $store_name . "%' ";
            $store_list = $storeModel->field('store_id,store_name')->where($where)->select();
        }
        $this->success("查找成功","",$store_list);
    }

    public function bannerInfo(StoreModel $storeModel, UploadService $uploadService, Request $request)
    {
        $banners = $request->param();
        $store_id = $banners['store_id'];
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
                $imgUrl  = '/images/Store/'.$store_id.'/';
                $result = $uploadService->uploadmore($info,$imgUrl);
                $store['store_banner_img'] = $result;
            }
            $upWhere['store_id'] = $store_id;
            $result = $storeModel->update($store,$upWhere);
            if($result){
                $this->setAdminUserLog("上传","上传店铺轮播图");
                $this->success("上传成功",null,$store_id);
            }else{
                $this->error("上传失败");
            }
        }else{
            $this->assign('bannerImage',$storeInfo->store_banner_img);
            $this->assign('store_id',$store_id);
            // 模板输出
            return view("Store/banner_info");
        }
    }
    //根据行业获取分类
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