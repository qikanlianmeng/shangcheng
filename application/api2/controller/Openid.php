<?php

namespace app\api2\controller;
use think\Db;
//use think\cache\driver\Redis;

class Openid{

  public function getOpenid($appid, $appsecret)
  {
    $SERVER_NAME = $_SERVER['SERVER_NAME'];
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
    $redirect_uri = urlencode('http://' . $SERVER_NAME . $REQUEST_URI);
    $code = input('param.code');
    if (!$code) {
      // 网页授权当scope=snsapi_userinfo时才会提示是否授权应用
      $autourl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_base&state=STATE&connect_redirect=1#wechat_redirect";
      header("location:$autourl");
    } else {
      // 获取openid
      $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
      $row = $this->posturl($url);
      if(isset($row['openid'])){
      	cookie('openid',$row['openid']);
      }
      $openid=cookie('openid');
     
      return $openid;
    }
  }
  public function posturl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    $jsoninfo = json_decode($output, true);
    return $jsoninfo;
  }


}