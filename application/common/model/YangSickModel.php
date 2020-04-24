<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangSickModel extends Model
{
    protected $name = 'yang_sick';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }
}