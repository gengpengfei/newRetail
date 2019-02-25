<?php

namespace app\admin\model;

use app\admin\model\CommonModel;
class ProScoreIntervalModel extends CommonModel
{
    protected $table = 'new_pro_score_interval';
    //-- 设置主键
    protected $pk = 'score_interval_id';
    //--隐藏属性
    protected $hidden = [
        'create_time','update_time'
    ];
}