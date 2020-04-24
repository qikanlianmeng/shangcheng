<?php
namespace app\home\model;
use think\Model;
use think\Db;

class WxinModel extends Model
{
	protected $name = 'wxin_menu';
	protected $APPID='';
	protected $APPSECRET='';
	private $token='txjyszcpscores';
	private $tokenurl = 'https://api.weixin.qq.com/cgi-bin/token';
    private $userurl='https://api.weixin.qq.com/cgi-bin/user/info';
    private $ticketurl='https://api.weixin.qq.com/cgi-bin/ticket/getticket';
    const msg='欢迎到羊币商城!';
    public function __construct($APPID='',$APPSECRET=''){
    	parent::__construct();
    	if(!$APPID||!$APPSECRET){
	    	$config=db('auth_config')->where(array('type'=>'weixin'))->value('config');
	    	if($config){
	    		$config=unserialize($config);
	    		$this->APPID=$config['app_key'];
	    		$this->APPSECRET=$config['app_secret'];
	    	}else{
	    		return '微信配置出现错误';
	    	}
    	}elseif($APPID&&$APPSECRET){
    		$this->APPID=$APPID;
    		$this->APPSECRET=$APPSECRET;
    	}else{
    		return '微信配置出现错误';
    	}
    }
	/**
	 * 微信校验
	 */
	public function signatureInfo(){
		$echostr=input('echostr','');
		if (!$echostr) {
			$this->responseMsg();
		}else{
			if($this->valid()){
				echo $echostr ;
			}
		}
	}
	/**
	 * 验证签名
	 */
	private function valid() {
		$signature=input("signature");
		$timestamp=input("timestamp");
		$nonce=input("nonce");
		$tmpArr=[$this->token, $timestamp, $nonce];
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 响应消息处理
	 */
	private function responseMsg(){
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if (!empty($postStr)){
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj->MsgType);
			if($RX_TYPE=="event"){
				$result = $this->receiveEvent($postObj);
			}elseif ($RX_TYPE=="text"){
				$result = $this->receiveText($postObj);     //接收文本消息
			}
			if($result){
				//输出消息给微信
				echo $result;
			}
		}
	}
	/**
	 * 接收事件消息
	 */
	private function receiveEvent($object){
		$content = "";
		$TYPE = trim($object->Event);
		switch ($TYPE){
			case "subscribe":
				$content="欢迎关注羊币商城！";
				$access_token=$this->get_token();
				$openid = $object->FromUserName;
				if($access_token){
					//微信用户信息
					$user=$this->getWxinInfo($openid,$access_token);
				}
				break;
			case "unsubscribe":
				$content = "取消关注";
				break;
			case "SCAN":
				$content = "欢迎关注羊币商城！！！";
				break;
			case "CLICK":
				$content=$this->replyInfo($object->EventKey);
				break;
			default:
				$content = "欢迎关注羊币商城！";
				break;
		}
		if(is_array($content)){
			$result = $this->transmitNews($object, $content);
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}
	/**
	 * 菜单回复内容
	 */
	private function replyInfo($key){
		$data=$this->field('info.*,think_wxin_menu.type')->join('think_wxin_reply_info info','think_wxin_menu.id=info.wxin_menu_id')->where(array('key_value'=>$key))->find();
		$content='';
		if($data['type']=='1'){
			$content = $data['description'];
		}elseif($data['type']=='3'){
			$content[] = array("Title"=>$data['title'],  "Description"=>$data['description'], "PicUrl"=>$data['pic_url'], "Url" =>$data['url']);
		}else{
			$content=self::msg;
		}
		return $content;
	}
	/**
	 * 接收文本消息
	 */
	private function receiveText($object){
		$model=db('wxin_autoreply');
		$keyword = trim($object->Content);
		$value=$model->where('key_word','like',"%{$keyword}%")->value('content');
		if($value){
			$result = $this->transmitText($object, $value);
			return $result;
		}else{
			return self::msg;
		}
	}
	/**
	 * 获取微信用户信息
	 */
	private function getWxinInfo($openid,$access_token){
		$url=$this->userurl;
		$params=['access_token'=>$access_token,'openid'=>$openid,'lang'=>'zh_CN'];
		$url.='?'.http_build_query($params);
		$wxuser = $this->https_request($url);
		$arrUser=json_decode($wxuser,true);
		if(isset($arrUser['errcode'])){
			$this->logger('loginLog.txt',"获取微信用户信息失败！");
			return false;
		}
		return $arrUser;
	}
	/**
	 * 回复文本消息
	 */
	private function transmitText($object, $content){
		$xmlTpl ="<xml><ToUserName><![CDATA[%s]]></ToUserName>";
		$xmlTpl.="<FromUserName><![CDATA[%s]]></FromUserName>";
		$xmlTpl.="<CreateTime>%s</CreateTime>";
		$xmlTpl.="<MsgType><![CDATA[text]]></MsgType>";
		$xmlTpl.="<Content><![CDATA[%s]]></Content></xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
		return $result;
	}
	/**
	 * 回复图文消息
	 */
	private function transmitNews($object, $newsArray){
		if(!is_array($newsArray)){
			return;
		}
		$itemTpl = "<item><Title><![CDATA[%s]]></Title>";
		$itemTpl.="<Description><![CDATA[%s]]></Description>";
		$itemTpl.="<PicUrl><![CDATA[%s]]></PicUrl>";
		$itemTpl.="<Url><![CDATA[%s]]></Url></item>";
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>";
		$xmlTpl .="<FromUserName><![CDATA[%s]]></FromUserName>";
		$xmlTpl .="<CreateTime>%s</CreateTime>";
		$xmlTpl .="<MsgType><![CDATA[news]]></MsgType>";
		$xmlTpl .="<ArticleCount>%s</ArticleCount>";
		$xmlTpl .="<Articles>$item_str</Articles></xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
		return $result;
	}
	/**
	 * 外部调用接口返回微信分享密钥
	 */
	public function getWxSharePacks($url=''){
		if($url==''){
			$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		}
		$jsapiTicket = $this->wx_ticket('jsapi_ticket');
		if(!$jsapiTicket){
			return array('status'=>0,'data'=>'无法获取票据');
		}
		$timestamp = time();
		$nonceStr = $this->createNonceStr();
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
		$signature = sha1($string);
		$signPackage = array(
				"appId"     => $this->APPID,
				"nonceStr"  => $nonceStr,
				"timestamp" => $timestamp,
				"url"       => $url,
				"signature" => $signature,
				"rawString" => $string,
				'jsapiTicket'=>$jsapiTicket
		);
		return json(array('status'=>0,'data'=>$signPackage));
	}
	/**
		* 产生随机串
		*/
	private function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
	/**
		* js-sdk接口jsapi_ticket票据获得
		*/
	private function wx_ticket($type='access_token'){
		$access_token = Cache::get('access_token') ; $jsapi_ticket = Cache::get('jsapi_ticket') ;
		$APPID = $this->APPID ;  $APPSECRET = $this->APPSECRET;
		if(!$access_token){
			//重新获取
			$access_token = $this->get_token() ;
			if(!$access_token) {
				//写入日志，抛出错误
				return false;
			}
			Cache::set('access_token',$access_token,7000);//缓存数据
		}
		if($type == 'access_token' ) return $access_token ;
		//获取票据
		if(!$jsapi_ticket){
			//重新获取
			$url=$this->ticketurl."?access_token=$access_token&type=jsapi";
			$res=$this->https_request($url);
			$data = json_decode($res,true);
			$jsapi_ticket = $data['ticket'] ;
			if(!$jsapi_ticket) {
				//写入日志，抛出错误
				return false;
			}
			Cache::set('jsapi_ticket',$jsapi_ticket,7000);//缓存数据
		}
		if($type == 'jsapi_ticket' ) return $jsapi_ticket ;
	}
	/**
	 * https的get或 post,当data=null时为get请求，data是post的参数
	 */
	private function https_request($url, $data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	/**
	 * 获取access_token
	 */
	private function get_token() {
		$model=db('auth_config');
			$url=$this->tokenurl;
			$params=array(
					'grant_type'=>'client_credential',
					'appid'=>$this->APPID,
					'secret'=>$this->APPSECRET
			);
			$url.='?'.http_build_query($params);
			$json=$this->https_request($url);
			$arr= json_decode($json, true);
			if(isset($arr['errcode'])){
				return false;
			}
			return $arr["access_token"];
	}
	//日志记录
	public function logger($name,$log_content){
		file_put_contents($name, $log_content.'   '.date("Y-m-d h:i:sa").PHP_EOL, FILE_APPEND);
	}
}