<?php

namespace app\wap\controller;

use think\Db;

class Yang extends Base{



    public function index(){



        return view();

    }

    public function game(){



        return view();

    }

    public function login(){



        return view();

    }

    public function register(){



        return view();

    }



    public function tender_list(){

        return view();

    }

    public function tender_desc(){

        return view();

    }
    public function sheep_list(){

        return view();

    }
    public function sheep_detail(){

        return view();

    }
    public function receive(){
        return view();
    }
    public function ranch(){
        return view();
    }
    public function receive_detial(){
        return view();
    }
    public function contact($id){
        if (!$uid = get_uid()) {
            $this->redirect('wap/index/login');
        }
        $info = Db::name('yang_tender_receive')->where(['uid'=>$uid,'id'=>$id])->find();
        if(!$info){
            return json(['code' => 0, 'msg' => '信息不存在']);
        }
        $user=Db::name('member')->find($info['uid']);

        $data=[
            'bianhao'=>$info['id'],
            'username'=>$user['account'],
            'phone'=>$user['account'],
            'order_id'=>$info['id'],
            'order_num'=>$info['receive_num'],
            'order_price'=>$info['receive_num']*$info['receive_price'],
        ];
        $this->assign('data',$data);
        return view();
    }



    public function camera_lists(){   return view();}

    public function camera(){   return view();}



}