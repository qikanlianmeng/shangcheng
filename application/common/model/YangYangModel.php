<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangYangModel extends Model
{
    protected $name = 'yang_yang';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }
    public static function  getListsByUids($uid,$status=1,$page=1){
        return self::where(['uid'=>$uid,'status'=>$status])->page($page, 10)->order('id desc')->select();
    }

    public static function  getListsByUid($uid){
        return self::where(['uid'=>$uid,'status'=>1])->select();
    }
    public static function  getInfoByYid($uid,$yid){
        return self::where(['uid'=>$uid,'id'=>$yid,'status'=>1])->find();
    }



}