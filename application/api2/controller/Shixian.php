<?php

namespace app\api2\controller;
use think\Controller;
use think\Db;
use app\common\service\Payment;

/**
 * swagger: 10%阳寿康充值，扣除90%余额一起转股票
 */
class Shixian
{
  public function index()
  {
    //首先每天下午统计10%截至到下午五点之前的订单，购买10%金额产品的
    //首先查询think_order_goods表中几种规格的商品,56,57,58,59代表10%的那几个商品
    $re = db::table('think_order_goods')->where('goods_id','in',[56,57,58,59])->select();
    dump(count($re));
    exit;
  }

//每天先查询一下当天购买10%金额的订单数
  public function num()
  {
    //首先每天下午统计10%截至到下午五点之前的订单，购买10%金额产品的
    //首先查询think_order_goods表中几种规格的商品,56,57,58,59代表10%的那几个商品
    //今天开始的时间戳
    $starttime = mktime(0,0,0,date('m'),date('d'),date('Y'));
    $starttime = $starttime - 3600*8;
    //今天结束的时间戳
    $endtime =  $starttime + 3600*16;

    $re = db::table('think_order')->where('pay_time','>',$starttime)->where('pay_time','<',$endtime)->where('pay_code','<>',1)->select();
    //dump($re);
    dump(count($re));
    exit;
  }

}