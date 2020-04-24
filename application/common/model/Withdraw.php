<?php

namespace app\common\model;
use app\common\service\Payment;
use app\common\service\Users;
use app\common\service\Friends;
use think\Model;
use think\Db;
class Withdraw extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    public function tixian($uid,$money,$type){

        $fx_tixian=config('fx_tixian');
        $system_money=round($money*$fx_tixian/100,2);
        $user_money=round($money-$system_money,2);

        if($money<100){
           return ['code'=>0,'msg'=>'提现金额不能低于100元'];
        }

        if($money%100>0){
            return ['code'=>0,'msg'=>'提现金额必须是100的倍数'];
        }

        //每天提币不超过两次
        $ttime = strtotime(date('Y-m-d',time()));
        $map['create_time'] = array('gt',$ttime);
        $map['uid'] = $uid;
        $map['status'] = array('lt',2);
        /*$listnum = Db::name('withdraw')->where($map)->select();
        $zongmoney = Db::name('withdraw')->where($map)->sum('money');
        if(count($listnum)>=1){
            return ['code'=>0,'msg'=>'每天只能提现一次'];
        }
        if($zongmoney>50000){
            return ['code'=>0,'msg'=>'每天提现金额不能超过50000元'];
        }*/

        //查询 当前用户的钱 是不是够提现
        $info=Db::name('member')->where(['id'=>$uid])->find();
        if($type==1){
            if(!$info['zfb_name'] || !$info['zfb_hao']){
                return ['code'=>0,'msg'=>'请先正确绑定提现支付账号'];
            }
            $payname = "支付宝";
            $name = $info['zfb_name'];
            $hao = $info['zfb_hao'];
            $yinhang = "";
        }else{
            if(!$info['huming'] || !$info['kahao']){
                return ['code'=>0,'msg'=>'请先正确绑定提现银行卡账号'];
            }
            $payname = "银行卡";
            $name = $info['huming'];
            $hao = $info['kahao'];
            $yinhang = $info['yinhang'];
        }
        $zmoney =$info['integral'];
        if($zmoney < $money){
            return ['code'=>0,'msg'=>'可提现金额不足'];
        }


        //给用户减钱
        $Users= new Users();
        $Users->userDecIntegral($uid,$money);

        //增减记录
        $IntegralLog = new IntegralLog();
        $data = $IntegralLog->operate($uid,-$money,25,1,$payname.'提现');

        $status=0;
        $payment_no='';
        $id = $this->insert([
            'uid'=>$uid,
            'money'=>$money,
            'status'=>$status,
            'type'=>$type,
            'username'=>$info['account'],
            'kh_hang'=>$yinhang,
            'zfb_name'=>$name,
            'zfb_hao'=>$hao,
            'payment_no'=>$payment_no,
            'feilv'=>$fx_tixian,
            'system_money'=>$system_money,
            'user_money'=>$user_money,
            'update_time'=>time(),
            'create_time'=>time()
        ],false,true);

        if($id){
            return ['status'=>1,'msg'=>'申请成功'];
        }
    }


    public function dakuan($id){
       $info = $this->find($id);
       if($info['status']==0){
           $info->status=1;
           if($info->type ==1){

               $res = $this->pay($info,$info['user_money']);
               if($res['status'] == 1){
                   $info->status=1;
                   $info->payment_no=$res['payment_no'];
                   if($info->save()){
                       return ['status'=>1,'msg'=>'打款成功'];
                   }else{
                       return ['status'=>0,'msg'=>'打款保存失败'];
                   }
               }else{
                   return ['status'=>0,'msg'=>$res['msg']];
               }


           }else{
               if($info->save()){
                   return ['status'=>1,'msg'=>'打款成功'];
               }else{
                   return ['status'=>0,'msg'=>'打款保存失败'];
               }
           }
          /* */
          /* */
       }else{
           return ['status'=>0,'msg'=>'该状态不能打款'];
       }
    }

    public function jjdakuan($id){
        $info = $this->find($id);
        if($info['status']==0){
            $info->status=2;
            if($info->save()){
                $User = new Users();
              //  $Friends = new Friends();
                //会员退钱
                $User->userIncIntegral($info['uid'],$info['money']);
                //记录
                //增减记录
                $IntegralLog = new IntegralLog();

                $data = $IntegralLog->operate($info['uid'],$info['money'],26,1,'提现失败金额退回');
               // $Friends->addrebate($info['uid'],$info['username'],4,$info['money'],"提现失败金额退回");
                return ['status'=>1,'msg'=>'拒绝成功'];
            }else{
                return ['status'=>0,'msg'=>'拒绝失败'];
            }
        }
    }

    private function pay($user,$money,$desc='用户提现'){
        // $user =  Db::name('oauth_user')->where(['from'=>'weixin','uid'=>$uid])->find();


        $Payment = new Payment();
        $ret= $Payment->ali_transfer([
            'trans_no' => time(),
            'payee_type' => 'ALIPAY_LOGONID',
            'payee_account' => $user['zfb_hao'],// ALIPAY_USERID: 2088102169940354      ALIPAY_LOGONID：aaqlmq0729@sandbox.com
            'amount' => $money,
            'remark' => $desc,
            'payer_show_name' =>$user['zfb_name'],
        ]);
        /* $ret = $Payment->wx_transfer(
             [
                 'trans_no' => time(),
                 'openid' => $user['openid'],
                 'check_name' => 'NO_CHECK',// NO_CHECK：不校验真实姓名  FORCE_CHECK：强校验真实姓名   OPTION_CHECK：针对已实名认证的用户才校验真实姓名
                 'payer_real_name' => $user['name'],
                 'amount' => $money,
                 'desc' => $desc,
                 'spbill_create_ip' => get_client_ip(1),
             ]
         );*/
        if($ret['code']==0){
            return ['status'=>0,'msg'=>$ret['msg']];
        }else{
            return ['status'=>1,'payment_no'=>$ret['data']];
        }



    }

    /*private function pay($uid,$money,$desc='用户提现'){
        $user =  Db::name('oauth_user')->where(['from'=>'weixin','uid'=>$uid])->find();
        if(!$user){
            return ['status'=>0,'msg'=>'无此微信用户'];
        }
        $Payment = new Payment();
        $ret = $Payment->wx_transfer(
            [
                'trans_no' => time(),
                'openid' => $user['openid'],
                'check_name' => 'NO_CHECK',// NO_CHECK：不校验真实姓名  FORCE_CHECK：强校验真实姓名   OPTION_CHECK：针对已实名认证的用户才校验真实姓名
                'payer_real_name' => $user['name'],
                'amount' => $money,
                'desc' => $desc,
                'spbill_create_ip' => get_client_ip(1),
            ]
        );
        if($ret['code']==0){
            return ['status'=>0,'msg'=>$ret['msg']];
        }else{
            return ['status'=>1,'payment_no'=>$ret['data']];
        }



    }*/
}

