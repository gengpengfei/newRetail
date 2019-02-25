<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 2018/3/6
 * Time: 18:00
 */

namespace app\admin\service;

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
    /*
     * $file 上传获取的图片信息
     * $imgurl 需要存放的文件路径
     * $imgname 图片名称
     */
    public function uploadImage($file,$imgName)
    {
        $image = \think\Image::open($file);
        //获取图片宽高
        $width = $image->width();
        $height = $image->height();
        //获取缩略图大小
        $thumWidth = $this->getConfig('thumb_width');
        $thumHeight = $thumWidth/$width*$height;
        $smallWidth = $this->getConfig('small_width');
        $smallHeight = $smallWidth/$width*$height;
        //创建图片名称
        $image->save('.'.$imgName);
        $image->thumb($smallWidth,$smallHeight)->save('./small'.$imgName);
        $image->thumb($thumWidth,$thumHeight)->save('./thum'.$imgName);
        return true;
    }

    public function uploadFile($file,$path,$imgName){
        //服务器端设定限制
        $maxsize=10485760;//10M,10*1024*1024
        $allowExt = array('jpeg','jpg','png','gif','pdf','word','txt','excel');//允许上传的文件类型（拓展名
        $ext = pathinfo($file['name'],PATHINFO_EXTENSION);//提取上传文件的拓展名

        //目的信息
        if (!file_exists($path)) {  //当目录不存在，就创建目录
            mkdir($path,0777,true);
            chmod($path, 0777);
        }
        //得到唯一的文件名！防止因为文件名相同而产生覆盖
        //$uniName = md5(uniqid(microtime(true),true)).'.'.$ext;
        $destination = $path . $imgName.'.'.$ext;


        if ($file['error']==0) {
            if ($file['size']>$maxsize) {
                exit("上传文件过大！");
            }
            if (!in_array($ext, $allowExt)) {
                exit("非法文件类型");
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                exit("上传方式有误，请使用post方式");
            }
            if (@move_uploaded_file($file['tmp_name'], $destination)) {//@错误抑制符，不让用户看到警告
                //序列化
                $imgRes=[$path,$imgName,$ext];
                $result =serialize($imgRes);
                return $result;
            }else{
                return false;
            }

        }else{
            switch ($file['error']){
                case 1:
                    exit ("超过了上传文件的最大值，请上传2M以下文件");
                    break;
                case 2:
                    exit ("上传文件过多，请一次上传20个及以下文件！");
                    break;
                case 3:
                    exit ("文件并未完全上传，请再次尝试！");
                    break;
                case 4:
                    exit ("未选择上传文件！");
                    break;
                case 7:
                    exit ("没有临时文件夹");
                    break;
            }
        }
    }
}