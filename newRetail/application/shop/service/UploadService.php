<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\shop\service;

class UploadService extends CommonService
{
    use \app\api\traits\GetConfig;

    /*
     * $file 上传获取的图片信息
     * $imgurl 需要存放的文件路径
     * $imgname 图片名称
     */
    public function upload($file,$imgUrl,$imgName)
    {
        $image = \think\Image::open($file);
        //-- 判断文件夹
        if(!file_exists('.'.$imgUrl)) $this->createFile($imgUrl);
        if(!file_exists('./small'.$imgUrl)) $this->createFile('/small'.$imgUrl);
        if(!file_exists('./thum'.$imgUrl)) $this->createFile('/thum'.$imgUrl);
        //获取图片宽高
        $width = $image->width();
        $height = $image->height();
        //获取缩略图大小
        $thumWidth = $this->getConfig('thumb_width');
        $thumHeight = $thumWidth/$width*$height;
        $smallWidth = $this->getConfig('small_width');
        $smallHeight = $smallWidth/$width*$height;
        //创建图片名称
        $imgtype='.'.$image->type();
        $image->save('.'.$imgUrl.$imgName.$imgtype);
        $image->thumb($smallWidth,$smallHeight)->save('./small'.$imgUrl.$imgName.$imgtype);
        $image->thumb($thumWidth,$thumHeight)->save('./thum'.$imgUrl.$imgName.$imgtype);
        //序列化
        $imgRes=[$imgUrl,$imgName,$imgtype];
        $result =serialize($imgRes);
        return $result;
    }

    /*
     * $file 上传获取的图片信息
     * $imgurl 需要存放的文件路径
     * $imgname 图片名称
     */
    public function uploadImg($file,$imgUrl,$imgName)
    {
        $image = \think\Image::open($file);
        //-- 判断文件夹
        if(!file_exists('.'.$imgUrl)) $this->createFile($imgUrl);
        //创建图片名称
        $imgType = '.'.$image->type();
        $image->save($imgUrl.$imgName.$imgType);
        //图片地址
        $imgUrl=$imgUrl.$imgName.$imgType;
        return $imgUrl;
    }

    public function createFile($file)
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



    /* 多图上传
     * $file 上传获取的图片信息
     * $imgurl 需要存放的文件路径
     * $imgname 图片名称
     */
    public function uploadmore($file,$imgUrl)
    {
        foreach ($file as $value){
            $image = \think\Image::open($value['tmp_name']);
            $imgName=time().rand(1,100);
            //-- 判断文件夹
            if(!file_exists('.'.$imgUrl)) $this->createFile($imgUrl);
            if(!file_exists('./small'.$imgUrl)) $this->createFile('/small'.$imgUrl);
            if(!file_exists('./thum'.$imgUrl)) $this->createFile('/thum'.$imgUrl);
            //获取图片宽高
            $width = $image->width();
            $height = $image->height();
            //获取缩略图大小
            $thumWidth = $this->getConfig('thumb_width');
            $thumHeight = $thumWidth/$width*$height;
            $smallWidth = $this->getConfig('small_width');
            $smallHeight = $smallWidth/$width*$height;
            //创建图片名称
            $imgtype='.'.$image->type();
            $image->save('.'.$imgUrl.$imgName.$imgtype);
            $image->thumb($smallWidth,$smallHeight)->save('./small'.$imgUrl.$imgName.$imgtype);
            $image->thumb($thumWidth,$thumHeight)->save('./thum'.$imgUrl.$imgName.$imgtype);
            $imgRes[]=array($imgUrl,$imgName,$imgtype);
        }
        $result =serialize($imgRes);
        return $result;
    }

    //删除图片(应用于修改是重新上传图片)
    public function delimage($ulr){
        $imageurl=substr($ulr,7);
        $smallurl=substr($ulr,1);
        $thumurl=str_replace("small","thum",$smallurl);
        unlink($imageurl);
        unlink($smallurl);
        unlink($thumurl);
    }


}