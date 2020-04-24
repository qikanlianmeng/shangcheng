<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangDrugModel extends Model
{
    protected $name = 'yang_drug';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳



    public static function getInfo(){
        return self::where(['status'=>1])->order('price asc')->find();
    }




}