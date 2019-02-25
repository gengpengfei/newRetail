<?php
namespace app\shopapi\service;
class QRcodeService{
    /*
     * explain:生成二维码
     * params :
     * authors:Mr.Geng
     * addTime:2018/6/12 15:07
     */
    public function createQRcode($savePath, $qrData, $qrLevel = 'L', $qrSize = 4)
    {
        if (!isset($savePath)) return false;
        //设置生成png图片的路径
        $PNG_TEMP_DIR = $savePath;

        //检测并创建生成文件夹
        if (!file_exists($PNG_TEMP_DIR)) {
            mkdir($PNG_TEMP_DIR);
        }
        $errorCorrectionLevel = 'L';
        if (isset($qrLevel) && in_array($qrLevel, ['L', 'M', 'Q', 'H'])) {
            $errorCorrectionLevel = $qrLevel;
        }
        $matrixPointSize = 4;
        if (isset($qrSize)) {
            $matrixPointSize = min(max((int)$qrSize, 1), 10);
        }
        if (isset($qrData)) {
            if (trim($qrData) == '') {
                return false;
            }
            //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
            $filename = $PNG_TEMP_DIR . md5($qrData . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
            //开始生成
            \PHPQRCode\QRcode::png($qrData, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        }
        if (file_exists($PNG_TEMP_DIR . basename($filename)))
            return basename($filename);
        else
            return FALSE;
    }
}
