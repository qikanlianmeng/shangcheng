<?php

namespace app\admin\controller;

use app\common\model\PayConfig;

class Payment extends Base
{
    //支付
    public function test(){
        pe(config('alipay_notify_url'));
      echo serialize( [
            'mch_id'=>'商户ID',
            'app_id'=>'公众账号ID',
            'md5_key'=>'md5 秘钥'
        ]
    );

    }

    public function index()
    {
        $this->assign('lists', PayConfig::all(function($query){
            $query->order('id', 'asc');
        }));
        return $this->fetch();
    }

    public function config()
    {
        $id=input('param.id');
        if(request()->isPost() && !empty($id)){
            $data=request()->post();
            if(PayConfig::where(['id'=>$id])->update(['config'=>serialize($data)])){
                return json(['code' => 1, 'data' => 1, 'msg' => '更新成功']);
            }else{
                return json(['code' => 0, 'data' => 1, 'msg' => '更新失败']);
            }
        }
        $res = PayConfig::get($id);
        $this->assign([
            'title'=>$res->name,
            'param'=>unserialize($res->param),
            'config'=>unserialize($res->config)
        ]);
        return $this->fetch();


    }

    public function status()
    {

        $id = input('param.id');
        $res = PayConfig::get($id);
        if ($res->status == 1) {
            $res->status = 0;
            $flag = $res->save();
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $res->status = 1;
            $flag = $res->save();
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }


    }


}