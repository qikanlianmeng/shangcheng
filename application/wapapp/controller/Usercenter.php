<?php
namespace app\wapapp\controller;
use think\session;
use think\Db;
use think\Request;

class Usercenter extends Base {

    public function index(){
        $this->redirect('wapapp/index/index');
    }
	/**
	* @Title: add_address
	* @Description: 新增收货地址
	* @param
	* @return
	 */
	public function qr(){
		$this->set_seo('分享');
		return view();
	}
	
	/**
	* @Title: add_address
	* @Description: 新增收货地址
	* @param
	* @return
	 */
	public function add_address(){
		$this->set_seo('新增收货地址');
		return view();
	}
	/**
	* @Title: address_list
	* @Description: 管理收货地址
	* @param
	* @return
	 */
	public function address_list(){
		$this->set_seo('管理收货地址');
		return view();
	}
	/**
	* @Title: collection
	* @Description: 我的收藏
	* @param
	* @return
	 */
	public function collection(){
		$this->set_seo('我的收藏');
		return view();
	}
	/**
	* @Title: information
	* @Description: 消息中心
	* @param
	* @return
	 */
	public function information(){
		$this->set_seo('消息中心');
		return view();
	}
	/**
	* @Title: information
	* @Description: 系统消息
	* @param
	* @return
	 */
	public function systemmessage(){
		$this->set_seo('系统消息');
		return view();
	}
	/**
	* @Title: refund_detail
	* @Description: 退款详情
	* @param
	* @return
	 */
	public function refund_detail(){
		$this->set_seo('退款详情');
		return view();
	}
	/**
	* @Title: refund_list
	* @Description: 退款记录
	* @param
	* @return
	 */
	public function refund_list(){
		$this->set_seo('退款记录');
		return view();
	}
	/**
	* @Title: refund
	* @Description: 申请退款
	* @param
	* @return
	 */
	public function refund(){
		$this->set_seo('申请退款');
		return view();
	}
	/**
	* @Title: usercenter
	* @Description: 个人中心
	* @param
	* @return
	 */
	public function usercenter(Request $request){
		$this->set_seo('个人中心');
		if(is_weixin()){
      $appid='wx36eb1cf950efdc18';
      $secrect='71fb2daeb4d085d1023295d08f6d8b79';
      $jssdk = jssdk($appid, $secrect);
      $this->assign('jssdk', $jssdk);
		  $this->assign('weixin',1);
    }else{
		  $arr['appId']='123';
      $arr['timestamp']='123';
      $arr['nonceStr']='123';
      $arr['signature']='123';
      $this->assign('jssdk', $arr);
      $this->assign('weixin',2);
    }
		return view();
	}
	/**
	* @Title:
	* @Description: 我的学币
	* @param
	* @return
	 */
	public function mycoin(){
		$this->set_seo('我的余额');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币充值记录
	* @param
	* @return
	 */
	public function coinrecharged(){
		$this->set_seo('余额充值记录');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币花费记录
	* @param
	* @return
	 */
	public function spendcoin(){
		$this->set_seo('余额花费记录');
		return view();
	}
	/**
	* @Title:
	* @Description: 我的
	* @param
	* @return
	 */
	public function myintegral(){
		$this->set_seo('我的积分');
		return view();
	}
	/**
	* @Title:
	* @Description: 充值记录
	* @param
	* @return
	 */
	public function integralrecharged(){
		$this->set_seo('充值记录');
		return view();
	}
	/**
	* @Title:
	* @Description: 花费记录
	* @param
	* @return
	 */
	public function spendintegral(){
		$this->set_seo('我的余额');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币充值
	* @param
	* @return
	 */
	public function buycoin(){
		$this->set_seo('余额充值');
		return view();
	}
	/**
	* @Title:
	* @Description: 注册
	* @param
	* @return
	 */
	public function register(){
		$this->set_seo('注册');
		return view();
	}
	/**
	* @Title:
	* @Description: 找回密码
	* @param
	* @return
	 */
	public function find_password(){
		$this->set_seo('找回密码');
		return view();
	}
	/**
	* @Title:
	* @Description: 修改资料
	* @param
	* @return
	 */
	public function editinfo(){
		$this->set_seo('修改资料');
		return view();
	}
	/**
	* @Title:
	* @Description: 消息详情
	* @param
	* @return
	 */
	public function message_detail(){
		$this->set_seo('消息详情');
		return view();
	}
	/**
	* @Title:
	* @Description: 购买学币失败
	* @param
	* @return
	 */
	public function buycoin_fail(){
		$this->set_seo('购买失败');
		return view();
	}
	public function share(){
		$this->set_seo('我的分享');
		return view();
	}
	public function team(){
		$this->set_seo('我的团队');
		return view();
	}
	public function recharge(){
		$this->set_seo('提现');
    $user_info=Session::get('user_auth');
    $uid=$user_info['uid'];
   //根据用户id获取当前用户的静态可提现，动态可提现，以及以往已经提现成功的
   $yusuan=DB::name('order')->where(['user_id' => $uid, 'pay_status' => 1])->where('pay_code','=',1)->where('pay_time','<',1586238961)->sum('total_amount');
    $jingti=DB::name('pay_log')->where(['uid' => $uid, 'status' => 1])->where('pay_type','>',1)->sum('money');
    $dongti=DB::name('log_income')->where(['uid' => $uid])->where('type','in',[1,3,4,5,6,7,16,17,18,19,20,21,22])->where('money','>',0)->sum('money');
    $yiti=DB::name('log_cash')->where(['uid' => $uid])->where('status','<',3)->sum('money');

	$this->assign('yusuan',$yusuan);
    $this->assign('jingti',$jingti);
    $this->assign('dongti',$dongti);
    $this->assign('yiti',$yiti);
    return view();
	}
	public function addcard(){
		$this->set_seo('绑定银行卡');
		return view();
	}
	public function bang(){
		$this->set_seo('绑定银行卡');
		return view();
	}
	public function recharge_list(){
		$this->set_seo('提现记录');
		return view();
	}

	public function perfect_info(){
	    $this->set_seo('完善资料');
		return view();
    }
	public function artical(){
	    $this->set_seo('文章详情');
		return view();
    }
	public function news(){
	    $this->set_seo('新闻');
		return view();
    }
	public function promote(){
	    $this->set_seo('我的推广');
		return view();
    }
	public function coupons(){
	    $this->set_seo('我的优惠券');
		return view();
    }
	public function pcode(){
	    $this->set_seo('我的虚拟码');
		return view();
    }
	public function commission(){
	    $this->set_seo('我的佣金');
		return view();
    }
	public function commission_list(){
	    $this->set_seo('佣金明细');
		return view();
    }
	public function binding_m(){
	    $this->set_seo('绑定手机号');
		return view();
    }
	public function auth(){
	    $this->set_seo('实名认证');
		return view();
    }
	public function setting(){
	    $this->set_seo('设置');
		return view();
    }
	public function lucky_list(){
	    $this->set_seo('抽奖活动');
		return view();
    }
	public function lucky(){
	    $this->set_seo('抽奖');
		return view();
    }
	public function lucky_log(){
	    $this->set_seo('抽奖记录');
		return view();
    }
	public function qianggou(){
	    $this->set_seo('抢购');
		return view();
    }
}
