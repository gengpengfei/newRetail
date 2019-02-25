<?php
namespace app\shopapi\controller;

use app\shopapi\model\ArticleModel;
use app\shopapi\model\ArticleTypeModel;
use think\Request;

class Article extends Common {
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;

    /*
     * explain:获取文章列表
     * params :null
     * authors:Mr.Geng
     * addTime:2018/4/3 13:40
     */
    public function articleTypeList(ArticleTypeModel $articleTypeModel,ArticleModel $articleModel)
    {
        $ArticleTypeList = $articleTypeModel->where(['disabled'=>1,'app_type'=>2])->order('sort_order','asc')->select();
        //-- 获取详情列表
        foreach($ArticleTypeList as &$v){
            $articleList = $articleModel->where(['cat_id'=>$v->article_type_id,'disabled'=>1])->select();
            $v->article_list = $articleList;
        }
        $phone = $this->getConfig('appStoreHelpPhone');
        $this->jkReturn(1,'文章分类列表',['article_list'=>$ArticleTypeList,'phone'=>$phone]);
    }

    /*
         * explain:获取文章详情
         * params :null
         * authors:Mr.Geng
         * addTime:2018/4/3 13:40
         */
    public function articleContent(Request $request,ArticleModel $articleModel){
        $articleId = $request->param('article_id')??$_GET['article_id'];
        $articleInfo = $articleModel->where(['article_id'=>$articleId])->find();
        $content = htmlspecialchars_decode(htmlspecialchars_decode($articleInfo->content));
        $this->assign('content',$content);
        return view("Article/article_content");
    }

}