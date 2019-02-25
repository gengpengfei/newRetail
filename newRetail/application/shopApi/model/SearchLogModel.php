<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class SearchLogModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_search_log';
    protected $hidden = ['id','search_times'];

    private function splitStrForLike($searchStr){

        if($searchStr){
            $tempStr = "%";
            for ($i=0; $i < mb_strlen($searchStr); $i++){
                $value = mb_substr($searchStr,$i,1);
                $tempStr = $tempStr.$value."%";
            }

            return $tempStr;
        }else{
            return null;
        }



    }


    function getSearchStrList($searchStr){


        //去掉特殊字符，并得到预期的数组
        $search = array("   ","  "," ",',','，',"@",'/', ':', '*', '?', '"', '<', '>', '|',"!","#","$","¥","^ ^","&","(",")","%","^");
        $replace = array(',',',',',',',',',');
        $searchStr = str_replace($search, $replace, $searchStr);
        $searchArr = explode(",",$searchStr);

        //将得到的搜索数组字符串转成期望的搜索字符串数组
        $tempArr = [];
        foreach ($searchArr as $value){
            $likeStr = $this->splitStrForLike($value);
            if($likeStr){
                $tempArr[]=$likeStr;
            }
        }
        if(!$tempArr){
            return [];
        }

        //拼接搜索语句
        $where = "";
        for ($i = 0;$i < count($tempArr);$i++){
            $itemStr = $tempArr[$i];
            if($i == 0){
                $where .= "search_name like '{$itemStr}' and search_num>0";
            }
            else{
                $where .= "or search_name like '{$itemStr}' and search_num>0";
            }

        }

        return $this
            ->where($where)
            ->order('search_times' ,'desc')
            ->limit(20)
            ->select();

    }

}