<?php
namespace app\api2\controller;

use think\Controller;
use think\Log;
use Payment\Common\PayException;
use Payment\Client\Notify;
use app\common\model\PayConfig;

date_default_timezone_set('Asia/Shanghai');

class Snotify extends Base
{
    public $PayConfig;
    public function index(){

        $type=input('type','');
        $this->PayConfig=new PayConfig();
        if (stripos($type, 'ali') !== false) {
           $config =$this->PayConfig->getConf('alipay');
        } elseif (stripos($type, 'wx') !== false) {

        } else {
           // $config = $cmbConfig;
        }

        if (stripos($type, 'ali') !== false) {
            $config =$this->PayConfig->getConf('alipay');
            try {
                $callback = new PayNotify();
                $retData = Notify::getNotifyData($type, $config);
                if($retData['trade_status'] == 'TRADE_SUCCESS'){
                    $ret= $callback->notifyProcess($retData);
                }
                /*$retData = ['out_trade_no'=>input('pay_order')];
                $ret= $callback->notifyProcess($retData);*/

            } catch (PayException $e) {
                Log::error($e->errorMessage());
                exit;
            }
        } elseif (stripos($type, 'wx') !== false) {
            $config =$this->PayConfig->getConf('wxpay');
            $fen=input('fen','');
            if($fen != ''){
                $config['mch_id']='1521769561';
                $config['app_id']='wx105a07ac423c3e9d';
                $config['md5_key']='fasdfsdafsdfsdfsadfwe3223r32r23r';
            }
            try {
                $callback = new PayNotify();
                $ret = Notify::run($type, $config, $callback);// 处理回调，内部进行了签名检查


            } catch (PayException $e) {
                Log::error($e->errorMessage());
                exit;
            }
        } else {
            // $config = $cmbConfig;
        }

        Log::info(var_export($ret,true));
        var_dump($ret);
        exit;

    }

    public function return_url(){
        $type=input('type','');
        $this->PayConfig=new PayConfig();
        if (stripos($type, 'ali') !== false) {
            $config =$this->PayConfig->getConf('alipay');
        } elseif (stripos($type, 'wx') !== false) {
            $config =$this->PayConfig->getConf('wxpay');
        } else {
            // $config = $cmbConfig;
        }
        try {
            //TODO 没有做任何的验证。。
            $retData = Notify::getNotifyData($type, $config);
            $callback = new PayNotify();
            $callback->frontCallBack($retData);

        } catch (PayException $e) {
            Log::error($e->errorMessage());
            echo $e->errorMessage();
            exit;
        }
    }




}




