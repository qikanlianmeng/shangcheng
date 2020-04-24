<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangDrugLogModel extends Model
{
    protected $name = 'yang_drug_log';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳



    public static  function log($uid,$num,$act,$yang_id=0){
       return  self::insert([
            'uid'=>$uid,
            'num'=>$num,
            'act'=>$act,
            'yang_id'=>$yang_id,
            'create_time'=>time(),
            'update_time'=>time(),
        ]);
    }




}