<?php
namespace app\shop\controller;
use app\shop\service\UploadService;
use think\Session;

/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/4/16
 * Time: 17:51
 */
class Upload extends Common
{
    public function uploadImg(UploadService $uploadService)
    {
        $file=request()->file('upfile');
        $imgUrl='/images/zancun/'.date("Ymd",time()) . "/";
        $imgname = time();
        $result=$uploadService->upload($file,$imgUrl,$imgname);
        if ($result){
            $saveImgUrl = '';
            $imgArr = unserialize($result);
            foreach ($imgArr as $item){
                $saveImgUrl .= $item;
            }
            //记录暂存上传图片
            $imgData = empty(Session::get("uploadimg"))?array():Session::get("uploadimg");
            array_push($imgData,$saveImgUrl);
            Session::set("uploadimg",$imgData);
            $imgData = Session::get("uploadimg");
            $result = array();
            $result['state'] = 'SUCCESS';
            $result['url'] = $saveImgUrl;
            $result['title'] = $imgArr[1].$imgArr[2];
            $result['original'] = $imgArr[1].$imgArr[2];
            $result['type'] = $imgArr[2];
            $result['size'] = $file->size;
            print_r(json_encode($result));die;
        }
    }
}