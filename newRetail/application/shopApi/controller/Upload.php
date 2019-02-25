<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/5
 * Time: 14:31
 */

namespace app\shopapi\controller;

use think\Request;
class Upload extends Common
{
    use \app\shopapi\traits\BuildParam;
    use \app\shopapi\traits\GetConfig;

    /*
     * explain:上传图片
     * params :img_base64
     * authors:Mr.Geng
     * addTime:2018/4/9 15:58
     */
    public function uploadImg(Request $request)
    {
        header('Content-type:text/html;charset=utf-8');
        $param = $request->param();
        //-- 是否是单图上传
        $single = false;
        switch($param['type']){
            case 'orderComment':
                $imgUrl = '/images/orderComment/'.date('Ymd',time()).'/';
                break;
            case 'storeComment':
                $imgUrl = '/images/storeComment/'.date('Ymd',time()).'/';
                break;
            case 'orderRefund':
                $imgUrl = '/images/orderRefund/'.date('Ymd',time()).'/';
                break;
            case 'storeRefund':
                $imgUrl = '/images/storeRefund/'.date('Ymd',time()).'/';
                break;
            case 'header':
                $imgUrl = '/images/header/'.date('Ymd',time()).'/';
                $single = true;
                break;
            case 'storeAudit':
                $storeId = $request->param('store_id');
                $imgUrl = '/images/storeAudit/'.$storeId.'/';
                $single = true;
                break;
            case 'storeClose':
                $storeId = $request->param('store_id');
                $imgUrl = '/images/storeClose/'.$storeId.'/';
                break;
            case 'opinionImg':
                $storeId = $request->param('store_id');
                $imgUrl = '/images/opinionImg/'.$storeId.'/';
                break;
            default:
                $imgUrl = '/images/other/';
        }
        //-- 判断文件夹
        if(!file_exists('.'.$imgUrl)) $this->createFile($imgUrl);
        if(!file_exists('./small'.$imgUrl)) $this->createFile('/small'.$imgUrl);
        if(!file_exists('./thum'.$imgUrl)) $this->createFile('/thum'.$imgUrl);
        $imgRes = [];
        $fail = 0;
        if($param['source']=='file'){
            $files = $request->file('file');
            $data = $files->getMime();
            $base64_image_content[] = 'data:'.$data.';base64,'.base64_encode($files->openFile()->fread($files->getSize()));
        }else{
            $base64_image_content = $param['img_base64'];
        }
        foreach ($base64_image_content as $base64_image) {
            $imgArr = $this->upload($base64_image,$imgUrl);
            if($imgArr){
                $this->image($imgArr);
                $imgRes[] = $imgArr;
            }else{
                $fail++;
            }
        }
        $data = empty($imgRes)? [] : $single==true ? serialize($imgRes[0]) : serialize($imgRes);
        $msg = '文件上传成功';
        if($fail>0){
            $msg = $fail.'张图片上传失败';
        }
        $this->jkReturn(1,$msg,$data);
    }

    /*
     * explain:文件上传
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/9 16:12
     */
    protected function upload($base64_image,$imgUrl)
    {
        header('Content-type:text/html;charset=utf-8');
        $size = file_get_contents($base64_image);
        $size = strlen($size)/1024;
        $config = $this->getConfig('upload_size_limit');
        if($size>$config){
            return false;
        }
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
            $type = '.'.$result[2];
            $img = base64_decode(str_replace($result[1], '', $base64_image));
            $imgName = $this->imgName();
            $imgArr = [$imgUrl,$imgName,$type];
            if (file_put_contents('.'.$imgUrl.$imgName.$type,$img)){
                return $imgArr;
            }else{
                return false;
            }
        }
        return false;
    }
    
    /*
     * explain:图片处理
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/9 17:01
     */
    protected function image($imgArr)
    {
        $image = \think\Image::open('./'.$imgArr[0].$imgArr[1].$imgArr[2]);
        $width = $image->width();
        $height = $image->height();

        $thumWidth = $this->getConfig('thumb_width');
        $thumHeight = $thumWidth/$width*$height;

        $smallWidth = $this->getConfig('small_width');
        $smallHeight = $smallWidth/$width*$height;
        //-- 图片质量
        $imageQuality = $this->getConfig('image_quality');
        $image->thumb($smallWidth,$smallHeight)->save('./small/'.$imgArr[0].$imgArr[1].$imgArr[2],null,$imageQuality);
        $image->thumb($thumWidth,$thumHeight)->save('./thum/'.$imgArr[0].$imgArr[1].$imgArr[2],null,$imageQuality);
    }
    /*
     * explain:创建文件夹
     * params :
     * authors:Mr.Geng
     * addTime:2018/4/9 17:55
     */
    protected function createFile($file)
    {
        $fileArr = explode ( "/", $file );
        $file_add = "";
        for($i = 1; $i < count ($fileArr) - 1; $i ++) {
            $file_add = empty ( $file_add ) ? $fileArr[$i] : $file_add . "/" . $fileArr[$i];
            if (! file_exists ( $file_add )) {
                mkdir ( $file_add, 0777 );
            }
        }
    }
}