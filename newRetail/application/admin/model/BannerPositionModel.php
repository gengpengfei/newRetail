<?php
/**
 * Created by PhpStorm.
 * User: guanyl
 * Date: 2018/4/8
 * Time: 14:09
 */

namespace app\admin\model;
use app\admin\model\CommonModel;
class BannerPositionModel extends CommonModel
{

    // 设置当前模型对应的完整数据表名称
    public $table = 'new_banner_position';
    protected $pk= 'position_id';
}