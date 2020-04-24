<?php

namespace app\common\service;

use app\common\model\PayConfig;
use app\common\model\PayLog;
use app\common\model\RefundsLog;
use think\Model;
use think\Log;
use Payment\Common\PayException;
use Payment\Client\Charge;
use Payment\Config;
use Payment\Client\Refund;
use Payment\Client\Transfer;
class Payment extends Model
{
    public $PayConfig;//配置
    public function __construct()
    {
        parent::__construct();
        $this->PayConfig=new PayConfig();
    }

    /*
  $data = [
           'out_trade_no' => '14935460661343',
           'refund_fee' => '0.01',
           'reason' => '测试帐号退款',
           'refund_no' => $refundNo,
       ];


   $data = [
   'out_trade_no' => '14935385689468',
   'total_fee' => '3.01',
   'refund_fee' => '3.01',
   'refund_no' => $refundNo,
   'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE:可用余额退款  REFUND_UNSETTLED:未结算资金退款（默认）
   ];

 */
    /**
     * 退款操作
     * @param $order_sn
     * @param $money 0代表全部
     */
    public function refunds($order_sn,$money=0,$reason='订单退款'){
        $info=PayLog::get(['order_number'=>$order_sn,'status'=>1]);
        if($info){
            //退款金额
            $refunds = $info->money;
            if($money>0){
                $refunds =$money;
            }
            if($refunds>$info->money){
                return ['code'=>-2,'msg'=>'退款金额不能大于支付金额'];
            }

            $refundNo=$this->create_order_no();
            $tag=['code'=>0];
            //判断支付类型
            switch($info->pay_type){
                case 3://阿里支付
                    $data = [
                        'out_trade_no' => $info->out_trade_no,
                        'refund_fee' => $refunds,
                        'reason' => $reason,
                        'refund_no' => $refundNo,
                    ];
                   $tag = $this->ali_refund($data);
                   break;
                case 2://微信支付
                    $data = [
                        'out_trade_no' => $info->out_trade_no,
                        'total_fee' => $info->money,
                        'refund_fee' => $refunds,
                        'refund_no' => $refundNo,
                        'refund_account' => 'REFUND_SOURCE_UNSETTLED_FUNDS',// REFUND_RECHARGE:可用余额退款  REFUND_UNSETTLED:未结算资金退款（默认）
                    ];
                    $tag = $this->wx_refund($data);
                    break;
            }
            if($tag['code'] == 1){
                RefundsLog::create([
                    'out_trade_no'=>$refundNo,
                    'pay_trade_no'=>$info->out_trade_no,
                    'order_number'=>$order_sn,
                    'uid'=>$info->uid,
                    'money'=>$refunds,
                    'pay_type'=>$info->pay_type,
                    'status'=>'1',
                    'reason'=>$reason
                ]);
                //改变订单 支付的状态
                $info->status=2;
                $info->save();

                return ['code'=>1,'msg'=>'退款成功'];
            }else{
                return $tag;
            }



        }else{
            return ['code'=>-1,'msg'=>'无该支付订单信息'];
        }
    }


    /**
     * 阿里APP 支付
     * @param $uid  用户ID
     * @param $subject  商品标题
     * @param $order_id 订单号
     * @param $amount  金额
     * @param $param  成功后的回调参数
     * @param string $redirect_url 成功后的跳转URL
     * @param string $body 商品描述
     * @param int $goods_type 商品类型 1实物 0虚拟
     * @return array
     */
    public function ali_app($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);

        try {
            $str = Charge::run(Config::ALI_CHANNEL_APP, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $str, 'msg' => ''];

    }

    /**
     * 阿里WAP支付
     * @param $uid  用户ID
     * @param $subject  商品标题
     * @param $order_id 订单号
     * @param $amount  金额
     * @param $param  成功后的回调参数
     * @param string $redirect_url 成功后的跳转URL
     * @param string $body 商品描述
     * @param int $goods_type 商品类型 1实物 0虚拟
     * @return array
     */
    public function ali_wap($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_WAP, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];

    }

    /**
     * 阿里二维码
     * @param $uid  用户ID
     * @param $subject  商品标题
     * @param $order_id 订单号
     * @param $amount  金额
     * @param $param  成功后的回调参数
     * @param string $redirect_url 成功后的跳转URL
     * @param string $body 商品描述
     * @param int $goods_type 商品类型 1实物 0虚拟
     * @return array  要生成二维码的 内容
     */
    public function ali_qr($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_QR, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];
    }

    /**
     * 阿里 PC 支付
     * @param $uid  用户ID
     * @param $subject  商品标题
     * @param $order_id 订单号
     * @param $amount  金额
     * @param $param  成功后的回调参数
     * @param string $redirect_url 成功后的跳转URL
     * @param string $body 商品描述
     * @param int $goods_type 商品类型 1实物 0虚拟
     * @return array 成功后需要跳转的URL
     */
    public function ali_web($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_WEB, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];
    }
    /**
     * 阿里转账
     * */
    public function ali_transfer($account,$uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){

        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        $data = [
            'trans_no' => time(),
            'payee_type' => 'ALIPAY_LOGONID',
            'payee_account' =>$account,// ALIPAY_USERID: 2088102169940354      ALIPAY_LOGONID：aaqlmq0729@sandbox.com
            'amount' => $amount,
            'remark' => '提现',
            'payer_show_name' => '',
        ];

        try {
            $ret = Transfer::run(Config::ALI_TRANSFER, $this->PayConfig->getConf('alipay'), $data);
            $ret = json_deocde($ret,true);
            if($ret['return_code'] == 'SUCCESS' && $ret['result_code'] == 'SUCCESS'){
                return true;
            }else{
                return $ret['err_code'];
            }
        } catch (PayException $e) {
            //echo $e->errorMessage();
            //exit;
            return $e->errorMessage();
        }
    }
    /**
     * 微信转账
     * */
    public function wx_transfer($openid,$uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){

        //创建订单信息
        $payData=$this->ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        $data = [
            'trans_no' => time(),
            'openid' => $openid,
            'check_name' => 'NO_CHECK',// NO_CHECK：不校验真实姓名  FORCE_CHECK：强校验真实姓名   OPTION_CHECK：针对已实名认证的用户才校验真实姓名
            'payer_real_name' => '',
            'amount' => $amount,
            'desc' => '转账',
            'spbill_create_ip' => '127.0.0.1',
        ];

        try {
            $ret = Transfer::run(Config::WX_TRANSFER, $this->PayConfig->getConf('wxpay'), $data);
            $ret = json_deocde($ret,true);
            if($ret['alipay_trade_app_pay_response']['code'] == 10000){
                return true;
            }else{
                return $ret['alipay_trade_app_pay_response']['sub_msg'];
            }
        } catch (PayException $e) {
            //echo $e->errorMessage();
            //exit;
            return $e->errorMessage();
        }
    }


    /**
     阿里支付处理
    $payData = [
    'body'    => 'ali qr pay',
    'subject'    => '测试支付宝扫码支付',
    'order_no'    => $orderNo,
    'timeout_express' => time() + 600,// 表示必须 600s 内付款
    'amount'    => '0.01',// 单位为元 ,最小为0.01
    'return_param' => '123123',
    'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// 客户地址
    'goods_type' => '1',
    'store_id' => '',
    ];
     */
    private function ali_handle($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //创建支付订单号
        $out_trade_no=$this->create_order_no();
        //添加 支付信息
        PayLog::create([
            'out_trade_no'=>$out_trade_no,
            'uid'=>$uid,
            'money'=>$amount,
            'order_number'=>$order_id,
            'pay_type'=>'3',
            'param'=>serialize($param),
            'redirect_url'=>$redirect_url,
        ]);
        return   $payData=[
            'body'    => $body,
            'subject'    =>$subject,
            'order_no'    => $out_trade_no,
            'timeout_express' => time() + 600,// 表示必须 600s 内付款
            'amount'    => $amount,// 单位为元 ,最小为0.01
            'return_param' => '',
            'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// 客户地址
            'goods_type' =>$goods_type,
            'store_id' => ''
        ];
    }

 /*
   $data = [
            'out_trade_no' => '14935460661343',
            'refund_fee' => '0.01',
            'reason' => '测试帐号退款',
            'refund_no' => $refundNo,
        ];


    $data = [
    'out_trade_no' => '14935385689468',
    'total_fee' => '3.01',
    'refund_fee' => '3.01',
    'refund_no' => $refundNo,
    'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE:可用余额退款  REFUND_UNSETTLED:未结算资金退款（默认）
    ];

  */
    /**
     * 阿里 退款
     * @param $data
     * @return array
     */
    public function ali_refund($data){
        try {
            $ret = Refund::run(Config::ALI_REFUND, $this->PayConfig->getConf('alipay'), $data);
            if($ret['code']=='10000'){
                return ['code' => 1, 'data' => $ret, 'msg' => '退款成功'];
            }else{
                return ['code' => 0, 'data' => $ret, 'msg' => $ret['msg']];
            }
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }


    }






    /**
     * 微信APP
     * @param $uid 用户ID
     * @param $subject 产品名称
     * @param $order_id 订单ID
     * @param $amount 金额
     * @param $param  回调参数
     * @param string $body 产品描述
     */
    public function wx_app($uid,$subject,$order_id,$amount,$param,$body='在线支付'){
        //创建订单信息
        $payData=$this->wx_handle('wx_app','',$uid,$subject,$order_id,$amount,$param,$body);
        try {
            $conf=$this->PayConfig->getConf('wxpay');
            /*$conf['mch_id']='1521769561';
            $conf['app_id']='wx105a07ac423c3e9d';
            $conf['md5_key']='fasdfsdafsdfsdfsadfwe3223r32r23r';
            $conf['notify_url']=$conf['notify_url'].'/fen/public';*/
            $ret = Charge::run(Config::WX_CHANNEL_APP, $conf, $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }

    /**
     * 微信h5支付
     * @param $uid 用户ID
     * @param $subject 产品标题
     * @param $order_id 订单ID
     * @param $amount 金额
     * @param $param 成功后的回调
     * @param string $body 产品描述
     */
    public function wx_wap($uid,$subject,$order_id,$amount,$param,$body='在线支付'){
        $payData=$this->wx_handle('wx_wap','',$uid,$subject,$order_id,$amount,$param,$body);
        try {
            $url = Charge::run(Config::WX_CHANNEL_WAP, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
         return ['code' => 1, 'data' => $url, 'msg' => ''];

    }

    /**
     * 微信公众号支付
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array
     */
    public function wx_pub($openid,$uid,$subject,$order_id,$amount,$param,$body='在线支付',$product_id=1){
        //创建订单信息
        $payData=$this->wx_handle('wx_pub',$openid,$uid,$subject,$order_id,$amount,$param,$body,$product_id);
        try {
            $ret = Charge::run(Config::WX_CHANNEL_PUB, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 2, 'data' => $ret, 'msg' => ''];

    }

    /**
     * 微信二维码
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array  需要生成二维码的 URL
     */
    public function wx_qr($openid,$uid,$subject,$order_id,$amount,$param,$body='在线支付',$product_id=1){
        $payData=$this->wx_handle('wx_qr',$openid,$uid,$subject,$order_id,$amount,$param,$body,$product_id);
        try {
            $ret = Charge::run(Config::WX_CHANNEL_QR, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }
    /**
     * 微信数据处理
     * @param $type
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array
     */
    private function wx_handle($type,$openid,$uid,$subject,$order_id,$amount,$param,$body='在线支付',$product_id=1){
        //创建支付订单号
        $out_trade_no=$this->create_order_no();
        //添加 支付信息
        PayLog::create([
            'out_trade_no'=>$out_trade_no,
            'uid'=>$uid,
            'money'=>$amount,
            'order_number'=>$order_id,
            'pay_type'=>'2',
            'param'=>serialize($param),
            'redirect_url'=>'',
        ]);
        $payData=[];
        $client_ip=isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';// 客户地址
        switch($type){
            case  'wx_app':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                    'timeout_express' => time() + 600,// 表示必须 600s 内付款
                    'amount'    => $amount,// 单位为元 ,最小为0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// 客户地址
                ];
                break;
            case 'wx_wap':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                    'timeout_express' => time() + 600,// 表示必须 600s 内付款
                    'amount'    => $amount,// 单位为元 ,最小为0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// 客户地址
                    'scene_info' => [
                        'type' => 'Wap',// IOS  Android  Wap  腾讯建议 IOS  ANDROID 采用app支付
                        'wap_url' => 'helei112g.github.io',//自己的 wap 地址
                        'wap_name' => '测试充值',
                    ]
                ];
                break;
            case 'wx_pub':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                    'timeout_express' => time() + 600,// 表示必须 600s 内付款
                    'amount'    => $amount,// 单位为元 ,最小为0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// 客户地址
                    'openid' => $openid,
                    'product_id' =>$product_id,
                    'store_id' => ''
                ];
                break;
            case 'wx_qr':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                    'timeout_express' => time() + 600,// 表示必须 600s 内付款
                    'amount'    => $amount,// 单位为元 ,最小为0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// 客户地址
                    'openid' => $openid,
                    'product_id' =>$product_id,
                ];
                break;

        };
        return $payData;
    }

    /**
    $data = [
    'out_trade_no' => '14935385689468',
    'total_fee' => '3.01',
    'refund_fee' => '3.01',
    'refund_no' => $refundNo,
    'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE:可用余额退款  REFUND_UNSETTLED:未结算资金退款（默认）
    ];
     */
    /**
     * 微信退款
     * @param $data
     * @return array
     */
    public function wx_refund($data){

        try {
            $ret = Refund::run(Config::WX_REFUND, $this->PayConfig->getConf('wxpay'), $data);
            Log::error($ret);
            if($ret['return_code']=='SUCCESS'){
                return ['code' => 1, 'data' => $ret, 'msg' => '退款成功'];
            }else{
                return ['code' => 0, 'data' => $ret, 'msg' => $ret['msg']];
            }

        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
    }




    /**
     * 创建支付订单号
     * @return string
     */
    private function create_order_no(){
        $microtime_str = microtime();
        $microtime = substr($microtime_str,strpos($microtime_str,'.')+1,6);//获取当前时间的毫秒级
        return time() .$microtime.rand(1000, 9999);
    }






}

