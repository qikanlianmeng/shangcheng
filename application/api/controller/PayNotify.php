<?php
/**
 * Created by PhpStorm.
 * User: qiyu
 * Date: 2017-09-06
 * Time: 11:24
 */

namespace app\api\controller;


use app\api\model\PayNotifyAct;
use think\Log;
use app\common\model\PayLog;
use Payment\Notify\PayNotifyInterface;



class PayNotify implements PayNotifyInterface{

    public function notifyProcess(array $data)
    {
        $PayLog = new PayLog();
        $info = $PayLog->get(['out_trade_no'=>$data['out_trade_no']]);
        if($info){
            if($info['status'] == 0){
                try {
                    $param=unserialize($info->param);
                    $PayNotifyAct=new PayNotifyAct();
                  // if($PayNotifyAct->$param['method']($info->order_number,$param['param'])){
                  if(call_user_func_array([$PayNotifyAct,$param['method']],[$info->order_number,$param['param']])){
                       $info->status=1;
                       if(!$info->save()){
                           Log::error(var_export($data,true));
                           Log::error("订单处理完成，支付记录更新失败");
                       }
                   }
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }

        }else{
           Log::error(var_export($data,true));
           Log::error("没有找到相关订单号数据");
        }

        return true;

    }
    //前端回调
    public function frontCallBack($data){
        //直接根据 out_trade_no获取 数据 然后跳转
        $PayLog = new PayLog();
        $info = $PayLog->get(['out_trade_no'=>$data['out_trade_no']]);
        //页面跳转
        echo  empty($info['redirect_url'])?'': "<script>window.location.href='".$info['redirect_url']."'</script>";
    }
}