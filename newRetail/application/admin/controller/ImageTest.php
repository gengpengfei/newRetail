<?php
namespace app\admin\controller;
use think\Config;
use think\Controller;
use think\Paginator;
use think\view\driver\Think;

class Image extends Controller
{
    private $uploadurl = '';
    private $jsuploadurl = '';
    public function _initialize(){
        $user = session('userinfo');
        if($user['groupid'] == 1){
            $this->uploadurl = '.'.Config::get('upload');
            $this->jsuploadurl = Config::get('upload');
        }else{
            $this->uploadurl = '.'.Config::get('upload').'/user'.$user['id'];
            $this->jsuploadurl = Config::get('upload').'/user'.$user['id'];
            if(!is_dir($this->uploadurl)){
                mkdir($this->uploadurl, 0777);
                chmod($this->uploadurl, 0777);
            }
        }

    }
    public function show()
    {

        //./upload  /a/b;
        $param = request()->param();
        if (isset($param['filter_name'])) {
            $filter_name = rtrim(str_replace(array('../', '..\\', '..', '*'), '', $param['filter_name']), '/');
        } else {
            $filter_name = null;
        }


        $uploadurl = $this->uploadurl;
        $imgtype= '';
        $videotype = '';
        $pdftype = '';
        if(isset($param['type'])){
            if($param['type'] == 1){
                $imgtype = Config::get('img_type');
            }
            if($param['type'] == 2){
                $videotype = Config::get('video_type');
            }
            if($param['type'] == 3){
                $pdftype = Config::get('pdf_type');
            }
        }else{
            $imgtype = Config::get('img_type');
        }



        // Make sure we have the correct directory
        if (isset($param['directory'])) {
            $directory = str_replace('-','/',$param['directory']);
            $directory = $directory.'/';
        } else {
            $directory = null;
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {
            $page = 1;
        }
//a-b-c
        $data['images'] = array();
//echo $uploadurl.'/'.$directory  . $filter_name . '*';exit;
        // Get directories
        $directories = glob($uploadurl.'/'.$directory  . $filter_name . '*', GLOB_ONLYDIR);
        if (!$directories) {
            $directories = array();
        }
        // Get files
        $files = glob($uploadurl.'/'.$directory . $filter_name . '*.{'.$pdftype.','.$videotype.','.$imgtype.'}', GLOB_BRACE);
        $timefile = array();
        $filelist  =array();
        if (!$files) {
            $files = array();
        }else{
            foreach ($files as $k=>$file){
                if(is_file($file)){
                    $timefile[filemtime($file)] = $file;
                }
            }
        }
        krsort($timefile);
        //var_dump($timefile);exit;

        // Merge directories and files
        $images = array_merge($directories, $timefile);

        $image_total = count($images);

        $images = array_splice($images, ($page - 1) * 16, 16);


        $imgtype_arr = explode(',',$imgtype );
        $videotype_arr = explode(',',$videotype );
        foreach ($images as $image) {
            $name = $this->get_basename($image);

            $thumb_name = $this->get_basename($image);
            if (is_dir($image)) {
                $path = $image;

                $path = str_replace($uploadurl.'/','',$path);

                //$path = $directory;
                $path = str_replace('/','-',$path);
                //$path = $path.'-';
                $data['images'][] = array(
                    'name'  => $name,
                    'type'  => 'directory',
                    'path'  => $path,
                );

            } elseif (is_file($image)) {

                if(!in_array(get_extension($thumb_name),$imgtype_arr)){
                    if(!in_array(get_extension($thumb_name),$videotype_arr)){
                        $data['images'][] = array(
                            'name'  => $name,
                            'type'  => 'pdf',
                            'path'  => $image,
                            'file_src' => $this->jsuploadurl.'/'.$directory.$thumb_name,
                        );
                    }else{
                        $data['images'][] = array(
                            'name'  => $name,
                            'type'  => 'video',
                            'path'  => $image,
                            'file_src' => $this->jsuploadurl.'/'.$directory.$thumb_name,
                        );
                    }

                }else{
                    // Find which protocol to use to pass the full image link back
                    $image_class = \think\Image::open($image);
                    $image_class->thumb(150,150 )->save('./thumb/'.$thumb_name);


                    $data['images'][] = array(
                        'thumb' => '/thumb/'.$thumb_name.'?cache='.rand(1,99999),
                        'img_src'=>$this->jsuploadurl.'/'.$directory.$thumb_name,
                        'name'  => $name,
                        'type'  => 'image',
                        'path'  => $image,
                    );
                }

            }
        }
        //上级目录
        $Parent='';
        if (isset($param['directory'])) {

            $Parent = $param['directory'];

            $Parent = explode('-',$Parent );
            array_pop($Parent);
            $Parent = implode('-',$Parent );
        }
        // var_dump($Parent);exit;
// a-b-c
        $this->assign('parent',$Parent);//上级目录
        $this->assign('filter_name',$filter_name);//查找文件名称
        $directory = isset($param['directory']) ? $param['directory'] : '';
        $type = isset($param['type']) ? $param['type'] :'';
        $this->assign('directory',$directory);//刷新

        $this->assign('pagination', $this->pagerender($image_total,16,$page,$directory,$filter_name,$type));

        $this->assign('images',$data['images']);
        $this->assign('type', $type);

        return $this->fetch();
    }

    public function upload() {

        $json = array();
        $param = request()->param();
        $files = $_FILES;

        $uploadurl = $this->uploadurl;
        // Make sure we have the correct directory
        if (isset($param['directory'])) {
            $directory = str_replace('-','/',$param['directory']);
            $directory = $uploadurl.'/'.$directory.'/';

        } else {
            $directory = $uploadurl.'/';
        }
        $date = array();

        //var_dump($files['file']['name']);exit;
        if(!empty($files['file']['name'][0])){

            foreach ($files['file']['name'] as $k=> $name){
                $file = array();
                $file['file']['name'] = $name;
                $file['file']['type'] = $files['file']['type'][$k];
                $file['file']['tmp_name'] = $files['file']['tmp_name'][$k];
                $file['file']['error'] = $files['file']['error'][$k];
                $file['file']['size'] = $files['file']['size'][$k];

                if (!empty($file['file']['name']) && is_file($file['file']['tmp_name'])) {
                    // Sanitize the filename
                    $filename = $this->get_basename(html_entity_decode($file['file']['name'], ENT_QUOTES, 'UTF-8'));

                    // Validate the filename length
                    if ((utf8_strlen($filename) < 2) || (utf8_strlen($filename) > 255)) {
                        $json[$k]['error'] .= '文件错误';
                    }

                    $img_type = Config::get('img_type');
                    $video_type = Config::get('video_type');
                    $pdf_type = Config::get('pdf_type');
                    $allowed = $img_type.','.$video_type.','.$pdf_type;
                    $allowed = explode(',',$allowed );

                    if (!in_array(utf8_strtolower(utf8_substr(strrchr($filename, '.'), 1)), $allowed)) {
                        $json[$k]['error'] = '文件类型错误';
                    }

                    // Allowed file mime types
                    $allowed = array(
                        'image/jpeg',
                        'image/pjpeg',
                        'image/png',
                        'image/x-png',
                        'image/gif',
                        'video/mp4',
                        'application/pdf'
                    );

                    if (!in_array($file['file']['type'], $allowed)) {
                        $json[$k]['error'] = '文件类型错误,不支持'.$file['file']['type'];
                    }

                    // Return any upload error
                    if ($file['file']['error'] != UPLOAD_ERR_OK) {
                        $json[$k]['error'] =  $file['file']['error'];
                    }
                } else {
                    $json[$k]['error'] = $file['file']['error'];
                }


                if (!isset($json[$k])) {
                    try {
                        move_uploaded_file($file['file']['tmp_name'], $directory. $filename);
                        touch($directory. $filename,time()+$k);
                        //$json['success'] .= $filename.'上传成功/';
                    } catch (Exception $e) {
                        $json[$k]['error'] = $e->getMessage();
                    }
                }

                if(isset($json[$k]['error'])){
                    if(isset($date['error'])){
                        $date['error'] .= $filename.'上传失败,原因:'.$json[$k]['error'];
                    }else{
                        $date['error'] = $filename.'上传失败,原因:'.$json[$k]['error'];
                    }
                }
            }
        }

        if(!isset($date['error'])){
            $date['success'] = '上传成功';
        }
        exit(json_encode($date));
        //$this->response->setOutput(json_encode($json));
    }
    /*
     * @node 创建目录
     * @author 32个字符
     */
    function folder(){

        $json = array();
        $param = request()->param();

        $uploadurl = $this->uploadurl;
        // Make sure we have the correct directory
        if (isset($param['directory'])) {
            $directory = str_replace('-','/',$param['directory']);
            $directory = $uploadurl.'/'.$directory;

        } else {
            $directory = $uploadurl;
        }


        // Check its a directory
        if (!is_dir($directory)) {
            $json['error'] = '路径错误';
        }

        if (!$json) {
            // Sanitize the folder name
            setlocale(LC_ALL, 'zh_CN');
            //echo $this->get_basename(html_entity_decode($param['folder'], ENT_QUOTES, 'UTF-8'));
            //exit;
            $folder = str_replace(array('../', '..\\', '..'), '', basename(html_entity_decode($param['folder'], ENT_QUOTES, 'UTF-8')));
            $folder = str_replace(array('../', '..\\', '..'), '', $this->get_basename(html_entity_decode($param['folder'], ENT_QUOTES, 'UTF-8')));

            if(strpos($folder, ' ') || strpos($folder, '+') || strpos($folder, '/') || strpos($folder, '.') || strpos($folder, '?') || strpos($folder, '%') || strpos($folder, '#') || strpos($folder, '-') || strpos($folder, '&') || strpos($folder, '=')){
                $json['error'] = '文件夹名称不可包含特殊字符:空格+-/?%#&=.';
            }
            // Validate the filename length
            if ((utf8_strlen($folder) < 2) || (utf8_strlen($folder) > 128)) {
                $json['error'] = '名字长度错误';
            }

            // Check if directory already exists or not
            if (is_dir($directory . '/' . $folder)) {
                $json['error'] = '文件夹已存在';
            }
        }

        if (!$json) {
            mkdir($directory . '/' . $folder, 0777);
            chmod($directory . '/' . $folder, 0777);

            $json['success'] = '创建成功';
        }
        exit(json_encode($json));
    }

    public function delete() {

        $json = array();
        $param = request()->param();
        $uploadurl = $this->uploadurl;

        if (isset($param['path'])) {
            $paths = $param['path'];
        } else {
            $paths = array();
        }

        // Loop through each path to run validations
        foreach ($paths as $path) {
            // Check path exsists
            if ($path == $uploadurl.'/') {
                $json['error'] = '根目录无法删除';

                break;
            }
        }

        if (!$json) {
            // Loop through each path
            foreach ($paths as $path) {
                // If path is just a file delete it
                if (is_file($path)) {
                    unlink($path);

                    // If path is a directory beging deleting each file and sub folder
                } else {
                    $path = $uploadurl.'/'.str_replace('-','/' ,$path );


                    if(is_dir($path)){
                        $files = array();

                        // Make path into an array
                        $path = array($path . '*');

                        // While the path array is still populated keep looping through
                        while (count($path) != 0) {
                            $next = array_shift($path);

                            foreach (glob($next) as $file) {
                                // If directory add to path array
                                if (is_dir($file)) {
                                    $path[] = $file . '/*';
                                }

                                // Add the file to the files to be deleted array
                                $files[] = $file;
                            }
                        }

                        // Reverse sort the file array
                        rsort($files);

                        foreach ($files as $file) {
                            // If file just delete
                            if (is_file($file)) {
                                unlink($file);

                                // If directory use the remove directory function
                            } elseif (is_dir($file)) {

                                rmdir($file);
                            }
                        }
                    }

                }
            }

            $json['success'] = '删除成功';
        }
        exit(json_encode($json));
    }


    public function pagerender($total,$limit,$page,$directory,$filter_name,$type) {

        if ($page < 1) {
            $page = 1;
        }

        if (!(int)$limit) {
            $limit = 10;
        }

        $num_links = 8;
        $num_pages = ceil($total / $limit);

        $output = '';

        if ($page > 1) {
            //$output .= '<a class="pageUp" href="' . str_replace('{page}', 1, $this->url) . '">' . $this->text_first . '</a>';
            $output .= '<li><a class="pageUp"  href="' . url('admin/image/show',['page'=>$page-1,'directory'=>$directory,'filter_name'=>$filter_name,'type'=>$type]) . '">' . '«' . '</a></li>';
        }

        if ($num_pages > 1) {
            if ($num_pages <= $num_links) {
                $start = 1;
                $end = $num_pages;
            } else {
                $start = $page - floor($num_links / 2);
                $end = $page + floor($num_links / 2);

                if ($start < 1) {
                    $end += abs($start) + 1;
                    $start = 1;
                }

                if ($end > $num_pages) {
                    $start -= ($end - $num_pages);
                    $end = $num_pages;
                }
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($page == $i) {
                    $output .= '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    $output .= '<li><a href="' . url('admin/image/show',['page'=>$i,'directory'=>$directory,'filter_name'=>$filter_name,'type'=>$type]) . '">' . $i . '</a></li>';
                }
            }
        }

        if ($page < $num_pages) {
            $output .= '<li><a class="pageDown" href="' .url('admin/image/show',['page'=>$page+1,'directory'=>$directory,'filter_name'=>$filter_name,'type'=>$type]) . '">' . '»' . '</a></li>';
            //$output .= '<a class="pageDown" href="' . str_replace('{page}', $num_pages, $this->url) . '">' . $this->text_last . '</a>';
        }

        $output .= '';
        $output = '<ul class="pagination">'.$output.'</ul>';
        if ($num_pages > 1) {
            return $output;
        } else {
            return '';
        }
    }

    function croper(){

        ini_set('memory_limit','300M');
        $param = request()->param();
        $x = isset($param['x']) ? $param['x'] : '' ;
        $y = isset($param['y']) ? $param['y'] : '' ;
        $w = isset($param['w']) ? $param['w'] : '' ;
        $h = isset($param['h']) ? $param['h'] : '' ;

        $imgsrc = isset($param['img']) ? urldecode($param['img']) : '' ;
        if($x == '' || $y== '' || $w == '' || $h == '' || $imgsrc == ''){
            $this->error('参数错误');
        }else{

            $imgname = basename($imgsrc);
            $fname = $w.'_'.$h.'_'.rand(1,9999).$imgname;
            $thname = './croper/'.$fname;
            $rhname = '/croper/'.$fname;
            $temp = array(1=>'gif', 2=>'jpeg', 3=>'png');


            list($fw, $fh, $tmp) = getimagesize('.'.$imgsrc);

            if(!$temp[$tmp]){
                $this->error('该图片类型不匹配');
            }

            $tmp = $temp[$tmp];
            $infunc = "imagecreatefrom$tmp";
            $outfunc = "image$tmp";
            $fimg = $infunc('.'.$imgsrc);

            $timg = @imagecreatetruecolor($w, $h);


            $color = imagecolorAllocate($timg,255,255,255);
            imagefill($timg,0,0,$color);

            imagecopyresampled($timg, $fimg,0,0, $x,$y, $w,$h,$w,$h);
            if($outfunc($timg, $thname)){
                $this->success($rhname);
            }else{
                $this->error('裁图失败,重新裁图');
            }

        }


        //1原图  2缩略图  3截图
        //指定图片位置
    }
    function get_basename($filename){
        return preg_replace('/^.+[\\\\\\/]/', '', $filename);
    }

}