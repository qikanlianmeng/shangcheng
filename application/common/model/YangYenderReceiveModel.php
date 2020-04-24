<?php

namespace app\common\model;
use think\Model;
use think\Db;

class YangYenderReceiveModel extends Model
{
    protected $name = 'yang_tender_receive';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳



    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_yang_tender_receive.*,think_yang_tender.title,nickname')->join('think_yang_tender','think_yang_tender.id=think_yang_tender_receive.tender_id','left')->join('think_member','think_yang_tender_receive.uid=think_member.id','left')->where($map)->page($Nowpage, $limits)->order('think_yang_tender_receive.id desc')->select();
    }

    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    public static function  getListsByUids($uid,$page){
        return self::where(['uid'=>$uid])->page($page, 10)->order('id desc')->select();
    }


    public static function  getListsByUid($uid){
        return self::where(['uid'=>$uid])->select();
    }



}