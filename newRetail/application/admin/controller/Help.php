<?php
namespace app\admin\controller;
use app\admin\model\ArticleModel;
use app\admin\model\ArticleTypeModel;
use app\admin\service\UploadService;
use think\Request;
use think\Session;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/13
 * Time: 19:27
 */
class Help extends Common
{
    use \app\api\traits\GetConfig;
    public function articleTypeList(Request $request,ArticleTypeModel $articleTypeModel) {
        $articleTypeModel->data($request->param());
        if (!empty($articleTypeModel->show_count)){
            $show_count = $articleTypeModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($articleTypeModel->keywords)){
            $keywords = $articleTypeModel->keywords;
            $where .= " and (article_type_name like '%" . $keywords . "%')";
        }

        //排序条件
        if(!empty($articleTypeModel->orderBy)){
            $orderBy = $articleTypeModel->orderBy;
        }else{
            $orderBy = 'article_type_id';
        }
        if(!empty($articleTypeModel->orderByUpOrDown)){
            $orderByUpOrDown = $articleTypeModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $article_type_list = $articleTypeModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $article_type_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('articletypelist');

        // 模板变量赋值
        $this->assign('article_type_list', $article_type_list);
        $this->assign('where', $articleTypeModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Help/article_type_list");
    }

    public function articleTypeAdd(ArticleTypeModel $articleTypeModel, Request $request)
    {
        $articleTypeModel->data($request->param());
        //如果是提交
        if(!empty($articleTypeModel->is_ajax)){
            $article_type_info = $articleTypeModel->where(["article_type_name"=>$articleTypeModel->article_type_name,'app_type'=>$articleTypeModel->app_type])->find();
            if(!empty($article_type_info)){
                $this->error("该类名称已存在");
            }

            $result = $articleTypeModel->allowField(true)->save($articleTypeModel);
            if($result){
                $cat_id = $articleTypeModel->getLastInsID();
                $this->setAdminUserLog("新增","添加文章分类：" . $cat_id . "-" . $articleTypeModel->article_type_name,$articleTypeModel->table,$cat_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            // 模板输出
            return view("Help/article_type_info");
        }
    }

    public function articleTypeEdit(ArticleTypeModel $articleTypeModel, Request $request){
        $articleTypeModel->data($request->param());
        if(empty($articleTypeModel->article_type_id)){
            $this->error("类型id不能为空");
        }
        //如果是提交
        if(!empty($articleTypeModel->is_ajax)){
            if(!empty($articleTypeModel->article_type_name)){
                $user_info = $articleTypeModel->where(["article_type_name"=>$articleTypeModel->article_type_name,'app_type'=>$articleTypeModel->app_type])->where("article_type_id","neq",$articleTypeModel->article_type_id)->find();
                if(!empty($user_info)){
                    $this->error("该类型名称已存在");
                }
            }

            $article_type_info = $articleTypeModel->where(["article_type_id"=>$articleTypeModel->article_type_id])->find();
            if(!empty($article_type_info)){
                $upWhere['article_type_id'] = $articleTypeModel->article_type_id;
                $result = $articleTypeModel->allowField(true)->save($articleTypeModel,$upWhere);
                if($result){
                    $this->setAdminUserLog("编辑","编辑文章类型：" . $articleTypeModel->article_type_id ,$articleTypeModel->table,$articleTypeModel->article_type_id);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("该类型不存在，修改失败");
            }
        }else{
            //获取类型信息
            $article_type_info = $articleTypeModel->where(["article_type_id"=>$articleTypeModel->article_type_id])->find();
            if(!empty($article_type_info)){
                $article_type_info = $article_type_info->toArray();
            }
            $this->assign('article_type_info', $article_type_info);
            // 模板输出
            return view("Help/article_type_info");
        }
    }

    public function articleTypeDel(ArticleTypeModel $articleTypeModel, ArticleModel $articleModel,Request $request){
        $articleTypeModel->data($request->param());
        $article_type_id = $articleTypeModel->article_type_id;
        //-- 判断该类型下是否有文章
        $list = $articleModel->where("cat_id=$article_type_id")->count();
        if($list>0){
            $this->error("该分类下有文章,请先删除文章!");
        }
        $result = $articleTypeModel->destroy($article_type_id);
        if($result){
            $this->setAdminUserLog("删除","删除文章类型：" . $articleTypeModel->article_type_id ,$articleTypeModel->table,$articleTypeModel->article_type_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }
    public function articleTypeQueryEdit(ArticleTypeModel $articleTypeModel, Request $request){
        $articleTypeModel->data($request->param());
        if(empty($articleTypeModel->article_type_id)){
            $this->error("类型id不能为空");
        }else{
            $article_type_id_list = explode(",",$articleTypeModel->article_type_id);
        }

        foreach ($article_type_id_list as $key=>$item){
            $data = array();
            $data['article_type_id'] = $item;
            $data['disabled'] = $articleTypeModel->disabled;
            $data['update_time'] = date("Y-m-d H:i:s",time());
            $list[$key] = $data;
        }
        $result = $articleTypeModel->allowField(true)->saveAll($list);
        if($result){
            if($articleTypeModel->disabled == 0){
                $this->setAdminUserLog("编辑","批量停用文章类型：" . $articleTypeModel->article_type_id ,$articleTypeModel->table,$articleTypeModel->article_type_id);
            }else{
                $this->setAdminUserLog("编辑","批量启用文章类型：" . $articleTypeModel->article_type_id ,$articleTypeModel->table,$articleTypeModel->article_type_id );
            }
            $this->success("编辑成功");
        }else{
            $this->error("编辑失败");
        }

    }

    public function articleList(Request $request,ArticleModel $articleModel,ArticleTypeModel $articleTypeModel) {
        $articleModel->data($request->param());
        if (!empty($articleModel->show_count)){
            $show_count = $articleModel->show_count;
        }else{
            $show_count = 10;
        }
        $where = " 1=1 ";
        if(!empty($articleModel->keywords)){
            $keywords = $articleModel->keywords;
            $where .= " and (title like '%" . $keywords . "%' )";
        }
        if(!empty($articleModel->article_type_id)){
            $article_type_id = $articleModel->article_type_id;
            $where .= " and cat_id=$article_type_id ";
        }
        //排序条件
        if(!empty($articleModel->orderBy)){
            $orderBy = $articleModel->orderBy;
        }else{
            $orderBy = 'article_id';
        }
        if(!empty($articleModel->orderByUpOrDown)){
            $orderByUpOrDown = $articleModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $article_list = $articleModel
            ->alias('a')
            ->field('a.*,t.article_type_name,t.app_type')
            ->where($where)
            ->join('new_article_type t','t.article_type_id=a.cat_id','left')
            ->order($orderBy.' '.$orderByUpOrDown)
            ->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $article_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('articlelist');
        //-- 文章类型
        $articleTypeList = $articleTypeModel->where('disabled=1')->select();
        // 模板变量赋值
        $this->assign('article_list', $article_list);
        $this->assign('article_type_list', $articleTypeList);
        $this->assign('where', $articleModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Help/article_list");
    }

    public function articleAdd(ArticleModel $articleModel,ArticleTypeModel $articleTypeModel,UploadService $uploadService, Request $request)
    {
        $articleModel->data($request->param());
        //如果是提交
        if(!empty($articleModel->is_ajax)){
            $articleModel->content = $articleModel->editorValue;
            $result = $articleModel->allowField(true)->save($articleModel);
            if($result){
                $article_id = $articleModel->getLastInsID();
                $imgData = Session::get("uploadimg");
                $baseUrl = $this->getConfig('base_url');
                foreach ($imgData as $item){
                    //移动原图片
                    $image  = '.'.$item;
                    $ImgName = rand(100,999).time();
                    $imgUrl = './images/help/'.$article_id.'/';
                    $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                    if($newImgName){
                        $newImgName = str_replace("./","$baseUrl/",$newImgName);
                        //替换content
                        $articleModel->content = str_replace($item,$newImgName,$articleModel->content);
                    }
                }
                $upWhere['article_id'] = $article_id;
                $articleModel->allowField(true)->save($articleModel,$upWhere);
                $this->setAdminUserLog("新增","添加文章：" . $article_id . "-" . $articleModel->title,$articleModel->table,$article_id);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            //获取分类
            $cat_list = $articleTypeModel->where("disabled=1")->select()->toArray();
            $this->assign("cat_list",$cat_list);
            //清除上传图片session
            Session::delete('uploadimg');
            // 模板输出
            return view("Help/article_info");
        }
    }

    public function articleEdit(ArticleModel $articleModel,ArticleTypeModel $articleTypeModel,UploadService $uploadService,Request $request){
        $articleModel->data($request->param());
        if(empty($articleModel->article_id)){
            $this->error("文章id不能为空");
        }
        //如果是提交
        if(!empty($articleModel->is_ajax)){
            $article_info = $articleModel->where(["article_id"=>$articleModel->article_id])->find();
            if(!empty($article_info)){
                $upWhere['article_id'] = $articleModel->article_id;
                if($articleModel->editorValue??0){
                    $articleModel->content = $articleModel->editorValue;
                }
                $result = $articleModel->allowField(true)->save($articleModel,$upWhere);
                if($result){
                    $imgData = Session::get("uploadimg");
                    $baseUrl = $this->getConfig('base_url');
                    foreach ($imgData as $item){
                        //移动原图片
                        $image  = '.'.$item;
                        $ImgName = rand(100,999).time();
                        $imgUrl = './images/help/'.$articleModel->article_id.'/';
                        $newImgName = $uploadService->uploadImg($image,$imgUrl,$ImgName);
                        if($newImgName){
                            $newImgName = str_replace("./","$baseUrl/",$newImgName);
                            //替换content
                            $articleModel->content = str_replace($item,$newImgName,$articleModel->content);
                            //删除原图
                            unlink($image);

                        }
                    }
                    $articleModel->allowField(true)->save($articleModel,$upWhere);
                    $this->setAdminUserLog("编辑","编辑文章：" . $articleModel->article_id ,$articleModel->table,$articleModel->article_id);
                    $this->success("编辑成功");
                }else{
                    $this->error("编辑失败");
                }

            }else{
                $this->error("该文章不存在，修改失败");
            }
        }else{
            Session::delete('uploadimg');
            //获取分类
            $cat_list = $articleTypeModel->where("disabled=1")->select()->toArray();
            $this->assign("cat_list",$cat_list);
            //获取文章信息
            $article_info = $articleModel->where(["article_id"=>$articleModel->article_id])->find();
            if(!empty($article_info)){
                $article_info = $article_info->toArray();
            }
            $article_info['content'] = htmlspecialchars_decode($article_info['content']);
            $this->assign('article_info', $article_info);
            // 模板输出
            return view("Help/article_info");
        }
    }

    public function articleDel(ArticleModel $articleModel, Request $request){
        $articleModel->data($request->param());
        if(!empty($articleModel->article_id)){
            $article_id_list = explode(",",$articleModel->article_id);
        }

        $result = $articleModel->destroy($article_id_list);
        if($result){
            $this->setAdminUserLog("删除","删除文章：" . $articleModel->article_id ,$articleModel->table,$articleModel->article_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    public function articleQueryDel(ArticleModel $articleModel, Request $request){
        $articleModel->data($request->param());
        if(!empty($articleModel->article_id)){
            $article_id_list = explode(",",$articleModel->article_id);
        }

        $result = $articleModel->destroy($article_id_list);
        if($result){
            $this->setAdminUserLog("删除","批量删除文章：" . $articleModel->article_id ,$articleModel->table,$articleModel->article_id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    public function articleQueryEdit(ArticleModel $articleModel, Request $request){
        $articleModel->data($request->param());
        if(empty($articleModel->article_id)){
            $this->error("文章id不能为空");
        }else{
            $article_id_list = explode(",",$articleModel->article_id);
        }

        foreach ($article_id_list as $key=>$item){
            $data = array();
            $data['article_id'] = $item;
            $data['disabled'] = $articleModel->disabled;
            $data['update_time'] = date("Y-m-d H:i:s",time());
            $list[$key] = $data;
        }
        $result = $articleModel->allowField(true)->saveAll($list);
        if($result){
            if($articleModel->disabled == 0){
                $this->setAdminUserLog("编辑","批量停用文章：" . $articleModel->article_id ,$articleModel->table,$articleModel->article_id);
            }else{
                $this->setAdminUserLog("编辑","批量启用文章：" . $articleModel->article_id ,$articleModel->table,$articleModel->article_id );
            }
            $this->success("编辑成功");
        }else{
            $this->error("编辑失败");
        }

    }



}