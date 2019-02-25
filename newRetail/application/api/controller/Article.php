<?php
namespace app\api\controller;

use app\api\model\ArticleModel;
use app\api\model\ArticleTypeModel;
use app\api\model\UserFeedbackModel;
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
        $ArticleTypeList = $articleTypeModel->where(['disabled'=>1,'app_type'=>1])->order('sort_order','asc')->select();
        //-- 获取详情列表
        foreach($ArticleTypeList as &$v){
            $articleList = $articleModel->where(['cat_id'=>$v->article_type_id,'disabled'=>1])->select();
            $v->article_list = $articleList;
        }
        $phone = $this->getConfig('appHelpPhone');
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

    /**
     * 用户意见反馈
     *
     * @param Request $request
     * @param UserFeedbackModel $userFeedbackModel
     * @Author: guanyl
     * @Date: 2018/8/15
     */
    public function userFeedbackList(Request $request, UserFeedbackModel $userFeedbackModel)
    {
        $param = $request->param();
        $userFeedback = $userFeedbackModel->where(['user_id'=>$param['user_id']])->order('id','desc')->select();

        $this->jkReturn(1,'用户意见反馈列表',$userFeedback);
    }

    /**
     * 添加意见反馈
     *
     * @param Request $request
     * @param UserFeedbackModel $userFeedbackModel
     * @Author: guanyl
     * @Date: 2018/8/15
     */
    public function addUserFeedback(Request $request, UserFeedbackModel $userFeedbackModel){
        $param = $request->param();
        if(!empty($param['feedback_img'])){
            $param['feedback_img'] = urldecode($param['feedback_img']);
        }
        $res = $userFeedbackModel->create($param);
        if(!$res){
            $this->jkReturn(-1,'添加意见反馈失败','1');
        }
        $feedback_id = $userFeedbackModel->getLastInsID();
        $this->jkReturn(1,'添加意见反馈成功',$feedback_id);
    }

}
