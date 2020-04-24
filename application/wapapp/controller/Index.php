<?php
namespace app\wapapp\controller;
use app\api2\controller\Share;
use think\Session;
use think\Db;


class Index extends Base{
	/**
	* @Title: index
	* @Description: 首页
	* @param
	* @return
	 */
  public function index(){
     $this->set_seo('天佑众成CRM订货平台');
   
     return view();
  }
  public function login(){
  	if(is_weixin()){
  		$openid=Session::get('openid');
  		if(!$openid){
  			 $open= new Share();
    		$openid=$open->get_userinfo();
      //Cookie::set('openid',$openid);
    		 Session::set('openid',$openid);
  		}
  		
    }
    //以下是之前的代码
    
      if(get_uid()){
          $this->redirect('wapapp/index/index');
      }
      /*if(is_weixin()){
          //直接微信登录
          $this->redirect('api/oalogin/login');
      }*/

      $this->set_seo('登录_天佑众成CRM订货平台');
     return view();
  }
  public function register(){
     $this->set_seo('注册_天佑众成CRM订货平台');
	 //$agentpand = $_GET['agentpand'];
     return view();
  }
	public function protocol(){
        $this->set_seo('天佑众成CRM订货平台');
	   return view();
	}
	public function share(){
        $this->set_seo('完善资料_天佑众成CRM订货平台');
	   return view();
	}
	public function login_verify(){
        $this->set_seo('验证码登录');
	   return view();
	}
	public function goods_s(){
      $this->set_seo('商品详情');
		return view();
	}
	/*public function cesuan($id = '')
  {
    $uid = $id;
    $yusuan = DB::name('order')->where(['user_id' => $uid, 'pay_status' => 1])->where('pay_code', '=', 1)->where('pay_time', '<', 1586238961)->sum('total_amount');
    $jingti = DB::name('pay_log')->where(['uid' => $uid, 'status' => 1])->where('pay_type', '>', 1)->sum('money');
    $dongti = DB::name('log_income')->where(['uid' => $uid])->where('type', 'in', [1, 3, 4, 5, 6, 7, 16, 17, 18, 19, 20, 21, 22])->where('money', '>', 0)->sum('money');
    //动态收益小于2倍的静态收益，还可以继续计算奖金
    $jintai = $yusuan + $jingti;
    dump('静态收益为' . $jintai);
    dump('动态收益为' . $dongti);
    exit;
  }*/
}
