<?php

namespace app\common\model;
use think\Model;

class MoneyLog extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 钱记录
     * @param $uid 用户ID
     * @param $money 钱数
     * @param int $act 动作 1自己充值2管理员充值3付款4退款
     * @param int $status 充值状态0等待支付1支付成功
     * @param string $remark 备注
     * @param int $executor 执行人 0自己
     */
    public static function operate($uid,$money,$act=1,$status=0,$remark='',$executor=0){
        $create_order_no='cz'.time() . rand(1000, 9999);
        $data=[
            'out_trade_no'=>$create_order_no,
            'uid'=>$uid,
            'money'=>$money,
            'status'=>$status,
            'executor'=>$executor,
            'act'=>$act,
            'remark'=>$remark,
        ];

        if(self::create($data)){
            return $data;
        }else{
            return '';
        }
    }


}

