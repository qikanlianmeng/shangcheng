<?php
namespace app\api\controller;
use app\common\service\Users;
use think\Db;
use app\common\model\OauthLogin;

class Oalogin extends Base{
    /**
     * 第三方登录地址
     * @param OauthLogin $model
     * @param $type  类型(qq、weixin、wx_qrcode)
     */
    public function login($type = 'weixin'){
        empty($type) && $this->error('参数错误');
        if(isset($_SERVER['HTTP_REFERER'])){
            session('login_http_referer',$_SERVER["HTTP_REFERER"]);
        }
        $model=new OauthLogin();
        try {
            $model->OauthInit($type);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }
    /**
     * 授权回调地址
     * @param $type  类型(qq、weixin、wx_qrcode)
     */
    public function callback($type = null){
        (empty($type))&&$this->error('参数错误');
        $model=new OauthLogin();
        $user=$model->callback($type);
        if($user['token']){
            $this->loginHandle($user['user_info'], $type, $user['token']);
        }else{
            $this->success('登录失败！',_get_login_redirect());
        }
    }
    /**
     * 登陆
     * @param $user_info
     * @param $type
     * @param $token
     */
    private function loginHandle($user_info, $type, $token){
        $model=db('oauth_user');
        $type=strtolower($type);
        $row=$model->where(array("from"=>$type,"openid"=>$user_info['openid']))->find();

        $user_info['nick'] = preg_replace('~\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]~', '', $user_info['nick']);
        /*
         $ip=request()->ip();
         $nember= array(
            'nickname' => $user_info['nick'],
            'head_img' => $user_info['avatar'],
            'update_time' =>time(),
            'last_login_ip' => $ip,
            'group_id'=>$config['default_user_group'],
            'login_num' =>'1',
            'status'=>'1',
            'closed'=>'0',
            'weixin_account'=>$user_info['nick'],
        );*/
        if(isset($user_info['gender']))$nember['sex']=$user_info['gender'];
        if($row){
            $find_user=db('member')->where(array("id"=>$row['uid']))->find();
            if($find_user){
                if($find_user['closed']=='1'){
                    $this->error('您可能已经被列入黑名单，请联系网站管理员！');exit;
                }else{
                    $Users =  new Users();
                    $Users->autoLogin($find_user);
                    $this->redirect(_get_login_redirect());
                }
            }else{
                //没有资料 去完善资料
                $user_info['id']=$row['id'];
                session('perfect_info',$user_info);
                $this->redirect('wap/Usercenter/perfect_info');

            }


        }else{
            //没有资料 去完善资料
            $oauthUser= array(
                'from' => $type,
                'name' => $user_info['nick'],
                //'access_token' => $token['access_token'],
                //'expires_date' => (int)(time()+7*24*3600),
                'openid' => $user_info['openid'],
                'status'=>0
            );
            if($id = db('oauth_user')->insert($oauthUser,false,true)){
                $user_info['id']=$id;
                session('perfect_info',$user_info);
                $this->redirect('wap/Usercenter/perfect_info');
            }


        }

    }

}