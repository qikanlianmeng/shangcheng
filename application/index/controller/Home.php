<?php
namespace app\index\controller;
use think\Controller;
class Home  extends Controller{
	public function home(){
		return "登录成功";
	}
	public function wxlogin(){
		if(get_uid()) $this->redirect('home');
		return view();
	}
	
}
