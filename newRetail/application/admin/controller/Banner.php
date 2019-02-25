<?php
/**
 * Banner
 * User: guanyl
 * Date: 2018/4/8
 * Time: 10:46
 */

namespace app\admin\controller;
use app\admin\model\ActionModel;
use app\admin\model\BannerModel;
use app\admin\model\BannerPositionModel;
use app\admin\model\StoreModel;
use app\admin\model\StoreProModel;
use app\admin\service\UploadService;
use think\Image;
use think\Request;

class Banner extends Common
{
    use \app\api\traits\BuildParam;
    /**
     * 广告列表
     *
     * @param BannerModel $bannerModel
     * @param Request $request
     * @return \think\response\View
     */
    public function bannerList(BannerModel $bannerModel,  Request $request)
    {
        $bannerModel->data($request->param());
        if (!empty($bannerModel->show_count)){
            $show_count = $bannerModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($bannerModel->keywords)){
            $keywords = $bannerModel->keywords;
            $where .= " and (p.position_name like '%" . $keywords . "%' or b.banner_name like '%". $keywords . "%' or p.position_desc like '%" . $keywords ."%')";
        }
        //排序条件
        if(!empty($bannerModel->orderBy)){
            $orderBy = $bannerModel->orderBy;
        }else{
            $orderBy = 'banner_id';
        }
        if(!empty($bannerModel->orderByUpOrDown)){
            $orderByUpOrDown = $bannerModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $banner_list = $bannerModel
            ->alias('b')
            ->join('new_banner_position p','p.position_id=b.position_id','left')
            ->where($where)
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count);
        //echo $bannerModel->getLastSql();die;
        // 获取分页显示
        $page = $banner_list->render();
        // 模板变量赋值
        $this->assign('banner_list', $banner_list);
        $this->assign('where', $bannerModel->toArray());
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        //权限按钮
        $action_code_list = $this->getChileAction('bannerList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Banner/banner_list");
    }

    /**
     * 添加广告
     *
     * @param BannerModel $bannerModel
     * @param bannerPositionModel $bannerPositionModel
     * @param UploadService       $uploadService
     * @param Request $request
     * @return \think\response\View
     */
    public function bannerAdd(BannerModel $bannerModel, bannerPositionModel $bannerPositionModel, UploadService $uploadService, Request $request)
    {
        $bannerModel->data($request->param());
        $banner_Position_list = $bannerPositionModel->paginate();
        $this->assign('banner_Position_list', $banner_Position_list);
        //如果是提交
        if(!empty($bannerModel->is_ajax)){
            //$_FILES['image']
            $file = $request->file('image');
            if ($file) {
                $banner_Position = $bannerPositionModel->find($bannerModel->position_id);
                $imgUrl  = '/images/banner/'.$banner_Position->position_name.'/';
                $imgName = $this->imgName();
                $result = $uploadService->upload($file,$imgUrl,$imgName);
                $bannerModel->image = $result;
            }
            if (!empty($bannerModel->store_ad)) {
                $bannerModel->ad_url = $bannerModel->store_ad;
            }

            if (!empty($bannerModel->goods_ad)) {
                $bannerModel->ad_url = $bannerModel->goods_ad;
            }
            $result = $bannerModel->allowField(true)->save($bannerModel);
            if($result){
                $banner_id = $bannerModel->getLastInsID();
                $this->setAdminUserLog("新增","添加广告：id为" . $banner_id . "-" . $bannerModel->ad_url);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Banner/banner_info");
        }
    }

    /**
     * 编辑广告
     *
     * @param BannerModel $bannerModel
     * @param BannerPositionModel $bannerPositionModel
     * @param StoreModel $storeModel
     * @param StoreProModel $storeProModel
     * @param UploadService $uploadService
     * @param Request $request
     * @return \think\response\View
     * @Author: guanyl
     * @Date: 2018/4/8
     */
    public function bannerEdit(BannerModel $bannerModel, bannerPositionModel $bannerPositionModel,StoreProModel $storeProModel,StoreModel $storeModel,UploadService $uploadService, Request $request)
    {
        $bannerInfo = $request->param();
        $banner_Position_list = $bannerPositionModel->paginate();
        $this->assign('banner_Position_list', $banner_Position_list);
        //如果是提交
        if(!empty($bannerInfo['is_ajax'])){
            //$_FILES['image']
            $banner = $bannerModel->where(["banner_id"=>$bannerInfo['banner_id']])->find();
            if(!empty($banner)){
                $file = $request->file('image');
                if ($file) {
                    $banner_Position = $bannerPositionModel->find($bannerInfo['position_id']);
                    $imgUrl  = '/images/banner/'.$banner_Position->position_name.'/';
                    $imgName = $this->imgName();
                    $result = $uploadService->upload($file,$imgUrl,$imgName);
                    $bannerInfo['image'] = $result;
                }else{
                    unset($bannerInfo['image']);
                }
                if (!empty($bannerInfo['store_ad'])) {
                    $bannerInfo['ad_url'] = $bannerInfo['store_ad'];
                }

                if (!empty($bannerInfo['goods_ad'])) {
                    $bannerInfo['ad_url'] = $bannerInfo['goods_ad'];
                }
                $upWhere['banner_id'] = $bannerInfo['banner_id'];
                $result = $bannerModel->allowField(true)->save($bannerInfo,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑广告：id为" . $bannerInfo['banner_id'] );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }
            }else{
                $this->error("广告不存在，修改失败");
            }
        }else{
            //获取广告信息
            $banner = $bannerModel->where(["banner_id"=>$bannerInfo['banner_id']])->find();
            if(!empty($banner)){
                $banner = $banner->toArray();
                if ($banner['ad_url_type'] == 1 && !empty($banner['ad_url'])) {
                    $store_list = $storeModel->field('store_id,store_name')->where('store_id = ' . $banner['ad_url'])->find();
                    $banner['store_id'] = $store_list->store_id;
                    $banner['store_name'] = $store_list->store_name;
                }
                if ($banner['ad_url_type'] == 2 && !empty($banner['ad_url'])) {
                    $store_list = $storeProModel->field('store_pro_id,store_pro_name')->where('store_pro_id = ' . $banner['ad_url'])->find();
                    $banner['goods_id'] = $store_list->store_pro_id;
                    $banner['goods_name'] = $store_list->store_pro_name;
                }
            }
            $this->assign('banner', $banner);
            $this->assign('banner_id', $banner['banner_id']);
            // 模板输出
            return view("Banner/banner_info");
        }
    }

    /**
     * 删除广告
     *
     * @param BannerModel $bannerModel
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/4/8
     */
    public function bannerDel(BannerModel $bannerModel ,Request $request){
        $bannerModel->data($request->param());
        if(empty($bannerModel->banner_id)){
            $this->error("广告id不能为空");
        }
        $banner_id=$bannerModel->banner_id;
        if(is_array($banner_id)){
            //多个删除
            foreach ($banner_id as $v){
                $banner = $bannerModel->where(["banner_id"=>$v])->find();
                if(!empty($banner)){
                    unlink('.' . $banner->image);
                    $result = $bannerModel->where(["banner_id"=>$v])->delete();
                    if(!$result){
                        $this->error("删除失败");
                    }
                    $this->setAdminUserLog("删除","删除广告：id为" . $v );
                }else{
                    $this->error("广告不存在，删除失败");
                }
            }
            $this->success("删除成功");
        }
        //单个删除
        $banner = $bannerModel->where(["banner_id"=>$banner_id])->find();
        if(!empty($banner)){
            unlink('.' . $banner->image);
            $result = $bannerModel->where(["banner_id"=>$banner_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除广告：id为" . $banner_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("广告不存在，删除失败");
        }
    }

    /**
     * 广告位置列表
     *
     * @param ActionModel $actionModel
     * @param BannerPositionModel $bannerPositionModel
     * @return \think\response\View
     */
    public function bannerPositionList(ActionModel $actionModel,BannerPositionModel $bannerPositionModel)
    {
        if($_SESSION['AdminShowCount']){
            $showCount = $_SESSION['AdminShowCount'];
        }else{
            $_SESSION['AdminShowCount'] = 10;
            $showCount = $_SESSION['AdminShowCount'];
        }
        $banner_Position_list = $bannerPositionModel->paginate($showCount);
        // 获取分页显示
        $page = $banner_Position_list->render();
        // 模板变量赋值
        $this->assign('banner_Position_list', $banner_Position_list);
        //权限按钮
        $action_code_list = $this->getChileAction('bannerPositionList');
        $this->assign('page', $page);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Banner/banner_position_list");
    }

    /**
     * 添加广告位置
     *
     * @param BannerPositionModel $bannerPositionModel
     * @param Request $request
     * @return \think\response\View
     */
    public function bannerPositionAdd(BannerPositionModel $bannerPositionModel, Request $request)
    {
        $bannerPositionModel->data($request->param());
        //如果是提交
        if(!empty($bannerPositionModel->is_ajax)){
            $bannerPosition = $bannerPositionModel->where(["position_name"=>$bannerPositionModel->position_name])->find();
            if(!empty($bannerPosition)){
                $this->error("位置名称已存在");
            }
            $result = $bannerPositionModel->allowField(true)->save($bannerPositionModel);
            if($result){
                $position_id = $bannerPositionModel->getLastInsID();
                $this->setAdminUserLog("新增","添加广告位置：id为" . $position_id . "-" . $bannerPositionModel->position_name);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Banner/banner_position_info");
        }
    }

    /**
     * 编辑广告位置
     *
     * @param BannerPositionModel $bannerPositionModel
     * @param Request $request
     * @return \think\response\View
     */
    public function bannerPositionEdit(BannerPositionModel $bannerPositionModel, Request $request){
        $bannerPositionModel->data($request->param());
        //如果是提交
        if(!empty($bannerPositionModel->is_ajax)){
            if(!empty($bannerPositionModel->position_name)){
                $bannerPosition = $bannerPositionModel->where(["position_name"=>$bannerPositionModel->position_name])->find();
                if(!empty($bannerPosition)){
                    $this->error("位置名称已存在");
                }
            }
            $bannerPosition = $bannerPositionModel->where(["position_id"=>$bannerPositionModel->position_id])->find();
            if(!empty($bannerPosition)){
                $upWhere['position_id'] = $bannerPositionModel->position_id;
                $result = $bannerPositionModel->allowField(true)->save($bannerPositionModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑广告位置：id为" . $bannerPositionModel->position_id );
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("广告位置不存在，修改失败");
            }
        }else{
            //获取广告位置信息
            $bannerPosition = $bannerPositionModel->where(["position_id"=>$bannerPositionModel->position_id])->find();
            if(!empty($bannerPosition)){
                $bannerPosition = $bannerPosition->toArray();
            }
            $this->assign('bannerPosition', $bannerPosition);
            // 模板输出
            return view("Banner/banner_position_info");
        }
    }

    /**
     * 删除广告位置
     *
     * @param BannerPositionModel $bannerPositionModel
     * @param Request $request
     */
    public function bannerPositionDel(BannerPositionModel $bannerPositionModel, Request $request){
        $bannerPositionModel->data($request->param());
        $bannerPosition = $bannerPositionModel->where(["position_id"=>$bannerPositionModel->position_id])->find();
        if(!empty($bannerPosition)){
            $result = $bannerPositionModel->where(["position_id"=>$bannerPositionModel->position_id])->delete();
            if($result){
                $this->setAdminUserLog("删除","删除广告位置：id为" . $bannerPositionModel->position_id );
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }

        }else{
            $this->error("广告位置不存在，删除失败");
        }
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

    /**
     * 商品列表
     *
     * @param Request $request
     * @Author: guanyl
     * @Date: 2018/5/31
     */
    public function getGoods(Request $request)
    {
        $storeProModel = new StoreProModel();
        $bannerInfo = $request->param();
        $goods_list = array();
        $where2 = " disabled = 1 ";
        if(!empty($bannerInfo['goods_name'])){
            $goods_name = $bannerInfo['goods_name'];
            $where2 .= " and store_pro_name like '%" . $goods_name . "%' ";
            $goods_list = $storeProModel->field('store_pro_id,store_pro_name')->where($where2)->select();
        }
        $this->success("查找成功","",$goods_list);
    }


}