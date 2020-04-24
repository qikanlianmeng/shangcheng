<?php

namespace app\admin\controller;
use anerg\OAuth2\OAuth;

class Authorise extends Base
{
    //第三方登录配置
   public function index(){
   	    $list=db('auth_config')->where(array('model'=>1))->select();
   	    $this->assign('list',$list);
        return $this->fetch();
   }
   public function config(){
	   	$id=input('id');
	   	$res=db("auth_config")->find($id);
	   	$res['config']=unserialize($res['config']);
	   	$res['param']=unserialize($res['param']);
	   	$this->assign("res",$res);
     	return $this->fetch();
   }
   /**
    * 更新配置
    */
   public function del_config(){
	   	$id=input('id');
	   	$status=input('status');
	   	if($status=='1') {
	   		$status=0;
	   		$err='启用失败';
	   		$suc='启用成功';
	   	}else {
	   		$status=1;
	   		$err='禁用失败';
	   		$suc='禁用成功';
	   	};
	   	if(db("auth_config")->where(array("id"=>$id))->update(array('status'=>$status))){
	   		$this->success($suc);
	   	} else{
	   		$this->error($err);
	   	}
   }
   /**
    * 删除家法
    */
   public function updata_config(){
   		$id=input("id","");
   		$post=input('post.');
   		foreach ($post as $k=>$v){
   			if(!$v){
   				$this->error($k.'不可以是空');
   			}
   		}
   		if(db("auth_config")->where(array("id"=>$id))->update(array("config"=>serialize($post)))){
   			$this->success("更新成功",url('index'));
   		} else{
   			$this->error('更新失败');
   		}
   }
   public function status(){
     return $this->fetch();
   }
}