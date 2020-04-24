<?php
namespace app\wap\controller;

class Index extends Base{
	/**
	* @Title: index
	* @Description: 首页
	* @param
	* @return
	 */
  public function index(){
     $this->set_seo('羊羊商城');
     return view();
  }
  public function login(){
      if(get_uid()){
          $this->redirect('wap/index/index');
      }
      if(is_weixin()){
          //直接微信登录
          $this->redirect('api/oalogin/login');
      }

      $this->set_seo('登录_羊羊商城');
     return view();
  }
  public function register(){
     $this->set_seo('注册_羊羊商城');
	 //$agentpand = $_GET['agentpand'];
     return view();
  }
	public function protocol(){
        $this->set_seo('羊羊商城');
	   return view();
	}
	public function share(){
        $this->set_seo('完善资料_羊羊商城');
	   return view();
   }
	public function s(){
      $this->set_seo('商品详情');
		return view();
	}
	public function p(){
      $this->set_seo('注册协议');
		return view();
	}
	public function r(){
      $this->set_seo('注册');
		return view();
	}
}
