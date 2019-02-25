<?php
/**
 * Created by PhpStorm.
 * User: jlcr
 * Date: 2018/4/16
 * Time: 10:00
 */

namespace app\shop\service;
use PHPExcel_IOFactory;
use PHPExcel;

class ExcelService extends CommonService
{
    /*
     * $exceltitle 表格头部 格式
     * $exceltitle=array('ceshi1','ceshi2','ceshi3','ceshi4');
     * $excelcontent 表格内容  格式
     * $excelcontent=array(
            '0'=>array('1','2','3','0'),
            '1'=>array('4','5','6','0'),
            '2'=>array('7','8','9','0')
        );
     *
     * */
    public function exportexcel($exceltitle,$excelcontent){
        $title='ceshititle';   //给当前活动sheet设置名称
        $a="0ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $PHPExcel = new PHPExcel();
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle($title);
        //设置excel表头
        for ($i=1;$i<=count($exceltitle);$i++){
            $PHPSheet->setCellValue($a[$i].'1',$exceltitle[$i-1]);
        }
        //导入内容
        foreach($excelcontent as $key => $val){
            foreach($val as $k =>$v){
                $j=$key+2;
                $h=$k+1;
                $PHPSheet->setCellValue($a[$h].$j,$v);
            }
        }
        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="01simple.xlsx"');
        header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");

    }
}