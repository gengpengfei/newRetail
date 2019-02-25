<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/16
 * Time: 18:58
 */

namespace app\admin\controller;


use app\admin\model\OrderLogModel;
use app\admin\model\OrderMessageModel;
use app\admin\model\OrderModel;
use app\admin\model\OrderProModel;
use app\admin\model\OrderRefundLogModel;
use app\admin\model\OrderRefundModel;
use app\admin\model\ProActivityInfoModel;
use app\admin\model\ProActivityListModel;
use app\admin\model\ProActivityModel;
use app\admin\model\ProCommentModel;
use app\admin\model\ProductModel;
use app\admin\model\ProScoreIntervalModel;
use app\admin\model\RankModel;
use app\admin\model\ProCategoryModel;
use app\admin\model\RefundReasonModel;
use app\admin\model\ShippingModel;
use app\admin\model\ShippingPointModel;
use app\admin\model\UserModel;
use app\admin\model\UserScoreLogModel;
use app\admin\model\UsersModel;
use app\admin\service\UploadService;
use think\Request;
use think\Session;

class IntegralShop extends Common
{
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;
    /**
     * 商品列表
     *
     * @param ProductModel $productModel
     * @param ProCategoryModel $proCategoryModel
     * @param RankModel $rankModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/17
     */
    public function productList(ProductModel $productModel,RankModel $rankModel,  Request $request, ProCategoryModel $proCategoryModel)
    {
        $productModel->data($request->param());
        if (!empty($productModel->show_count)){
            $show_count = $productModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($productModel->keywords)){
            $keywords = $productModel->keywords;
            $where .= " and (p.pro_category_name like '%" . $keywords . "%' or b.pro_name like '%". $keywords . "%' or b.pro_desc like '%". $keywords . "%' or b.pro_keywords like '%". $keywords . "%')";
        }
        if (!empty($productModel->pro_category_id)) {
            $where .= " and b.pro_category_id = " . $productModel->pro_category_id;
        }
        if (!empty($productModel->is_push)) {
            $where .= " and b.is_push = " . $productModel->is_push;
        }
        if (!empty($productModel->rank_id)) {
            $where .= " and b.rank_id = " . $productModel->rank_id;
        }
        //排序条件
        if(!empty($productModel->orderBy)){
            $orderBy = $productModel->orderBy;
        }else{
            $orderBy = 'product_id';
        }
        if(!empty($productModel->orderByUpOrDown)){
            $orderByUpOrDown = $productModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $product_list = $productModel
            ->field('b.*,p.pro_category_name,r.rank_name')
            ->alias('b')
            ->join('new_pro_category p','p.pro_category_id = b.pro_category_id','left')
            ->join('new_rank r','r.rank_id = b.rank_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $product_list->render();

        $category_list = $proCategoryModel
            ->field('pro_category_id,pro_category_name')
            ->select();
        $rank_list = $rankModel
            ->field('rank_id,rank_name')
            ->select();
        $this->assign('rank_list', $rank_list);
        // 模板变量赋值
        $this->assign('category_list', $category_list);
        $this->assign('product_list', $product_list);
        $this->assign('where', $productModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('productList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/product_list");
    }

    /**
     * 添加商品
     *
     * @param ProductModel $productModel
     * @param ProCategoryModel $proCategoryModel
     * @param RankModel $rankModel
     * @param UploadService $uploadService
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/18
     */
    public function productAdd(ProductModel $productModel, ProCategoryModel $proCategoryModel, RankModel $rankModel, UploadService $uploadService, Request $request)
    {
        $productModel->data($request->param());
        $category_list = $proCategoryModel->paginate();
        $this->assign('category_list', $category_list);
        $rank_list = $rankModel->paginate();
        $this->assign('rank_list', $rank_list);
        //如果是提交
        if(!empty($productModel->is_ajax)){
            $file = $request->file('pro_img');
            if ($file) {
                $imgUrl  = '/images/product/'.$productModel->pro_category_id.'/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $productModel->pro_img = $result;
            }
            //获取轮播图
            $i = 0;
            $info = array();
            $myFile = $_FILES['myFile'];
            if (!empty($myFile['name'])  && !empty($myFile['name'][0])) {
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
                    $imgUrl  = '/images/product/'.$productModel->pro_category_id.'/';
                    $result = $uploadService->uploadmore($info, $imgUrl);
                    $productModel->pro_banner_img = $result;
                }
            }
            $productModel->pro_mes = $productModel->editorValue;
            //-- 判断商品编号和商品名称的唯一性
            $checkProName = $productModel->where('pro_name',$productModel->pro_name)->find();
            if(!empty($checkProName)){
                $this->error("商品名称已存在!");
            }
            $checkProCode = $productModel->where('pro_code',$productModel->pro_code)->find();
            if(!empty($checkProCode)){
                $this->error("商品编号已存在!");
            }
            $result = $productModel->allowField(true)->save($productModel);
            if($result){
                $product_id = $productModel->getLastInsID();
                $imgData = Session::get("uploadimg");
                $baseUrl = $this->getConfig('base_url');
                foreach ($imgData as $item){
                    //移动原图片
                    $image  = '.'.$item;
                    $ImgName = rand(100,999).time();
                    $imgUrl = './images/product/'.$product_id.'/';
                    $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);

                    if($newImgName){
                        $newImgName = str_replace("./","$baseUrl/",$newImgName);
                        //替换pro_mes
                        $productModel->pro_mes = str_replace($item,$newImgName,$productModel->pro_mes);
                        //删除原图
                        unlink($image);
                    }
                }
                $upWhere['product_id'] = $product_id;
                $productModel->allowField(true)->save($productModel,$upWhere);

                $this->setAdminUserLog("新增","添加商品：id为" . $product_id ,'IntegralShop',$product_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            //清除上传图片session
            Session::delete('uploadimg');
            return view("IntegralShop/product_info");
        }
    }

    /**
     * 编辑商品
     *
     * @param ProductModel $productModel
     * @param ProCategoryModel $proCategoryModel
     * @param RankModel $rankModel
     * @param UploadService $uploadService
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/18
     */
    public function productEdit(ProductModel $productModel, ProCategoryModel $proCategoryModel, RankModel $rankModel, UploadService $uploadService, Request $request)
    {
        $productInfo = $request->param();
        $category_list = $proCategoryModel->paginate();
        $this->assign('category_list', $category_list);
        $rank_list = $rankModel->paginate();
        $this->assign('rank_list', $rank_list);
        //如果是提交
        if(!empty($productInfo['is_ajax'])){
            $product = $productModel->where(["product_id"=>$productInfo['product_id']])->find();
            if(!empty($product)){
                $file = $request->file('pro_img');
                if ($file) {
                    $imgUrl  = '/images/product/'.$productInfo['pro_category_id'].'/';
                    $imgName = $this->imgName();
                    $result = $uploadService->upload($file,$imgUrl,$imgName);
                    $productInfo['pro_img'] = $result;
                }else{
                    unset($productInfo['pro_img']);
                }
                //获取轮播图
                $i = 0;
                $info = array();
                $myFile = $_FILES['myFile'];
                if (!empty($myFile['name'])  && !empty($myFile['name'][0])) {
                    if (is_string($myFile['name'])) { //单文件上传
                        $info[$i] = $myFile;
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
                        $imgUrl  = '/images/product/'.$productInfo['pro_category_id'].'/';
                        $result = $uploadService->uploadmore($info, $imgUrl);
                        $productInfo['pro_banner_img'] = $result;
                    }
                }
                $productInfo['pro_mes'] = $productInfo['editorValue'];
                $imgData = Session::get("uploadimg");
                $baseUrl = $this->getConfig('base_url');
                foreach ($imgData as $item){
                    //移动原图片
                    $image  = '.'.$item;
                    $ImgName = rand(100,999).time();
                    $imgUrl = './images/product/'.$productInfo['product_id'].'/';
                    $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                    if($newImgName){
                        $newImgName = str_replace("./","$baseUrl/",$newImgName);
                        //替换pro_mes
                        $productInfo['pro_mes'] = str_replace($item,$newImgName,$productInfo['pro_mes']);
                        //删除原图
                        unlink($image);
                    }
                }
                $upWhere['product_id'] = $productInfo['product_id'];
                $result = $productModel->update($productInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑商品：id为" . $productInfo['product_id'] ,'IntegralShop',$productInfo['product_id']);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("商品不存在，修改失败");
            }
        }else{
            //获取商品信息
            $product = $productModel->where(["product_id"=>$productInfo['product_id']])->find();
            if(!empty($product)){
                $product = $product->toArray();
            }
            $product['pro_mes'] = htmlspecialchars_decode($product['pro_mes']);
            //清除上传图片session
            Session::delete('uploadimg');
            $this->assign('bannerImage',$product['pro_banner_img']);
            $this->assign('product', $product);
            // 模板输出
            return view("IntegralShop/product_info");
        }
    }

    /**
     * 删除商品
     *
     * @param ProductModel $productModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/18
     */
    public function productDel(ProductModel $productModel ,Request $request){
        $productModel->data($request->param());
        if(empty($productModel->product_id)){
            $this->error("商品id不能为空");
        }
        $product_id=$productModel->product_id;
        /*if(is_array($product_id)){
            //多个删除
            foreach ($product_id as $v){
                $product = $productModel->where(["product_id"=>$v])->find();
                if(!empty($product)){
                    $result = $productModel->where(["product_id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除商品：id为" . $v );
                }else{
                    $this->error("广告不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }*/
        //单个删除
        $product = $productModel->where(["product_id"=>$product_id])->find();
        if(!empty($product)){
            $result = $productModel->destroy($product_id);
            if($result){
                $this->setAdminUserLog("删除","删除商品：id为" . $product_id ,'IntegralShop',$product_id);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("商品不存在，删除失败");
        }
    }

    /**
     * 回收站列表
     *
     * @param ProductModel $productModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/18
     */
    public function recycleProductList(ProductModel $productModel, Request $request)
    {
        $productModel->data($request->param());
        if (!empty($productModel->show_count)){
            $show_count = $productModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($productModel->keywords)){
            $keywords = $productModel->keywords;
            $where .= " and (p.pro_category_name like '%" . $keywords . "%' or b.pro_name like '%". $keywords . "%' or b.pro_desc like '%". $keywords . "%' or b.pro_keywords like '%". $keywords . "%')";
        }
        //排序条件
        if(!empty($productModel->orderBy)){
            $orderBy = $productModel->orderBy;
        }else{
            $orderBy = 'product_id';
        }
        if(!empty($productModel->orderByUpOrDown)){
            $orderByUpOrDown = $productModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $product_list = ProductModel::onlyTrashed()
            ->field('b.*,p.pro_category_name')
            ->alias('b')
            ->join('new_pro_category p','p.pro_category_id=b.pro_category_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $product_list->render();
        // 模板变量赋值
        $this->assign('product_list', $product_list);
        $this->assign('where', $productModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('productList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/recycle_product_list");
    }

    /**
     * 还原商品
     *
     * @param ProductModel $productModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/18
     */
    public function recycleProductEdit(ProductModel $productModel, Request $request)
    {
        $productInfo = $request->param();
        $upWhere['product_id'] = $productInfo['product_id'];
        $productInfo['delete_time'] = null;
        //$result = $productModel->allowField(true)->save($productInfo,$upWhere);
        $result = $productModel->update($productInfo,$upWhere);
        if($result){
            $this->setAdminUserLog("编辑","编辑商品：id为" . $productInfo['product_id'] ,'IntegralShop',$productInfo['product_id']);
            $this->success("编辑成功");
        }else{
            $this->error("编辑失败");
        }
    }

    /**
     * 商品评价列表
     *
     * @param ProCommentModel $proCommentModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/19
     */
    public function productCommentList(ProCommentModel $proCommentModel, Request $request)
    {
        $proCommentModel->data($request->param());
        if (!empty($proCommentModel->show_count)){
            $show_count = $proCommentModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($proCommentModel->keywords)){
            $keywords = $proCommentModel->keywords;
            $where .= " and (p.pro_name like '%" . $keywords . "%' or b.comment_cont like '%". $keywords . "%' or u.user_name like '%". $keywords . "%' )";
        }
        if (!empty($proCommentModel->has_img)) {
            $proCommentModel->has_img = $proCommentModel->has_img-1;
            $where .= " and b.has_img = " . $proCommentModel->has_img;
        }
        //排序条件
        if(!empty($proCommentModel->orderBy)){
            $orderBy = $proCommentModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($proCommentModel->orderByUpOrDown)){
            $orderByUpOrDown = $proCommentModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $product_list = $proCommentModel
            ->field('b.*,p.pro_name,u.user_name')
            ->alias('b')
            ->join('new_product p','p.product_id=b.product_id','left')
            ->join('new_users u','u.user_id=b.user_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $product_list->render();

        // 模板变量赋值
        $this->assign('product_list', $product_list);
        $this->assign('where', $proCommentModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('productList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/product_comment_list");
    }
    /*
     * explain:商品评价审核
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/27 19:00
     */
    public function productCommentDisabled(Request $request,ProCommentModel $proCommentModel)
    {
        $param = $request->param();
        $res = $proCommentModel->save(['disabled'=>$param['disabled']],['id'=>$param['id']]);
        if($res){
            $this->success('审核通过');
        }else{
            $this->error('网络延时,请稍后重试');
        }
    }
    /**
     * 删除商品评论
     *
     * @param ProCommentModel $proCommentModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/19
     */
    public function productCommentDel(ProCommentModel $proCommentModel,Request $request){
        $proCommentModel->data($request->param());
        if(empty($proCommentModel->id)){
            $this->error("商品id不能为空");
        }
        $id=$proCommentModel->id;

        if(is_array($id)){
            //多个删除
            foreach ($id as $v){
                $proComment = $proCommentModel->where(["id"=>$v])->find();
                if(!empty($proComment)){
                    unlink('.' . $proComment->comment_img);
                    $result = $proCommentModel->where(["id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除商品：id为" . $v ,'IntegralShop',$v);
                }else{
                    $this->error("广告不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }
        //单个删除
        $proComment = $proCommentModel->where(["id"=>$id])->find();
        if(!empty($proComment)){
            unlink('.' . $proComment->comment_img);
            $result = $proCommentModel->where(["id"=>$id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除商品：id为" . $id ,'IntegralShop',$id);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("商品不存在，删除失败");
        }
    }

    /**
     * 订单列表
     *
     * @param OrderModel $orderModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/20
     */
    public function orderList(OrderModel $orderModel,Request $request)
    {
        $orderModel->data($request->param());
        if (!empty($orderModel->show_count)){
            $show_count = $orderModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($orderModel->order_sn)){
            $order_sn = $orderModel->order_sn;
            $where .= " and order_sn like '%" . $order_sn . "%' ";
        }
        if (!empty($orderModel->user_name)) {
            $where .= " and user_name = " . $orderModel->user_name;
        }
        if(!empty($orderModel->order_state)){
            $where .= " and order_state = '" . $orderModel->order_state . " ' ";
        }
        //排序条件
        if(!empty($orderModel->orderBy)){
            $orderBy = $orderModel->orderBy;
        }else{
            $orderBy = 'order_id';
        }
        if(!empty($orderModel->orderByUpOrDown)){
            $orderByUpOrDown = $orderModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $order_list = $orderModel
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        if (!empty($order_list)) {
            foreach ($order_list as &$order) {
                $order->user_info = $order->user_name . '[TEL: ' . $order->address_mobile . ']' . '<br/>' . $order->address_cont;
                $order->status =  $this->order_state($order->order_state);
            }
        }
        // 获取分页显示
        $page = $order_list->render();
        // 模板变量赋值
        $this->assign('order_list', $order_list);
        $this->assign('where', $orderModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('orderList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/order_list");
    }

    /**
     * 订单详情
     *
     * @param OrderProModel $orderProModel
     * @param ShippingModel $shippingModel
     * @param ShippingPointModel $shippingPointModel
     * @param OrderRefundModel $orderRefundModel
     * @param OrderModel $orderModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/24
     */
    public function orderDetail(OrderProModel $orderProModel, ShippingModel $shippingModel, ShippingPointModel $shippingPointModel, OrderModel $orderModel,OrderRefundModel $orderRefundModel,Request $request){
        $orderInfo = $request->param();

        //获取商品信息
        $order = $orderModel->where(["order_id"=>$orderInfo['order_id']])->find();
        if(!empty($order)){
            $order = $order->toArray();
        }
        $order['order_state_name'] = $this->order_state($order['order_state']);
        $order['shipping_name'] = $this->shipping_name($order['shipping_id']);
        $order['point_name'] = $this->point_name($order['point_id']);

        $orderPro = $orderProModel
            ->field('b.*,p.pro_stock,o.*')
            ->alias('b')
            ->join('new_product p','p.product_id=b.product_id','left')
            ->join('new_order o','o.order_id=b.order_id','left')
            ->where(["b.order_id"=>$orderInfo['order_id']])
            ->select();
        if ($orderPro) {
            foreach ($orderPro as &$orderP){
                $orderP->all_score = $orderP->pro_score * $orderP->pro_num;
                $orderRefund = $orderRefundModel->where('order_pro_id = ' . $orderP->order_pro_id . ' and order_id = ' . $orderP->order_id)->find();
                if (!empty($orderRefund)) {
                    if ($orderRefund['is_refund_pro'] == 1) {
                        $state = $this->refund_state($orderRefund->refund_state);
                        $orderP->refundState = '退款退货('.$state.')';
                    }else{
                        $orderP->refundState = '仅退款('.$state.')';
                    }
                }else{
                    $orderP->refundState = '正常';
                    $orderP->refundState = $this->order_state($orderP->order_state);
                }
            }
        }
        $shippingInfo = $shippingModel->where(['disabled'=>1])->select();
        $shippingPointInfo = $shippingPointModel->where(['disabled'=>1])->select();
        $orderLog = $this->orderLog($orderInfo['order_id']);
        $orderMessage = $this->orderMessage($orderInfo['order_id']);

        if ($orderInfo['is_update'] == 1 && $order['order_state'] == 'Q03') {
            $is_update = 0;
            $this->success("编辑成功");
        }elseif ($order['order_state'] == 'Q03') {
            $is_update = 1;
        }
        $this->assign('input_state', $is_update);
        $this->assign('orderMessage', $orderMessage);
        $this->assign('orderLog', $orderLog);
        $this->assign('shippingInfo', $shippingInfo);
        $this->assign('shippingPointInfo', $shippingPointInfo);
        $this->assign('order', $order);
        $this->assign('goods_list', $orderPro);
        // 模板输出
        return view("IntegralShop/order_info");
    }

    public function orderLog($order_id,$status='')
    {
        $orderLogModel = new OrderLogModel();
        $where = "order_id = " . $order_id;
        if (!empty($status)) {
            $where .= " and log_type = '$status'";
        }
        $orderLog = $orderLogModel->where($where)->order('create_time DESC')->select();
        return $orderLog;
    }

    public function orderMessage($order_id)
    {
        $orderMessageModel = new OrderMessageModel();
        $orderMessage = $orderMessageModel->where(["order_id"=>$order_id])->order('create_time DESC')->select();
        return $orderMessage;
    }

    /**
     * 获取物流公司
     *
     * @param $shipping_id
     * @return mixed
     * @Author: guanyl
     * @Date: 2018/4/25
     */
    public function shipping_name($shipping_id)
    {
        $shippingModel = new ShippingModel();
        $shippingInfo = $shippingModel->where(["shipping_id"=>$shipping_id,'disabled'=>1])->find();
        if(!empty($shippingInfo)){
            $shippingInfo = $shippingInfo->toArray();
        }
        return $shippingInfo['shipping_name'];
    }

    /**
     * 获取物流配送点
     *
     * @param $point_id
     * @return mixed
     * @Author: guanyl
     * @Date: 2018/4/25
     */
    public function point_name($point_id)
    {
        $shippingPointModel = new ShippingPointModel();
        $shippingPointInfo = $shippingPointModel->where(["point_id"=>$point_id,'disabled'=>1])->find();
        if(!empty($shippingPointInfo)){
            $shippingPointInfo = $shippingPointInfo->toArray();
        }
        return $shippingPointInfo['point_name'];
    }

    /**
     * 订单一键发货
     *
     * @param OrderModel $orderModel
     * @param ProductModel $productModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/24
     */
    public function orderEdit(OrderModel $orderModel,OrderRefundModel $orderRefundModel,Request $request)
    {
        $orderInfo = $request->param();
        $upWhere['order_id'] = $orderInfo['order_id'];
        //订单状态改为待收货状态
        $orderInfo['order_state'] = 'Q03';
        $orderInfo['shipping_time'] = date('Y-m-d H:i:s');
        //-- 查询是否有待处理退款商品
        $count = $orderRefundModel->where("order_id={$orderInfo['order_id']} and refund_state<>'W05' and refund_state<>'W06'")->count();
        if($count>0){
            $this->error("该订单商品有退款请求未处理,请先处理退款请求!",null,$orderInfo['order_id']);
        }
        $result = $orderModel->update($orderInfo,$upWhere);
        if($result){
            if ($orderInfo['action_note']) {
                $this->setOrderMessage($orderInfo['action_note'] ,$orderInfo['order_id']);
            }
            $this->setOrderLog("发货","一键发货" ,$orderInfo['order_id'],'IntegralShop', $orderInfo['order_id']);
            $this->success("编辑成功",null,$orderInfo['order_id']);
        }else{
            $this->error("编辑失败",null,$orderInfo['order_id']);
        }
    }

    /**
     * 商品减库存
     *
     * @param $order_id
     * @Author: guanyl
     * @Date: 2018/4/27
     */
    public function pro_stock($order_id)
    {
        $productModel = new ProductModel();
        $orderProModel = new OrderProModel();
        $orderProList = $orderProModel->where(["order_id"=>$order_id])->select();
        foreach ($orderProList as $orderPro) {
            $upWhere['product_id'] = $orderPro['product_id'];
            $product['pro_stock'] = 'pro_stock-' . $orderPro['pro_num'];
            $productModel->update($product,$upWhere);
        }
    }

    /**
     * 编辑收货地址
     *
     * @param OrderModel $orderModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/25
     */
    public function collectGoods(OrderModel $orderModel,Request $request)
    {
        $orderInfo = $request->param();
        //如果是提交
        if(!empty($orderInfo['is_ajax'])){
            $order = $orderModel->where(["order_id"=>$orderInfo['order_id']])->find();
            if(!empty($order)){
                $upWhere['order_id'] = $orderInfo['order_id'];
                $result = $orderModel->allowField(true)->save($orderInfo,$upWhere);
                if($result){
                    $this->setOrderLog("编辑","编辑收货地址：id为" . $orderInfo['order_id'],$orderInfo['order_id'],'IntegralShop', $orderInfo['order_id']);
                    $this->success("编辑成功",null,$orderInfo['order_id']);
                }else{
                    $this->error("编辑失败",null,$orderInfo['order_id']);
                }
            }else{
                $this->error("订单不存在，修改失败");
            }
        }else {
            //获取商品信息
            $order = $orderModel->where(["order_id" => $orderInfo['order_id']])->find();
            if (!empty($order)) {
                $order = $order->toArray();
            }
            $this->assign('order', $order);
            // 模板输出
            return view("IntegralShop/collect_goods");
        }
    }

    /**
     * 退款列表
     *
     * @param OrderRefundModel $orderRefundModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/26
     */
    public function refundList(OrderRefundModel $orderRefundModel,Request $request)
    {
        $orderRefundModel->data($request->param());
        if (!empty($orderRefundModel->show_count)){
            $show_count = $orderRefundModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($orderRefundModel->order_sn)){
            $order_sn = $orderRefundModel->order_sn;
            $where .= " and b.order_sn like '%" . $order_sn . "%' ";
        }
        if (!empty($orderRefundModel->user_name)) {
            $where .= " and o.user_name = " . $orderRefundModel->user_name;
        }
        if(!empty($orderRefundModel->refund_state)){
            $where .= " and refund_state = '" . $orderRefundModel->refund_state . " ' ";
        }

        //排序条件
        if(!empty($orderRefundModel->orderBy)){
            $orderBy = $orderRefundModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($orderRefundModel->orderByUpOrDown)){
            $orderByUpOrDown = $orderRefundModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $order_list = $orderRefundModel
            ->field('b.*,o.user_name,o.address_mobile,o.address_cont')
            ->alias('b')
            ->join('new_order o','o.order_id=b.order_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        if (!empty($order_list)) {
            foreach ($order_list as &$order) {
                $order->user_info = $order->user_name . '[TEL: ' . $order->address_mobile . ']' . '<br/>' . $order->address_cont;
                $order->status =  $this->refund_state($order->refund_state);
            }
        }
        // 获取分页显示
        $page = $order_list->render();
        // 模板变量赋值
        $this->assign('order_list', $order_list);
        $this->assign('where', $orderRefundModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('orderList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/refund_list");
    }

    /**
     * 退款详情
     *
     * @param OrderProModel $orderProModel
     * @param OrderRefundModel $orderRefundModel
     * @param OrderModel $orderModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/27
     */
    public function refundDetail(OrderProModel $orderProModel, OrderRefundModel $orderRefundModel,OrderModel $orderModel, Request $request)
    {
        $orderInfo = $request->param();
        //获取商品信息
        $orderRefund = $orderRefundModel
            ->field('b.*,o.create_time as order_time,o.shipping_time,o.shipping_sn as order_shipping_sn,o.address_mobile,o.user_name,r.reason_desc')
            ->alias('b')
            ->join('new_order o','o.order_id = b.order_id','left')
            ->join('new_refund_reason r','r.reason_id = b.reason_id','left')
            ->where(["id"=>$orderInfo['refund_id']])
            ->find();
        $orderRefund->all_score = sprintf('%.2f',$orderRefund->pro_score*$orderRefund->pro_num);
        $orderRefund->refundStatus = '仅退款（无需退货）';
        if ($orderRefund->is_refund_pro == 1) {
            $orderRefund->refundStatus = '退款退货';
        }
        $orderLog = $this->orderLog($orderRefund->order_id,'退款');
        $orderMessage = $this->orderMessage($orderRefund->order_id);
        $this->assign('orderMessage', $orderMessage);
        $this->assign('orderLog', $orderLog);
        $this->assign('orderRefund', $orderRefund);
        // 模板输出
        return view("IntegralShop/refund_info");
    }

    /**
     * 退款编辑
     *
     * @param OrderModel       $orderModel
     * @param OrderRefundModel $orderRefundModel
     * @param OrderProModel $orderProModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/27
     */
    public function refundEdit(OrderProModel $orderProModel,OrderModel $orderModel,OrderRefundModel $orderRefundModel,Request $request,UserModel $userModel,UserScoreLogModel $userScoreLogModel)
    {
        $orderRefund = $request->param();
        $orderRefundInfo = $orderRefundModel->where(["id"=>$orderRefund['refund_id']])->find();
        //-- 开启事物
        $orderRefundModel->startTrans();
        if(!$orderRefundModel->update($orderRefund,['id'=>$orderRefund['refund_id']])){
            $orderRefundModel->rollback();
            $this->error("编辑失败",null,$orderRefund['refund_id']);
        }
        //-- 退积分
        if ($orderRefund['refund_state'] == 'W05'){
            $userId = $orderRefundInfo->user_id;
            if(!$userModel->where(['user_id'=>$userId])->setInc('user_score',$orderRefundInfo->refund_price)){
                $orderRefundModel->rollback();
                $this->error("退款失败",null,$orderRefund['refund_id']);
            }
            $userScoreInfo['user_id'] = $userId;
            $userScoreInfo['score'] = $orderRefundInfo->refund_price;
            $userScoreInfo['desc'] = "订单退款:" . $orderRefundInfo->order_sn;
            $userScoreLogModel->create($userScoreInfo);
        }
        if(!$this->editRefundLog($orderRefund,$orderRefundInfo)){
            $orderRefundModel->rollback();
            $this->error("写入协商记录失败",null,$orderRefund['refund_id']);
        }
        $this->setOrderLog("退款","订单商品" . $this->refund_state($orderRefund['refund_state']) ,$orderRefund['order_id'],'IntegralShop', $orderRefund['refund_id']);
        if ($orderRefund['action_note'] || $orderRefund['refund_address']) {
            $this->setOrderMessage($orderRefund['action_note'] . '退货地址：' . $orderRefund['refund_address'] ,$orderRefund['order_id']);
        }
        //订单状态修改
        $orderProList = $orderProModel->where(["order_id"=>$orderRefund['order_id']])->select();
        $orderRefundList = $orderRefundModel->where(["order_id"=>$orderRefund['order_id'],'refund_state'=>'W05'])->select();
        if (count($orderProList) == count($orderRefundList)){
            if(!$orderModel->update(['order_state'=>'Q05'],['order_id'=>$orderRefund['order_id']])){
                $orderRefundModel->rollback();
                $this->error("订单状态编辑失败",null,$orderRefund['refund_id']);
            }
        }
        $orderRefundModel->commit();
        $this->success("编辑成功",null,$orderRefund['refund_id']);

    }

    /**
     * 写入协商记录
     *
     * @param $orderRefund
     * @param $refund
     * @Author: guanyl
     * @Date: 2018/5/7
     */
    public function editRefundLog($orderRefund,$refund)
    {
        $orderRefundLogModel = new OrderRefundLogModel();
        if ($refund['is_refund_pro'] == 1 && $orderRefund['refund_state'] == 'W04') {
            return true;
        }
        //-- 写入协商记录
        if ($orderRefund['refund_state'] == 'W06') {
            $log['consult_title'] = '商家拒绝售后申请';
            $log['consult_cont'] = '经过与您协商，取消售后';
        } elseif ($orderRefund['refund_state'] == 'W05') {
            $log['consult_title'] = '退款成功';
            $log['consult_cont'] = '退款' . $refund['refund_price'] . '积分';
        } elseif($orderRefund['refund_state'] == 'W04'){
            $log['consult_title'] = '商家同意仅退款';
            $log['consult_cont'] = '经过与您协商，同意退款' . $refund['refund_price'] . '积分，不需要退货';
        } elseif($orderRefund['refund_state'] == 'W02'){
            $log['consult_title'] = '商家同意退款退货';
            $log['consult_cont'] = '经过与您协商，同意退款' . $refund['refund_price'] . '积分,需要退货';
            $log['consult_address'] = empty(Request()->param('refund_address'))?"":Request()->param('refund_address');
        }
        $log['order_pro_id'] = $refund['order_pro_id'];
        $log['order_id'] = $refund['order_id'];
        $log['order_sn'] = $refund['order_sn'];
        $log['consult_name'] = '商家';
        if(!$orderRefundLogModel->create($log)){
            return false;
        }
        return true;
    }

    /**
     * 订单状态
     *
     * @param $order_state
     * @return string
     * @Author: guanyl
     * @Date: 2018/4/20
     */
    public function order_state($order_state)
    {
        switch ($order_state){
            case 'Q01':
                return '待付款';
                break;
            case 'Q02':
                return '待发货';
                break;
            case 'Q03':
                return '待收货';
                break;
            case 'Q04':
                return '已完成';
                break;
            case 'Q05':
                return '已退款';
                break;
            case 'Q06':
                return '已关闭';
                break;
        }
    }

    /**
     * 退款状态
     *
     * @param $refund_state
     * @return string
     * @Author: guanyl
     * @Date: 2018/4/26
     */
    public function refund_state($refund_state)
    {
        switch ($refund_state){
            case 'W01':
                return '退款申请中';
                break;
            case 'W02':
                return '待买家退货';
                break;
            case 'W03':
                return '待卖家收货';
                break;
            case 'W04':
                return '待退款';
                break;
            case 'W05':
                return '退款完成';
                break;
            case 'W06':
                return '退款关闭';
                break;
        }
    }
    /**
     * 订单评价状态
     *
     * @param $comment_state
     * @return string
     * @Author: guanyl
     * @Date: 2018/4/20
     */
    public function comment_state($comment_state)
    {
        switch ($comment_state){
            case 'C01':
                return '未评价';
                break;
            case 'C02':
                return '已评价';
                break;
        }
    }
    /**
     * 筛选条件列表
     *
     * @param ProScoreIntervalModel $proScoreIntervalModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/8
     */
    public function scoreIntervalList(ProScoreIntervalModel $proScoreIntervalModel, Request $request)
    {
        $proScoreIntervalModel->data($request->param());
        if (!empty($proScoreIntervalModel->show_count)){
            $show_count = $proScoreIntervalModel->show_count;
        }else{
            $show_count = 10;
        }

        //排序条件
        if(!empty($proScoreIntervalModel->orderBy)){
            $orderBy = $proScoreIntervalModel->orderBy;
        }else{
            $orderBy = 'score_interval_id';
        }
        if(!empty($proScoreIntervalModel->orderByUpOrDown)){
            $orderByUpOrDown = $proScoreIntervalModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $scoreIntervalList = $proScoreIntervalModel
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        // 获取分页显示
        $page = $scoreIntervalList->render();
        // 模板变量赋值
        $this->assign('scoreIntervalList', $scoreIntervalList);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('scoreIntervalList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/score_interval_list");
    }

    /**
     * 添加筛选条件
     *
     * @param ProScoreIntervalModel $proScoreIntervalModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/8
     */
    public function scoreIntervalAdd(ProScoreIntervalModel $proScoreIntervalModel, Request $request)
    {
        $proScoreIntervalInfo= $request->param();
        //如果是提交
        if(!empty($proScoreIntervalInfo['is_ajax'])){
            $result = $proScoreIntervalModel->create($proScoreIntervalInfo);
            if($result){
                $score_interval_id = $proScoreIntervalModel->getLastInsID();
                $this->setAdminUserLog("新增","添加退货原因：id为" . $score_interval_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("IntegralShop/score_interval_info");
        }
    }

    /**
     * 编辑筛选条件
     *
     * @param ProScoreIntervalModel $proScoreIntervalModel
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/5/8
     */
    public function scoreIntervalEdit(ProScoreIntervalModel $proScoreIntervalModel, Request $request){
        $proScoreIntervalInfo= $request->param();
        //如果是提交
        if(!empty($proScoreIntervalInfo['is_ajax'])){
            $proScoreInterval = $proScoreIntervalModel->where(["score_interval_id"=>$proScoreIntervalInfo['score_interval_id']])->find();
            if(!empty($proScoreInterval)){
                $upWhere['score_interval_id'] = $proScoreIntervalInfo['score_interval_id'];
                $result = $proScoreIntervalModel->update($proScoreIntervalInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑退款原因：id为" . $proScoreIntervalInfo['score_interval_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("退款原因不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $proScoreInterval = $proScoreIntervalModel->where(["score_interval_id"=>$proScoreIntervalInfo['score_interval_id']])->find();
            if(!empty($proScoreInterval)){
                $proScoreInterval = $proScoreInterval->toArray();
            }
            $this->assign('proScoreInterval', $proScoreInterval);
            // 模板输出
            return view("IntegralShop/score_interval_info");
        }
    }

    /**
     * 删除筛选条件
     *
     * @param ProScoreIntervalModel $proScoreIntervalModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/8
     */
    public function scoreIntervalDel(ProScoreIntervalModel $proScoreIntervalModel, Request $request){
        $proScoreIntervalInfo= $request->param();
        $proScoreInterval = $proScoreIntervalModel->where(["score_interval_id"=>$proScoreIntervalInfo['score_interval_id']])->find();
        if(!empty($proScoreInterval)){
            $result = $proScoreIntervalModel->where(["score_interval_id"=>$proScoreIntervalInfo['score_interval_id']])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除筛选条件：id为" . $proScoreIntervalInfo['score_interval_id']);
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("筛选条件不存在，删除失败");
        }
    }

    //分类列表
    public function categoryList(ProCategoryModel $proCategoryModel,Request $request){
        $proCategoryModel->data($request->param());
        //获取分页数
        if (!empty($proCategoryModel->show_count)){
            $show_count = $proCategoryModel->show_count;
        }else{
            $show_count = 10;
        }
        $where='1=1';
        if(!empty($proCategoryModel->keywords)){
            $keywords = $proCategoryModel->keywords;
            $where .= " and (pro_category_name like '%" . $keywords . "%' or pro_category_desc like '%".$keywords."%' or pro_category_keywords like '%".$keywords."%')";
        }
        //排序条件
        if(!empty($proCategoryModel->orderBy)){
            $orderBy = $proCategoryModel->orderBy;
        }else{
            $orderBy = 'pro_category_id';
        }
        if(!empty($proCategoryModel->orderByUpOrDown)){
            $orderByUpOrDown = $proCategoryModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $categoryList=$proCategoryModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count);
        // 获取分页显示
        $page = $categoryList->render();

        $this->assign('categoryList',$categoryList);
        $this->assign('page',$page);
        $this->assign('where', $proCategoryModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        // 模板输出
        return view("IntegralShop/category_list");
    }
    //添加分类
    public function categoryAdd(ProCategoryModel $proCategoryModel,UploadService $uploadService, Request $request)
    {
        $proCategoryInfo= $request->param();
        //如果是提交
        if(!empty($proCategoryInfo['is_ajax'])){
            $file = $request->file('pro_category_img');
            if ($file) {
                $imgUrl  = '/images/IntegralShop/category/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $proCategoryInfo['pro_category_img'] = $result;
            }
            $result = $proCategoryModel->create($proCategoryInfo);
            if($result){
                $score_interval_id = $proCategoryModel->getLastInsID();
                $this->setAdminUserLog("新增","添加分类：id为" . $score_interval_id );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            //获取分类列表
            $categoryList=$proCategoryModel->field(['pro_category_id','pro_category_name','grade'])->where('disabled =1 and grade = 1')->select()->toArray();
            if (!empty($categoryList)){
                foreach ($categoryList as $key =>$value){
                    $childCategory=$proCategoryModel->getChildCategory($value);
                    $categoryList[$key]['child']=$childCategory;
                }
            }
            $this->assign('categoryList',$categoryList);
            $this->assign('is_type',1);
            // 模板输出
            return view("IntegralShop/category_info");
        }
    }
    //编辑分类
    public function categoryEdit(ProCategoryModel $proCategoryModel,UploadService $uploadService,ProductModel $productModel, Request $request){
        $proCategoryInfo= $request->param();
        //如果是提交
        if(!empty($proCategoryInfo['is_ajax'])){
            $proCategory = $proCategoryModel->where(["pro_category_id"=>$proCategoryInfo['pro_category_id']])->find();
            if(!empty($proCategory)){
                $file = $request->file('pro_category_img');
                if ($file) {
                    $imgUrl  = '/images/IntegralShop/category/';
                    $imgName = $this->imgName();
                    $result = $uploadService->upload($file,$imgUrl,$imgName);
                    $proCategoryInfo['pro_category_img'] = $result;
                }else{
                    unset($proCategoryInfo['pro_category_img']);
                }
                if(isset($proCategoryInfo['disabled']) && $proCategoryInfo['disabled']==0){
                    //-- 判断分类下是否有商品
                    $productList = $productModel->where('pro_category_id='.$proCategoryInfo['pro_category_id'])->count();
                    if($productList>0){
                        $this->error("该分类下有商品 ,不能禁用!");
                    }
                }
                $upWhere['pro_category_id'] = $proCategoryInfo['pro_category_id'];
                $result = $proCategoryModel->update($proCategoryInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑分类：id为" . $proCategoryInfo['pro_category_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("分类不存在，修改失败");
            }
        }else{
            $proCategory = $proCategoryModel->where(["pro_category_id"=>$proCategoryInfo['pro_category_id']])->find();
            if(!empty($proCategory)){
                $proCategory = $proCategory->toArray();
            }
            $this->assign('proCategory', $proCategory);
            // 模板输出
            return view("IntegralShop/category_info");
        }
    }
    //删除分类
    public function categoryDel(ProCategoryModel $proCategoryModel,Request $request,ProductModel $productModel){
        $proCategoryInfo = $request->param();
        if(empty($proCategoryInfo['pro_category_id'])){
            $this->error("分类id不能为空");
        }
        $pro_category_id = $proCategoryInfo['pro_category_id'];
        $category_info = $proCategoryModel->where(["pro_category_id"=>$pro_category_id])->find();
        if(empty($category_info)){
            $this->error("该分类信息不存在");
        }
        $categoryList=$proCategoryModel->where('parent_id = '.$pro_category_id)->select();
        if (!empty($categoryList->toArray())){
            $this->error("该分类下有子分类 ,删除失败");
        }
        //-- 改分类下是否有商品
        $productList = $productModel->where('pro_category_id='.$pro_category_id)->count();
        if($productList>0){
            $this->error("该分类下有商品 ,禁止删除!");
        }
        if (!$category_info->delete()){
            $this->error("删除失败");
        }
        $this->setAdminUserLog("删除","删除分类：id为" . $pro_category_id ,'IntegralShop',$pro_category_id);
        $this->success('删除成功');
    }

    //专题活动
    public function activity(ProActivityModel $activityModel,Request $request)
    {
        $activityModel->data($request->param());
        if (!empty($activityModel->show_count)){
            $show_count = $activityModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($activityModel->keywords)){
            $keywords = $activityModel->keywords;
            $where .= " and (activity_name like '%" . $keywords . "%' or activity_desc like '%". $keywords . "%')";
        }
        $activity = $activityModel
            ->where($where)
            ->order('activity_id DESC')
            ->paginate($show_count);
        // 获取分页显示
        $page = $activity->render();
        // 模板变量赋值
        $this->assign('activity', $activity);
        $this->assign('where', $activityModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('activity');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/pro_activity_list");
    }
    //添加专题
    public function activityAdd(ProActivityModel $activityModel,UploadService $uploadService,Request $request){
        $activityModel->data($request->param());
        //如果是提交
        if(!empty($activityModel->is_ajax)){
            $file = $request->file('activity_img');
            if ($file) {
                $imgUrl  = '/images/IntegralShop/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityModel->activity_img = $result;
            }
            $activity = $activityModel->where(["activity_name"=>$activityModel->activity_name])->find();
            if(!empty($activity)){
                $this->error("活动分类名称已存在");
            }
            $result = $activityModel->allowField(true)->save($activityModel);
            if($result){
                $activity_id = $activityModel->getLastInsID();
                $this->setAdminUserLog("新增","添加活动分类：id为" . $activity_id . "-" . $activityModel->activity_name);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("IntegralShop/pro_activity_info");
        }
    }
    //编辑专题
    public function activityEdit(ProActivityModel $activityModel, UploadService $uploadService, Request $request){
        $activityModel->data($request->param());
        //如果是提交
        if(!empty($activityModel->is_ajax)){
            $file = $request->file('activity_img');
            if ($file) {
                $imgUrl  = '/images/IntegralShop/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityModel->activity_img = $result;
            }else{
                unset($activityModel->activity_img);
            }
            if(!empty($activityModel->position_name)){
                $activity = $activityModel->where(["activity_name"=>$activityModel->activity_name])->find();
                if(!empty($activity)){
                    $this->error("活动分类名称已存在");
                }
            }
            $activity = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
            if(!empty($activity)){
                $upWhere['activity_id'] = $activityModel->activity_id;
                $result = $activityModel->allowField(true)->save($activityModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑活动分类：id为" . $activityModel->activity_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("活动分类不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $activity = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
            if(!empty($activity)){
                $activity = $activity->toArray();
            }
            $this->assign('activity', $activity);
            // 模板输出
            return view("IntegralShop/pro_activity_info");
        }
    }
    //删除专题
    public function activityDel(ProActivityModel $activityModel,ProActivityListModel $activityListModel, Request $request){
        $activityModel->data($request->param());
        $activityList = $activityListModel->where(["activity_id"=>$activityModel->activity_id])->select()->toArray();
        if (!empty($activityList)) {
            $this->error("删除失败,此专题下还有活动，不允许删除");
        }
        $promotion = $activityModel->where(["activity_id"=>$activityModel->activity_id])->find();
        if(!empty($promotion)){
            unlink('.' . $promotion->activity_img);
            $result = $activityModel->where(["activity_id"=>$activityModel->activity_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除活动专题：id为" . $activityModel->activity_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("活动专题不存在，删除失败");
        }
    }
    //活动列表
    public function activityList(ProActivityListModel $activityListModel,Request $request)
    {
        $activityListModel->data($request->param());
        if (!empty($activityListModel->show_count)){
            $show_count = $activityListModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($activityListModel->activity_id)) {
            $this->error("活动参数错误");
        }
        $where = " 1=1 ";
        if(!empty($activityListModel->keywords)){
            $keywords = $activityListModel->keywords;
            $where .= " and (b.activity_list_name like '%" . $keywords . "%' or b.activity_list_desc like '%". $keywords . "%')";
        }
        if (!empty($activityListModel->activity_id)) {
            $activity_id = $activityListModel->activity_id;
            $where .= " and b.activity_id = " . $activity_id;
        }

        //排序条件
        if(!empty($activityListModel->orderBy)){
            $orderBy = $activityListModel->orderBy;
        }else{
            $orderBy = 'activity_list_id';
        }
        if(!empty($activityListModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityListModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }

        $activity_list = $activityListModel
            ->field('b.*,p.activity_name')
            ->alias('b')
            ->join('new_pro_activity p','p.activity_id=b.activity_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);

        // 获取分页显示
        $page = $activity_list->render();
        // 模板变量赋值
        $this->assign('activity_list', $activity_list);
        $this->assign('activity_id', $activityListModel->activity_id);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('where', $activityListModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('activityList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/activity_list");
    }
    //添加活动
    public function activityListAdd(ProActivityListModel $activityListModel,UploadService $uploadService,Request $request){
        $activityListModel->data($request->param());
        if (empty($activityListModel->activity_id)) {
            $this->error("活动参数错误");
        }
        $this->assign('activity_id', $activityListModel->activity_id);
        //如果是提交
        if(!empty($activityListModel->is_ajax)){
            $activityList = $activityListModel->where(["activity_list_name"=>$activityListModel->activity_list_name])->find();
            if(!empty($activityList)){
                $this->error("活动名称已存在");
            }
            $file = $request->file('activity_list_img');
            if ($file) {
                $imgUrl  = '/images/IntegralShop/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityListModel->activity_list_img = $result;
            }

            $result = $activityListModel->allowField(true)->save($activityListModel);
            if($result){
                $activity_list_id = $activityListModel->getLastInsID();
                $this->setAdminUserLog("新增","添加活动：id为" . $activity_list_id . "-" . $activityListModel->activity_list_name);
                $this->success("添加成功",null,$activityListModel->activity_id);
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("IntegralShop/activity_info");
        }
    }
    //编辑活动
    public function activityListEdit(ProActivityListModel $activityListModel,UploadService $uploadService, Request $request){
        $activityListModel->data($request->param());
        //如果是提交
        if(!empty($activityListModel->is_ajax)){
            $file = $request->file('activity_list_img');
            if ($file) {
                $imgUrl  = '/images/IntegralShop/activity/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $activityListModel->activity_list_img = $result;
            }else{
                unset($activityListModel->activity_list_img);
            }
            $activityList = $activityListModel->where(["activity_list_id"=>$activityListModel->activity_list_id])->find();
            if(!empty($activityList)){
                $upWhere['activity_list_id'] = $activityListModel->activity_list_id;
                $result = $activityListModel->allowField(true)->save($activityListModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑活动：id为" . $activityListModel->activity_list_id );
                    $this->success("编辑成功",null,$activityList->activity_id);
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("活动不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $activityList = $activityListModel->where(["activity_list_id"=>$activityListModel->activity_list_id])->find();
            if(!empty($activityList)){
                $activityList = $activityList->toArray();
            }
            $this->assign('activity_id', $activityList['activity_id']);
            $this->assign('activityList', $activityList);
            // 模板输出
            return view("IntegralShop/activity_info");
        }
    }
    //删除活动
    public function activityListDel(ProActivityListModel $activityListModel, ProActivityInfoModel $activityInfoModel,Request $request){
        $activityListModel->data($request->param());

        if(empty($activityListModel->activity_list_id)){
            $this->error("活动id不能为空");
        }
        $activity_list_id = $activityListModel->activity_list_id;

        $activityInfo = $activityInfoModel->where(["activity_list_id"=>$activity_list_id])->select()->toArray();
        if (!empty($activityInfo)) {
            $this->error("删除失败,此活动下还有商品，不允许删除");
        }

        $activityList = $activityListModel->where(["activity_list_id"=>$activity_list_id])->find();
        if(!empty($activityList)){
            unlink('.' . $activityList->activity_list_img);
            $result = $activityListModel->where(["activity_list_id"=>$activity_list_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除活动：id为" . $activity_list_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("活动不存在，删除失败");
        }

    }
    //商品活动列表
    public function goodsList(ProActivityInfoModel $activityInfoModel,ProActivityListModel $activityListModel,Request $request)
    {
        $activityInfoModel->data($request->param());
        if (!empty($activityInfoModel->show_count)){
            $show_count = $activityInfoModel->show_count;
        }else{
            $show_count = 10;
        }
        if (empty($activityInfoModel->activity_list_id)) {
            $this->error("活动参数错误");
        }
        $where = " 1=1 ";
        if(!empty($activityInfoModel->keywords)){
            $keywords = $activityInfoModel->keywords;
            $where .= " and (p.pro_name like '%" . $keywords . "%')";
        }
        if (!empty($activityInfoModel->activity_list_id)) {
            $where .= " and a.activity_list_id = " . $activityInfoModel->activity_list_id;
        }
        //排序条件
        if(!empty($activityInfoModel->orderBy)){
            $orderBy = $activityInfoModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($activityInfoModel->orderByUpOrDown)){
            $orderByUpOrDown = $activityInfoModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $activityInfo = $activityInfoModel
            ->field('a.*,b.activity_list_name,p.pro_name')
            ->alias('a')
            ->join('new_pro_activity_list b','a.activity_list_id = b.activity_list_id','left')
            ->join('new_product p','a.product_id = p.product_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        $activity = $activityListModel->where(['activity_list_id'=>$activityInfoModel->activity_list_id])->find();
        // 获取分页显示
        $page = $activityInfo->render();
        // 模板变量赋值
        $this->assign('activityInfo', $activityInfo);
        $this->assign('activity_id', $activity->activity_id);
        $this->assign('activity_list_id', $activityInfoModel->activity_list_id);
        $this->assign('where', $activityInfoModel->toArray());
        //权限按钮
        $action_code_list = $this->getChileAction('goodsList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("IntegralShop/goods_list");
    }
    //添加商品活动
    public function goodsAdd(ProActivityInfoModel $activityInfoModel,ProductModel $productModel,Request $request){
        $activityInfoModel->data($request->param());
        if (empty($activityInfoModel->activity_list_id)) {
            $this->error("活动参数错误");
        }
        $this->assign('activity_list_id', $activityInfoModel->activity_list_id);

        //如果是提交
        if(!empty($activityInfoModel->is_ajax)){
            $result = $activityInfoModel->allowField(true)->save($activityInfoModel);
            if($result){
                $id = $activityInfoModel->getLastInsID();
                $this->setAdminUserLog("新增","添加商品活动：id为" . $id);
                $this->success("添加成功",null,$activityInfoModel->activity_list_id);
            }else{
                $this->error("添加失败");
            }
        }else{
            /**
             * 店铺列表
             */
            $product_list = $productModel->field('product_id,pro_name')->where('disabled = 1')->select();
            $this->assign('product_list', $product_list);
            // 模板输出
            return view("IntegralShop/goods_info");
        }
    }
    // 编辑商品活动
    public function goodsEdit(ProActivityInfoModel $activityInfoModel,ProductModel $productModel, Request $request){
        $activityInfoModel->data($request->param());

        //如果是提交
        if(!empty($activityInfoModel->is_ajax)){
            $activityInfo = $activityInfoModel->where(["id"=>$activityInfoModel->id])->find();
            if(!empty($activityInfo)){
                $upWhere['id'] = $activityInfoModel->id;
                $result = $activityInfoModel->allowField(true)->save($activityInfoModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑商品：id为" . $activityInfoModel->id );
                    $this->success("编辑成功",null,$activityInfo->activity_list_id);
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("商品不存在，修改失败");
            }
        }else{
            //获取信息
            $activityInfo = $activityInfoModel->where(["id"=>$activityInfoModel->id])->find();
            /**
             * 商品列表
             */
            $product_list = $productModel->field('product_id,pro_name')->where('disabled = 1')->select();
            $this->assign('product_list', $product_list);
            $this->assign('activity_list_id', $activityInfo['activity_list_id']);
            $this->assign('activityInfo', $activityInfo);
            // 模板输出
            return view("IntegralShop/goods_info");
        }
    }
    //删除商品活动
    public function goodsDel(ProActivityInfoModel $activityInfoModel, Request $request){
        $activityInfoModel->data($request->param());
        if(empty($activityInfoModel->id)){
            $this->error("活动id不能为空");
        }
        $activity_Info_id = $activityInfoModel->id;
        if(is_array($activity_Info_id)){
            //多个删除
            foreach ($activity_Info_id as $v){
                $activity_InfoList = $activityInfoModel->where(["id"=>$v])->find();
                if(!empty($activity_InfoList)){
                    $result = $activityInfoModel->where(["id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除商品活动：id为" . $v );
                }else{
                    $this->error("商品活动不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }
        //单个删除
        $activityInfo = $activityInfoModel->where(["id"=>$activity_Info_id])->find();
        if(!empty($activityInfo)){
            $result = $activityInfoModel->where(["id"=>$activity_Info_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除商品活动：id为" . $activity_Info_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("商品活动不存在，删除失败");
        }
    }
    //获取商城商品
    public function getProduct(ProductModel $productModel,Request $request)
    {
        $productInfo = $request->param();
        $product_list = array();
        $where = " disabled = 1 ";
        if(!empty($productInfo['pro_name'])){
            $pro_name = $productInfo['pro_name'];
            $where .= " and pro_name like '%" . $pro_name . "%' ";
            $product_list = $productModel->field('product_id,pro_name')->where($where)->select();
        }
        $this->success("查找成功","",$product_list);
    }


}