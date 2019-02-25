<?php
namespace app\shopapi\model;
use app\shopapi\model\CommonModel;
class RegionModel extends CommonModel{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'new_region';

    protected $pk= 'region_id';

    public function queryCityWithSearchStr($areaStr)
    {
        $search = array("@"," ",',', '/', ':', '*', '?', '"', '<', '>', '|',"!","#","$","¥","^ ^","&","(",")","%","^",'，');
        $replace = array();
        $searchStr = str_replace($search, $replace, $areaStr);

        $tempStr = "%";
        for ($i=0; $i < mb_strlen($searchStr); $i++){
            $value = mb_substr($searchStr,$i,1);
            $tempStr = $tempStr.$value."%";
        }
        $searchResult = $this->where('name|merger_name|pinyin','like',$tempStr )
            ->where("level",'>',1 )
            ->order('level')
            ->limit(20)
            ->select();
        return $searchResult;
    }

}

