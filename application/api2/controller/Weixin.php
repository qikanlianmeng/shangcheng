<?php
namespace app\api\controller;
use app\common\model\WxinQrcode;
use app\common\service\Msg;
use think\Db;
class Weixin extends Base{
    private  $msg='';
    public $wx_config=[];
    public $receive;
    public function _initialize(){
        parent::_initialize();
        $this->msg=config('wx_default_reply');
        $this->wx_config= [
            'token'             => config('wx_token'), // 填写你设定的key
            'appid'             => config('wx_appid'), // 填写高级调用功能的app id, 请在微信开发模式后台查询
            'appsecret'         => config('wx_appSecret') // 填写高级调用功能的密钥
        ];
        $this->receive=new \Wechat\WechatReceive($this->wx_config);
    }
    public function index(){
        $Receive=$this->receive;
        $validres=$Receive->valid();
        $type = $Receive->getRev()->getRevType();
        //file_put_contents(RUNTIME_PATH.'aa.txt',var_export($postStr,true));
        switch($type) {
            //文字消息
            case $Receive::MSGTYPE_TEXT:
                $this->receiveText($Receive->getRevContent());
                break;
            //事件
            case $Receive::MSGTYPE_EVENT:
                $event=$Receive->getRevEvent();
                $TYPE=$event['event'];
                $this->receiveEvent($TYPE,$event);
                break;
            case $Receive::MSGTYPE_IMAGE:
                break;
            default:
                $Receive->text($this->msg)->reply();
        }

    }
    /**
     * 接收事件消息
     */
    private function receiveEvent($TYPE,$event){
        $Receive=$this->receive;
        $content='';
        switch ($TYPE){
            case "subscribe":
                $content=config('wx_focus');
                $openid=$Receive->getRevFrom();
                $SceneId=$Receive->getRevSceneId();
                if($openid){
                    //微信用户信息
                    $WechatUser =  new \Wechat\WechatUser($this->wx_config);
                    $userinfo = $WechatUser->getUserInfo($openid);
                    $this->addWxinUser($userinfo,$SceneId);
                }
                break;
            case "unsubscribe":
                $content = "取消关注";
                $openid=$Receive->getRevFrom();
                $this->changeUserStatus($openid);
                break;
            case "SCAN":
                $openid=$Receive->getRevFrom();
                $SceneId=$Receive->getRevSceneId();
                if(!empty($event['key']) && $openid){
                    //微信用户信息
                    $WechatUser =  new \Wechat\WechatUser($this->wx_config);
                    $userinfo = $WechatUser->getUserInfo($openid);
                    $this->addWxinUser($userinfo,$SceneId);
                }
                $content = $this->msg;
                break;
            case "CLICK":
                $content=$this->replyInfo($event['key']);
                break;
            default:
                $content = $this->msg;
                break;
        }
        if(is_array($content)){
            $Receive->news($content)->reply();
        }else{
            $Receive->text($content)->reply();
        }
    }
    /**
     *更改用户关注状态
     */
    public function changeUserStatus($openid){
        $model=db('oauth_user');
        if($model->where('openid', $openid)->find()){
            $model->where('openid', $openid)->update(['status' =>'2']);
        }
    }
    /**
     * 添加微信用户信息
     */
    private function addWxinUser($userinfo,$SceneId){
        $model=db('oauth_user');
        $userinfo['nickname'] = preg_replace('~\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]~', '', $userinfo['nickname']);
        $ret=$model->where(array("from"=>'weixin',"openid"=>$userinfo['openid']))->find();
        if(!$ret){
            $referid=0;
         /*   if($SceneId){
                $WxinQrcode= new WxinQrcode();
                $res = $WxinQrcode->get(['scene_id'=>$SceneId]);
                if($res){
                    switch ($res->apply_type){
                        case 0://地推的
                            //给这个$res->apply_key 的人加1
                            Db::name('member')->where(['id'=>$res->apply_key])->setInc('tuijian_guanzhu');
                            $referid=$res->apply_key;
                            break;
                        default:
                            Db::name('member')->where(['id'=>$res->apply_key])->setInc('tuijian_guanzhu');
                            $referid=$res->apply_key;
                            Msg::send(7,$referid,['tuijianren'=>'您自己','beituijian'=>$userinfo['nickname']]);
                            break;
                    }
                }
            }*/
            $oauthUser= [
                'from' => 'weixin',
                'name' => $userinfo['nickname'],
                'expires_date' => (int)(time()+7*24*3600),
                'openid' => $userinfo['openid'],
                'status'=>1,
              //  'referid'=>$referid
            ];
            db('oauth_user')->insert($oauthUser,false,true);
        }
    }
    /**
     * 接收文本消息
     */
    private function receiveText($content){
        $model=db('wxin_autoreply');
        $autoreply=$model->where(['status'=>0])->order('id asc')->select();
        $value=null;
        foreach ($autoreply as $v){
            $keywords =explode('|',trim($v['key_word'],'|'));
            foreach($keywords as $k){
                if(strpos($content,$k) !== false){
                    $value=$v['content'];
                    break 2;
                }
            }
        }
        if($value){
            $this->receive->text($value)->reply();
        }else{
            $this->receive->text($this->msg)->reply();
        }
    }
    /**
     * 菜单回复内容
     */
    private function replyInfo($key){
        $data=db('wxin_menu')->field('info.*,think_wxin_menu.type')->join('think_wxin_reply_info info','think_wxin_menu.id=info.wxin_menu_id')->where(array('key_value'=>$key))->find();
        if($data['description']){
            $content = trim($data['description']);
        }else{
            $content = $this->msg;
        }
        return $content;
    }
    /**
     * 临时二维码生成
     */
    public function spreadCodePro(){
        if(session('login_value') && session('outtime')>time()){
            $codeInfo=db('live_wxin_code')->where(['code'=>session('login_value')])->find();
            if($codeInfo['token'] && $codeInfo['code']){
                return ['status'=>'1','token'=>$codeInfo['token'],'code'=>$codeInfo['code']];
            }
        }
        $WechatUser =  new \Wechat\WechatUser($this->wx_config);
        $access_token=$WechatUser->getAccessToken();
        $time=time();
        $rand=$time.rand(1000, 9999);
        $scene_id=substr($rand, 5);
        $outtime=$time+600;
        if($access_token){
            $proTicketUrl="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";
            $arr='{"expire_seconds": '.$outtime.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
            $ticketInfo=json_decode($this->https_request($proTicketUrl,$arr),true);
            $ip=request()->ip();
            db('live_wxin_code')->insert(['create_time'=>$time,'code'=>$scene_id,'token'=>$ticketInfo['ticket'],'ip'=>$ip]);
            session('login_value', $scene_id);
            session('outtime', $outtime);
            return ['status'=>'1','token'=>$ticketInfo['ticket'],'code'=>$scene_id];
        }else{
            return ['status'=>'0','token'=>''];
        }
    }
    /**
     * 外部调用接口返回微信分享密钥
     */
    public function getJsSign($purl=''){
        if($purl==''){
            $purl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $WechatScript=new \Wechat\WechatScript($this->wx_config);
        return  $WechatScript->getJsSign($purl);
    }
    /**
     * curl请求
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
}
