<?php



namespace app\api\controller;

use app\common\service\Payment;
use app\common\model\Withdraw;
use app\common\service\Users;
use app\common\service\Upload;
use think\Db;
use org\Verify;


/**

 * swagger: 用户中心+

 */

class User extends Base

{


	public function login($account, $password) {

		if ($account != '' || $password != '') {
			$Users=new Users();
			$user =$Users->login($account,$password);
			if ($user == false)
			{
				$data['code'] = 0;
				$data['msg'] = '账号或密码错误';
				return json($data);
			} else {
				$Users->autoLogin($user);
				unset($user['password']);
				$data['code'] = 1;
				$data['data'] = $user;
				$data['msg'] = '登录成功';
				\app\common\model\Cart::getInstance()->doLoginHandle($user['id']);//购物车登录整合

				return json($data);

			}

		} else {

			$data['code'] = 0;
			$data['msg'] = '参数错误';
			return json($data);

		}

			

    }

	public function logout(){

		$Users=new Users();
		return json($Users->logout());

	}



	/**
	 * get: id
	 * path: getUserInfo
	 * method: getUserInfo
	 * param: id - {int} 用户id
	 */

	public function getuserinfo() {

		$uid =get_uid();
		if($uid){
			$Users=new Users();
			$data=$Users->getUserInfo($uid);

			//TODO 这里不能用羊币算 羊币还要消耗只能用经验值来算  消费总额

			// 会员等级
			$data['data']->grade=$Users->getUserLevel($data['data']->integral);
			// 消息中心
			$data['data']->unread_msg=$Users->getUserUnreadMsg($uid);
			$data['data']->user_max_yang=config('user_yang_max');
			//TODO 用户头像URL处理
			//TODO 订单状态处理

		}else{
			$data['code'] = 0;
			$data['msg'] = '用户未登录';

		}
		return json($data);
	}


    //会员信息
    public function userinfo(){
        $uid = get_uid();
        if($uid > 0) {
            $Users = new Users();
            $memberinfo = $Users->userinfo($uid);
            unset($memberinfo['password']);

            //$mapd['referid'] = $uid;
            //$tm_num =Db::name('member')->where($mapd)->count();
            //$memberinfo['zhitui'] = $tm_num;//我的直推荐
            $data = ['code' => 1, 'data' => $memberinfo, 'msg' => ''];
        }else{
            $data = ['code' => -1, 'data' => '', 'msg' => '未登录'];
        }
        return json($data);

    }



	/**
	 * post: 判断用户账或者昵称是否已存在
	 * path: check_user_account
	 * param: account  用户账号（邮箱/手机/昵称）
	 */

	public function check_user_account($account){

		$Users=new Users();
		$res = $Users->check_account($account);
		if($res == false){
			$data['code'] = 1;
			$data['msg'] = '未被占用';
		}else{
			$data['code'] = 0;
			$data['msg'] = '已被占用';
		}

		return json($data);

	}



	/**
	 * post: 注册
	 * path: register
	 * method: register
	 * param: account - {string} 手机号
	 * param: password - {string} 密码
	 * param: head_img - {string} 头像
	 */

	public function register($account,$password,$verify,$tusername="") {

		$verify_expire = session('verify_expire');
		if(!isset($verify_expire) || time()>$verify_expire){
			$data['code'] = 0;
			$data['msg'] = '验证码过期';
			return json($data);
		}

        $s_verify= session('verify_'.$account);
        if($s_verify != $verify){
            $data['code'] = 0;
            $data['msg'] = '验证码错误';
            return json($data);
        }

		if($tusername){
		    $info = Db::name('member')->field('id')->where(['account'=>$tusername])->find();
		    if($info){
		        $tuid = $info['id'];
            }else{
                return json(['status' => 0, 'msg' => '推荐人不存在', 'data' => '']);
            }
        }else{
		    $tuid = 0;
        }

		if ($account != '' || $password != '') {
			$Users=new Users();
			$res = $Users->reg($account, $password,$tuid,$tusername);
			if ($res)
			{
				$data['code'] = 1;
				$data['msg'] = '注册成功';
			} else {
				$data['code'] = 0;
				$data['msg'] = '注册失败';
			}
		} else {
			$data['code'] = 0;
			$data['msg'] = '参数错误';
		}

		return json($data);	

    }





    //上传头像

    public function upload(){

		$up = new Upload();
		$back = $up->upload();
		$back['info'] = $back['data'];
		unset($back['data']);
		return json($back);

    }

	//修改资料

	public function update_info(){

		$uid = get_uid();
		if($uid > 0){
			$param = input('post.');
			$Users=new Users();
			$res = $Users->update_info($uid,$param);
			if ($res)
			{
				$data['code'] = 1;
				$data['msg'] = '修改成功';

			} else {
				$data['code'] = 0;
				$data['msg'] = '修改失败';
			}
		}else{
			$data['code'] = 0;
			$data['msg'] = '未登录';
		}
		return json($data);

	}

	//修改密码

	public function update_password($old_pwd,$new_pwd){

		$uid = get_uid();
		if($uid > 0){
			$Users=new Users();
			$res = $Users->update_pwd($uid,$old_pwd,$new_pwd);
			if ($res)
			{
				$data['code'] = 1;
				$data['msg'] = '密码修改成功';
			} else {
				$data['code'] = 0;
				$data['msg'] = '密码修改失败';
			}

		}else{
			$data['code'] = 0;
			$data['msg'] = '未登录';
		}
		return json($data);	

	}



    //发送码验证码
    //$id		消息标识id
    //$param	发送地址
    //$type		发送方式：sms/email   短信或邮件
    public function send_verify($id,$param,$type,$content=''){
        if(!isMobile($param)){
            return json(['code'=>0,'msg'=>'手机号码不合法']);
        }
        $verify_send_time = session('verify_send_time');
        if(isset($verify_send_time) && time()<$verify_send_time+60){
            $data['code'] = 0;
            $data['msg'] = '验证码发送过于频繁，请稍后发送';
        }else{
            $verify = rand(100000,999999);

            if($type == 'sms'){
                $data = \app\common\service\Msg :: send_sms($id,$param,array('name'=>$param,'code'=>$verify,'content'=>$content));
            }
            if($data['code'] == 1){
                session('verify_'.$param,$verify);
                session('verify_expire',time()+300);
                session('verify_send_time',time());
            }
        }
        return json($data);
    }


	

	//找回密码--设置新密码

	public function getback_password($account,$password,$verify){

		$verify_expire = session('verify_expire');
		if(isset($verify_expire) && time()<$verify_expire){
			if(session('verify_'.$account) == $verify){
				$Users=new Users();
				$res = $Users->edit_pwd($account,$password);
				if ($res)
				{
					$data['code'] = 1;
					$data['msg'] = '密码修改成功';
				} else {
					$data['code'] = 0;
					$data['msg'] = '密码修改失败';
				}

			}else{
				$data['code'] = 0;
				$data['msg'] = '验证码错误';
			}
		}else{
			$data['code'] = 0;
			$data['msg'] = '验证码已失效';
		}
		return json($data);

	}

	//用户站内信列表

	public function msg_list($page,$pagesize=10){

		$uid = get_uid();
		if($uid > 0){
			$User = new Users();
			$data['code'] = 1;
			$data['info'] = $User->get_msg($uid,$page,$pagesize);

		}else{

			$data['code'] = 0;
			$data['msg'] = '未登录';
		}
		return json($data);

	}

	//查看消息

	public function read_msg($id){

		$uid = get_uid();
		if($uid > 0){
			$User = new Users();
			$data['code'] = 1;
			$data['info'] = $User->read_msg($uid,$id);

		}else{
			$data['code'] = 0;
			$data['msg'] = '未登录';
		}

		return json($data);

	}

	//删除消息

	public function del_msg($id){

		$uid = get_uid();

		if($uid > 0){
			$User = new Users();
			$res = $User->del_msg($uid,$id);
			if($res == false){
				$data['code'] = 0;
				$data['msg'] = '删除成功';
			}else{
				$data['code'] = 1;
				$data['msg'] = '删除失败';
			}
		}else{
			$data['code'] = 0;
			$data['msg'] = '未登录';
		}
		return json($data);

	}



	//充值记录

	public function paylog(){

		$uid =get_uid();
		$Users=new Users();
		return json($Users->userMoneyLog($uid,['status'=>1,'act'=>1]));



	}

	//消费记录

	public function moneylog(){

		$uid =get_uid();
		$Users=new Users();
		return json($Users->userMoneyLog($uid));

	}

	//羊币记录

	public function integrallog(){

		$uid =get_uid();
		$Users=new Users();
		return json($Users->userIntegralLog($uid));

	}



	//充值 $type1 微信

	public function wap_recharge($money,$pay_type = 'alipay'){

		$uid =get_uid();
		if(!$uid){
			return json(['code'=>0,'data'=>'','msg'=>'用户没有登录']);
		}

		$Users=new Users();
		$redata=$Users->recharge($uid,$money);
		if($redata['code'] != 1){
			return json($redata);
		}

		$data=$redata['data'];
		$Payment=new Payment();

		if ($pay_type == 'alipay') {
		    //	$res =$Payment->ali_wap($data['uid'],'用户充值',$data['out_trade_no'],$data['money'],['method'=>'chongzhi','param'=>[]],url('wap/usercenter/usercenter','','',true));
              $res =  $Payment->ali_app($data['uid'],'用户充值',$data['out_trade_no'], $data['money'],['method'=>'chongzhi','param'=>[]]);
		} else {

		    if(is_weixin()){
                $openid = Db::name('oauth_user')->where(['from' => 'weixin', 'uid' => $uid])->value('openid');
                $res = $Payment->wx_pub($openid, $uid, '用户充值', $data['out_trade_no'],$data['money'], ['method'=>'chongzhi','param'=>[]],'用户充值');
            }else{
                $res =  $Payment->wx_app($uid,'用户充值', $data['out_trade_no'], $data['money'],['method'=>'chongzhi','param'=>[]],'用户充值');

            }




		}

		return json($res);

	}


    /**
     * 游戏会员分享二维码调取
     *@param	$uid	    会员id
     */
    public function shaer_url(){
        $uid = get_uid();
        if($uid > 0) {
            //加密uid
            $tma=  Db::name('member')->where(['id'=>$uid])->value('account');
            $url= 'http://'.$_SERVER['HTTP_HOST'].'/wap/index/register.html?track_id='.$tma;
            $ewm = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$url;
            $data = ['code' => 1, 'data' =>['qrcode'=>$ewm] , 'msg' => ''];
        }else{
            $data = ['code' => 0, 'data' => '', 'msg' => '未登录'];
        }
        return json($data);
    }

    //一级推荐人列表
    //@param	$uid	会员id
    public function refer_user1($page=1){
        $uid = get_uid();
        if($uid > 0) {
            $map['tuid'] = $uid;
            $map['closed'] = 0;
            $map['id'] = array('gt',1);
            $Nowpage = $page;
            $limits = config('list_rows');// 获取总条数
            $res = Db::name('member')->order('id desc')->field('id,account,nickname,head_img,yang_count,yang_ing,create_time')->where($map)->page($Nowpage, $limits)->select();
            if ($res) {
                return json(['status' => 1, 'data' => $res, 'msg' => '']);
            } else {
                return json(['status' => 0, 'data' => '', 'msg' => '没有记录']);
            }
        }else{
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }

    }


    //二级推荐人列表
    //@param	$uid	会员id
    public function refer_user2($page=1){
        $uid = get_uid();
        if($uid > 0) {
            //一级会员id
            $map['tuid']=$uid;
            $map['closed'] = 0;
            $map['id'] = array('gt',1);
            $userall =Db::name('member')->field('id')->where($map)->select();
            $userid = "";
            foreach($userall as $value){
                if($userid){
                    $userid =$userid. ",".$value['id'];
                }else{
                    $userid = $value['id'];
                }
            }

            $map['tuid'] = array('in',$userid);
            $map['closed'] = 0;
            //$map['id'] = array('gt',12000);
            $Nowpage = $page;
            $limits = config('list_rows');// 获取总条数
            $res =Db::name('member')->order('id desc')->field('id,account,nickname,head_img,yang_count,yang_ing,create_time')->where($map)->where(['tuid'=>['>',1]])->page($Nowpage, $limits)->select();
            if($res){
                return json(['status'=>1,'data'=>$res,'msg'=>'']);
            }else{
                return json(['status'=>0,'data'=>'','msg'=>'没有记录']);
            }
        }else{
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
    }



    //绑定支付
    public function zfb($zfb_name,$zfb_hao,$verify){
        $uid = get_uid();
        if($uid > 0) {
            if(!$zfb_name || !$zfb_hao || !$verify){ return json(['code' => 0, 'data' => '', 'msg' => '参数错误']); }

            $verify_expire = session('verify_expire');
            if(!isset($verify_expire) || time()>$verify_expire){
                $data['code'] = 0;
                $data['msg'] = '验证码过期';
                return json($data);
            }

            $param['zfb_name'] = $zfb_name;
            $param['zfb_hao'] = $zfb_hao;
            $Users=new Users();
            $userinfo = $Users->userinfo($uid);
            $s_verify= session('verify_'.$userinfo['mobile']);
            if($s_verify != $verify){
                $data['code'] = 0;
                $data['msg'] = '验证码错误';
                return json($data);
            }
            $res = $Users->update_info($uid,$param);
            if ($res) {
                $data['code'] = 1;
                $data['msg'] = '支付绑定成功';
            }else{
                $data['code'] = 0;
                $data['msg'] = '支付绑定失败';
            }
            return json($data);
        }else{
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
    }


    //绑定银行卡支付
    public function yhk($yinhang,$huming,$kahao,$verify){
        $uid = get_uid();
        if($uid > 0) {
            if(!$yinhang || !$huming || !$kahao || !$verify){ return json(['code' => 0, 'data' => '', 'msg' => '参数错误']); }
            $verify_expire = session('verify_expire');
            if(!isset($verify_expire) || time()>$verify_expire){
                $data['code'] = 0;
                $data['msg'] = '验证码过期';
                return json($data);
            }


            $param['yinhang'] = $yinhang;
            $param['huming'] = $huming;
            $param['kahao'] = $kahao;
            $Users=new Users();

            $userinfo = $Users->userinfo($uid);
            $s_verify= session('verify_'.$userinfo['mobile']);
            if($s_verify != $verify){
                $data['code'] = 0;
                $data['msg'] = '验证码错误';
                return json($data);
            }

            $res = $Users->update_info($uid,$param);
            if ($res) {
                $data['code'] = 1;
                $data['msg'] = '银行卡绑定成功';
            }else{
                $data['code'] = 0;
                $data['msg'] = '银行卡绑定失败';
            }
            return json($data);
        }else{
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
    }

    //提现
    public function withdrawal($money,$type){
        $uid = get_uid();
        if($uid > 0) {
            //添加记录
            $Withdraw=new Withdraw();
            return json($Withdraw->tixian($uid,$money,$type));
        }else{
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
    }


    //提现记录
    public function withdraw(){
        $uid =get_uid();
        $map['uid']=$uid;
        $Withdraw=new Withdraw();
        $res =$Withdraw->order('create_time desc')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'没有记录'];
        }

        return json($Users->withdrawLog($uid));
    }

    /**
     * 好友基本信息
     * yuid  好友id
     */
    public function hymember_yang($yuid){
        $uid = get_uid();
        if(!$uid) {
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
        if(!$yuid){
            return json(['code' =>0, 'data' => '', 'msg' => '参数错误']);
        }
        $reyang = Db::name('member')->field('id,account,mobile,head_img,integral,yang_ing,tuid')->where(['id'=>$yuid])->find();
        if(!$reyang || $reyang['tuid']<=0){
            return json(['code' =>0, 'data' => '', 'msg' => '非好友牧场']);
        }
        $treyang = Db::name('member')->field('tuid')->where(['id'=>$reyang['tuid']])->find();
        if($reyang['tuid']!=$uid && $treyang['tuid']!=$uid){
            return json(['code' =>0, 'data' => '', 'msg' => '非好友信息']);
        }else{
            return json(['code' =>1, 'data' => $reyang, 'msg' => '']);
        }

    }



    /**
     * 判断是否是好友牧场
     * yuid  好友id
     */
    public function refer_yang($yuid){
        $uid = get_uid();
        if(!$uid) {
            return json(['code' => -1, 'data' => '', 'msg' => '未登录']);
        }
        if(!$yuid){
            return json(['code' =>0, 'data' => '', 'msg' => '参数错误']);
        }
        $reyang = Db::name('member')->field('tuid')->where(['id'=>$yuid])->find();
        if(!$reyang || $reyang['tuid']<=0){
            return json(['code' =>0, 'data' => '', 'msg' => '非好友牧场']);
        }
        $treyang = Db::name('member')->field('tuid')->where(['id'=>$reyang['tuid']])->find();
        if($reyang['tuid']!=$uid && $treyang['tuid']!=$uid){
            return json(['code' =>0, 'data' => '', 'msg' => '非好友牧场']);
        }else{
            return json(['code' =>1, 'data' => '', 'msg' => '好友牧场']);
        }

    }

    /*完善用户信息*/
    public function perfect_info(){
        $perfect_info =session('perfect_info');
        if(!$perfect_info){
            return json(['code'=>0,'msg'=>'微信用户信息不存在']);
        }
        $phone = input('phone','');
        $verify = input('verify','');
        $password = input('password',0);
        if(!$phone || !$verify || !$password){
            return json(['code'=>0,'msg'=>'参数未填写完整~']);
        }
        $check_verify = $this->check_verify($phone,$verify);
        if($check_verify['code'] != 1){
            return json($check_verify);
        }
        //这个地方占用也无所谓
        $Users=new Users();
        $user = Db::name('member')->where(['account'=>$phone])->find();
        if($user){
            //已经存在了 就直接做关联
            $uid =$user['id'];
            Db::name('oauth_user')->where(['id'=>$perfect_info['id']])->update(['uid'=>$uid]);
            $Users->autoLogin($user);
            return json(['code'=>1,'msg'=>'创建成功']);
            $this->redirect(_get_login_redirect());
        }else{
            $ip=request()->ip();
            $referid=0;
            $reusername='';
            $nember= array(
                'nickname' => $perfect_info['nick'],
                'head_img' => $perfect_info['avatar'],
                'update_time' =>time(),
                'last_login_ip' => $ip,
                'group_id'=>config('default_user_group'),
                'login_num' =>'1',
                'status'=>'1',
                'closed'=>'0',
                'account'=>$phone,
                'mobile'=>$phone,
                'password'=>md5(md5($password) . config('auth_key')),
                'tuid'=>$referid,
                'tusername'=>$reusername,

            );
            $uid =  Db::name('member')->insert($nember,false,true);
            $nember['id']=$uid;
            //更新$reusername
            Db::name('oauth_user')->where(['id'=>$perfect_info['id']])->update(['uid'=>$uid]);
            $Users->autoLogin($nember);
            return json(['code'=>1,'msg'=>'创建成功']);

        }
    }

    public function img_verify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->codeSet = '0123456789';
        $verify->length = 4;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }

    public function send_verify2($phone,$img_code){
        $verify = new Verify();
        if($verify->check($img_code) !== true){
            return json(['code'=>0,'msg'=>'图片验证码错误']);
        }
        if(!isMobile($phone)){
            return json(['code'=>0,'msg'=>'手机号码不合法']);
        }
        $verify_info = session('verify_'.$phone);
        if(isset($verify_info) && time()<$verify_info['verify_send_time']+120){
            $data['code'] = 0;
            $data['msg'] = '验证码发送过于频繁，请稍后发送';
        }else{
            $verify = rand(100000,999999);
            $data = \app\common\service\Msg :: send_sms(1,$phone,array('code'=>$verify,'content'=>''));
            //$data['code'] = 1;
            if($data['code'] == 1){
                $verify_info = [
                    'verify'  => $verify,
                    'verify_expire'  => time()+300,
                    'verify_send_time'  => time()
                ];
                session('verify_'.$phone,$verify_info);
            }
        }
        return json($data);
    }
    //校验验证码
    private function check_verify($phone,$verify){
        $verify_info = session('verify_'.$phone);
        if(!isset($verify_info)){
            return ['code'=>0,'msg'=>'验证码无效~'];
        }
        if(time()>$verify_info['verify_expire']){
            return ['code'=>0,'msg'=>'验证码过期~'];
        }
        $s_verify = $verify_info['verify'];
        if($s_verify != $verify){
            return ['code'=>0,'msg'=>'验证码错误~'];
        }else{
            session('verify_'.$phone,null);
            return ['code'=>1,'msg'=>'验证码通过~'];
        }
    }


}