<?php
namespace app\common\model;
use think\Model;
use think\Db;
use anerg\OAuth2\OAuth;
class OauthLogin extends Model
{
	protected $name = 'auth_config';
	protected $scopeValue=['qq'=>'get_user_info,add_share','weixin'=>'snsapi_userinfo','wx_qrcode'=>'snsapi_login'];
	/**
	 * 第三方登录初始化
	 * @param  $oauth_type 类型(qq、weixin、wx_qrcode)
	 * @return \think\response\Redirect 跳转地址
	 */
	public function OauthInit($oauth_type,$site_type='mobile') {
		$oauth_type=strtolower($oauth_type);
	    $oainfo=$this->getConfigInfo($oauth_type);
		$callback=url('api/Oalogin/callback',['type'=>$oauth_type],true,true);
		$config = [
		  'app_key'    => $oainfo['app_key'],
		  'app_secret' => $oainfo['app_secret'],
		  'scope'      =>$this->scopeValue[$oauth_type],
		];
		$config['callback']=[
		   'default' => $callback,
		   'mobile'  => $callback
		];
		$OAuth  = OAuth::getInstance($config, $oauth_type);
		$OAuth->setDisplay($site_type); //此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问
		$url=$OAuth->getAuthorizeURL();
		if (!headers_sent()){
			header("refresh:0;url={$url}");
		} else {
			$str="<meta http-equiv='Refresh' content='0;URL={$url}'>";
		}
	}
	/**
	 * 回调获取用户信息
	 * $channel 类型(qq、weixin、wx_qrcode)
	 */
	public function callback($channel) {
		$data=[];
		$channel=strtolower($channel);
		$callback=url('api/Oalogin/callback',['type'=>$channel],true,true);
		$oainfo=$this->getConfigInfo($channel);
		$config = [
		  'app_key'    => $oainfo['app_key'],
		  'app_secret' => $oainfo['app_secret'],
		  'scope'      =>$this->scopeValue[$channel],
		];
		$config['callback']=[
		   'default' => $callback,
		   'mobile'  => $callback
		];
		$OAuth=OAuth::getInstance($config, $channel);
		$data['token']=$OAuth->getAccessToken();
		$data['user_info']= $OAuth->userinfo();
		return $data;
	}
	/**
	 *获取配置信息
	 */
	private function getConfigInfo($type){
		if(!in_array($type, array('qq','weixin','wx_qrcode'))) {
			throw new \Exception('类型有误',0);
		}
		$oainfo=$this->where(array('type'=>$type))->value('config');
		if(!$oainfo){
			throw new \Exception('表里没有相应的配置信息',0);
		}
		$oainfo=unserialize($oainfo);
		return $oainfo;
	}
}
