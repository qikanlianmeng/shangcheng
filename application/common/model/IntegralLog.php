<?php

namespace app\common\model;
use think\Model;

class IntegralLog extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 羊币记录
     * @param $uid 用户ID
     * @param $money 钱数
     * @param int $act 动作 1自己2管理员3付款4退款
     * @param int $status  状态0？1成功
     * @param string $remark 备注
     * @param int $executor 执行人 0自己
     */
    public static function operate($uid,$num,$act=1,$status=1,$remark='',$executor=0){
        $data=[
            'uid'=>$uid,
            'num'=>$num,
            'status'=>$status,
            'executor'=>$executor,
            'act'=>$act,
            'remark'=>$remark,
        ];

        if(self::create($data)){
            return $data;
        }
    }


}

