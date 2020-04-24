<?php

namespace app\admin\controller;
use app\common\model\Withdraw;
use think\Db;

class Withdrawal extends Base
{

    public function index() {
        $id   = input('id',4);
        $map=[];
        if($id != 4){
            $map['think_withdraw.status']=$id;
        }

        $this->assign('id',$id);

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('withdraw')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $Withdraw=new Withdraw();
        if($id==1){
            $lists =$Withdraw->field('think_withdraw.*,think_member.nickname')->join('think_member','think_member.id=think_withdraw.uid','left')->where($map)->page($Nowpage, $limits)->order('update_time desc')->select();
        }elseif($id==0){
            $lists =$Withdraw->field('think_withdraw.*,think_member.nickname')->join('think_member','think_member.id=think_withdraw.uid','left')->where($map)->page($Nowpage, $limits)->order('create_time asc')->select();
        }else{
            $lists =$Withdraw->field('think_withdraw.*,think_member.nickname')->join('think_member','think_member.id=think_withdraw.uid','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
        }


        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        if(input('get.page')){
            return json($lists);
        }

        return $this->fetch();
    }

    public function dakuan($id){
        $Withdraw =  new Withdraw();
        return json($Withdraw->dakuan($id));
    }

    public function jujue_dakuan($id){
        $Withdraw =  new Withdraw();
        return json($Withdraw->jjdakuan($id));
    }

}