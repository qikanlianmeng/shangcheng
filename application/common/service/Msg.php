<?php
// +----------------------------------------------------------------------
// | 消息发送通用类
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | date:	2017.9. 11
// +----------------------------------------------------------------------
// | Author: wpy
// +----------------------------------------------------------------------
namespace app\common\service;

use think\Db;
use Aliyun\SmsDemo;
use phpmailer\PHPMailer;
use phpmailer\Exception;

class Msg {
    private static $msginfo;      //要发送的消息基本信息（包括发送权限、模板等）
    private static $config;	   //网站基本配置
    private static $back = array('code'=>0,'msg'=>'');    //返回信息

    private static function start($id){
        self::$config = cache('db_config_data');
        self::$msginfo = Db::name("msg_config")->where('id',$id)->find();
        if(empty(self::$msginfo)){
            self::$back['msg'] = '发送的消息标识不存在';
            return self::$back;
        }
    }

    /*发送消息*/
    //主要发送在用户  登录后  进行一些操作的响应消息
    //参数分别为: 后台消息标识id，手机/邮箱，与后台对应消息设置的预定义变量一一对应的参数数组
    static function send($id,$uid,$param){
        self::start($id);
        if($uid <1){
            self::$back['msg'] = '接收信息用户id不能为空';
            return self::$back;
        }
        $mid = 0;//发送者id默认为0，即系统发送
        $userinfo = Db :: name("member")->where('id',$uid)->find();
        if(self::check_param(unserialize(self::$msginfo['param']),$param) == false){
            return self::$back;
        }

        $auth = self::$msginfo['auth'];//消息发送权限
        //短信发送
        if($auth & 1){
            if(self::check_tpinfo('sms') === true){
                $tpinfo = self::set_tpinfo('sms',$param);
                self::sms($tpinfo['tpid'],$userinfo['mobile'],$tpinfo);
            }
        }
        //邮件发送
        if($auth & 2){
            if(self::check_tpinfo('email') === true){
                $tpinfo = self::set_tpinfo('email',$param);
                self::email($userinfo['email'],$tpinfo['title'],$tpinfo['content']);
            }
        }
        //站内信发送
        if($auth & 4){
            if(self::check_tpinfo('mail') === true){
                $tpinfo = self::set_tpinfo('mail',$param);
                self::mail($mid,$uid,$tpinfo['title'],$tpinfo['content']);
            }
        }
        //微信推送
        if($auth & 8){
            if(self::check_tpinfo('weichat') === true){
                $tpinfo = self::set_tpinfo('weichat',$param);
                self::weichat($uid,$tpinfo['tpid'],$tpinfo['param_data']);
            }
        }
        self::$back['code'] = 1;
        self::$back['msg'] = '消息已发送';
        return self::$back;
    }
    //不登录情况下发送短信
    static function send_sms($id,$phone,$param){
        /*		self::start($id);
                if(self::check_param(unserialize(self::$msginfo['param']),$param) == false){
                    return self::$back;
                }
                $tpinfo = self::set_tpinfo('sms',$param);*/
        $res = self::sms($phone,$param);
        $back['code'] = self::$back['detail']['sms']['code'];
        $back['msg'] = self::$back['detail']['sms']['info'];
        return $back;
    }

    //不登录情况下发送邮件
    static function send_email($id,$email,$param){
        self::start($id);
        if(self::check_param(unserialize(self::$msginfo['param']),$param) == false){
            return self::$back;
        }
        $tpinfo = self::set_tpinfo('email',$param);
        $res = self::email($email,$tpinfo['title'],$tpinfo['content']);
        $back['code'] = self::$back['detail']['email']['code'];
        $back['msg'] = self::$back['detail']['email']['info'];
        return $back;
    }

    /*短信发送*/
    //$code		短信平台模板id
    //$phone	接收消息的手机号
    //$param	与指定模板中预定义变量对应的  数组
    private static function sms($phone,$param){
        if(empty($phone)){
            self::$back['detail']['sms']['code'] = 0;
            self::$back['detail']['sms']['info']   = '手机号不能为空';
        }else{
            if(!isMobile($phone)){
                self::$back['detail']['sms']['code'] = 0;
                self::$back['detail']['sms']['info']   = '手机号不合法';
            }else{


                /**
                 * 传媒短信平台
                 */
                if($param['content']){
                    $content = $param['content'];
                }else{
                    $content = "您的验证码是:" . $param['code'] . ".请不要把验证码泄露给其他人.";
                }

                $time=date('YmdHis',time());//时间戳，系统当前时间字符串，年月日时分秒
                $md5=md5("yskshop"."yskshop".$time);//MD5加密，账号+密码+时间戳
                $content="【天佑众成商城】".$content;//短信内容

                $curl = curl_init();
                //设置抓取的url
                curl_setopt($curl, CURLOPT_URL, 'http://120.77.14.55:8888/v2sms.aspx?');
                //设置头文件的信息作为数据流输出
                //curl_setopt($curl, CURLOPT_HEADER, 1);
                //设置获取的信息以文件流的形式返回，而不是直接输出。
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                //设置post方式提交
                curl_setopt($curl, CURLOPT_POST, 1);
                //设置post数据
                $post_data = array(
                    'action' => 'send',
                    'userid' => '11344',
                    'timestamp'=>$time,
                    'sign'=> $md5,
                    'mobile' => $phone,
                    'content' => $content,
                    'sendtime'=>'',
                    'extno'=>'',
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                //执行命令
                $data = curl_exec($curl);
                //关闭URL请求
                curl_close($curl);
                //显示获得的数据

                $result=$data;
                //echo $result;exit;
                if (preg_match("|<returnstatus>Success|is", $result)) {
                    self::$back['detail']['sms']['code'] = 1;
                    self::$back['detail']['sms']['info'] = '短信发送成功';
                }else {
                    self::$back['detail']['sms']['code'] = 0;
                    self::$back['detail']['sms']['info'] = '短信发送失败';
                }

            }
        }
    }
    //邮件发送
    private static function email($email,$title,$content){
        if(empty($email)){
            self::$back['detail']['email']['code'] = 0;
            self::$back['detail']['email']['info']   = '邮件地址不能为空';
        }else{
            if(!isEmail($email)){
                self::$back['detail']['email']['code'] = 0;
                self::$back['detail']['email']['info']   = '邮件地址不合法';
            }else{
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();                      												// Set mailer to use SMTP
                    $mail->CharSet = "utf8";			  												// 内容格式
                    $mail->Host = self::$config['email_smtp'];  				  						// 发送方STMP服务器地址
                    $mail->SMTPAuth = true;               												// 是否使用身份验证
                    $mail->Username = self::$config['email_address'];           						// 发送方邮箱地址
                    $mail->Password = self::$config['email_passwd'];            							// SMTP password
                    $mail->SMTPSecure = 'ssl';            												// 使用ssl协议方式
                    $mail->Port = self::$config['email_port'];                  						// 端口

                    $mail->setFrom(self::$config['email_address'],self::$config['email_sender']);     	// 设置发件人名称
                    $mail->addAddress($email);            												// 收件人地址
                    $mail->addReplyTo(self::$config['email_address'],self::$config['email_sender']); 	// 邮件回复地址

                    $mail->isHTML(true);                  // Set email format to HTML
                    $mail->Subject = $title;              // 邮件标题
                    $mail->Body    = $content;            // 邮件内容
                    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用

                    $mail->send();
                    self::$back['detail']['email']['code'] = 1;
                    self::$back['detail']['email']['info']   = '邮件已发送';
                } catch (Exception $e) {
                    self::$back['detail']['email']['code'] = 0;
                    self::$back['detail']['email']['info']   = '邮件发送失败: ' . $mail->ErrorInfo;
                }
            }
        }
    }
    //发送站内信
    private static function mail($send_uid,$receive_uid,$title,$content){
        $back = self::insert_msg($send_uid,$receive_uid,$title,$content);
        if($back){
            self::$back['detail']['mail']['code'] = 1;
            self::$back['detail']['mail']['info']   = '站内信已发送';
        }else{
            self::$back['detail']['mail']['code'] = 0;
            self::$back['detail']['mail']['info']   = '站内信发送失败';
        }
    }

    //发送微信推送
    private static function weichat($uid,$tpid,$data){
        $wx_config = array(
            'token'			=>	self::$config['wx_token'],
            'appid'			=>	self::$config['wx_appid'],
            'appsecret'		=>	self::$config['wx_appSecret'],
            'encodingaeskey'=>	self::$config['wx_encodingAESKey']
        );

        $we=new \Wechat\WechatReceive($wx_config);

        //获取用户openid
        $openid = Db::name("oauth_user")->where(array('from'=>'weixin','uid'=>$uid))->value("openid");
        if(!isset($openid) || !$openid){
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info']   = '用户openid不存在';
            return;
        }
        //获得模板id
        //$template_id = $we->addTemplateMessage($tpid);
        //发送消息
        if(!$tpid){
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info']   = '模板id未填写';
            return;
        }
        //把微信模板的键值对构造成符合微信接口的数组
        $new_data = array();
        foreach($data as $k=>$v){
            $new_data[$v['param_name']] = array("value"	=> $v["param_val"],"color" => $v['param_color']);
        }
        $back = $we->sendTemplateMessage([
            'touser'=>$openid,
            'template_id'=>$tpid,
            //'url'=>url('weixin/shanghu/index','',true,true),
            'data'=>$new_data
        ]);
        if($back['errcode'] === 0){
            self::$back['detail']['weichat']['code'] = 1;
            self::$back['detail']['weichat']['info']   = '微信消息推送成功';
        }else{
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info']   = '微信消息'.$back['errmsg'];
        }
    }

    //向消息表中插入数据
    private static function insert_msg($send_uid,$receive_uid,$title,$content){
        $data = array(
            'receive_uid' => $receive_uid,
            'send_uid'    => $send_uid,
            'title'       => $title,
            'content'     => serialize($content),
            'status'      => 0,
            'send_time'   => time()
        );
        return Db :: name("member_msg")->insert($data);
    }
    //检查传入参数和消息预设变量是否一致，向左匹配
    private static function check_param($tp_param_str,$param){
        $tp_param =explode(',',preg_replace("/\s/","",$tp_param_str));
        if(!empty($tp_param)){
            foreach($tp_param as $k=>$v){
                preg_match_all("/#(.*)#/",$v,$r);
                if(!empty($r[1])){
                    $param_key[] = $r[1][0];
                }else{
                    self::$back['msg'] = '后台消息预定义变量格式错误';
                    return false;
                    break;
                }

            }
            if(!empty($param)){
                $data_key = array_keys($param);
                foreach($param_key as $k=>$v){
                    if(!in_array($v,$data_key)){
                        self::$back['msg'] = '传入的参数数组与消息预设变量不对应';
                        return false;
                        break;
                    }
                }
            }
        }
        return true;
    }
    //检测当前发送渠道模板信息是否为空
    private static function check_tpinfo($type){
        if(!empty(unserialize(self::$msginfo[$type]))){
            return true;
        }else{
            self::$back['detail'][$type]['code'] = 0;
            self::$back['detail'][$type]['info']   = $type.'模板为空';
        }
    }
    //替换模板内容中的预定于变量
    private static function set_tpinfo($type,$param){
        $tpinfo = unserialize(self::$msginfo[$type]);
        if($type == 'weichat'){
            //微信模板内容与其他模板内容不同，不是一个字符串。是一个数组
            foreach($param as $k=>$v){
                foreach($tpinfo['param_data'] as $k2=>$v2){
                    $tpinfo['param_data'][$k2]['param_val'] = str_replace("#$k#",$v,$v2['param_val']);
                }
            }
        }else{
            foreach($param as $k=>$v){
                $tpinfo['content'] = str_replace("#$k#",$v,$tpinfo['content']);
            }
        }

        return $tpinfo;
    }

}
