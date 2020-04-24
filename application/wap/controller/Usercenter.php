<?php
namespace app\wap\controller;

class Usercenter extends Base {

    public function index(){
        $this->redirect('wap/index/index');
    }
	/**
	* @Title: add_address
	* @Description: 新增收货地址
	* @param
	* @return
	 */
	public function qr(){
		$this->set_seo('分享_商城');
		return view();
	}
	
	/**
	* @Title: add_address
	* @Description: 新增收货地址
	* @param
	* @return
	 */
	public function add_address(){
		$this->set_seo('新增收货地址_商城');
		return view();
	}
	/**
	* @Title: address_list
	* @Description: 管理收货地址
	* @param
	* @return
	 */
	public function address_list(){
		$this->set_seo('管理收货地址_商城');
		return view();
	}
	/**
	* @Title: collection
	* @Description: 我的收藏
	* @param
	* @return
	 */
	public function collection(){
		$this->set_seo('我的收藏_商城');
		return view();
	}
	/**
	* @Title: information
	* @Description: 消息中心
	* @param
	* @return
	 */
	public function information(){
		$this->set_seo('消息中心_商城');
		return view();
	}
	/**
	* @Title: information
	* @Description: 系统消息
	* @param
	* @return
	 */
	public function systemmessage(){
		$this->set_seo('系统消息_商城');
		return view();
	}
	/**
	* @Title: refund_detail
	* @Description: 退款详情
	* @param
	* @return
	 */
	public function refund_detail(){
		$this->set_seo('退款详情_商城');
		return view();
	}
	/**
	* @Title: refund_list
	* @Description: 退款记录
	* @param
	* @return
	 */
	public function refund_list(){
		$this->set_seo('退款记录_商城');
		return view();
	}
	/**
	* @Title: refund
	* @Description: 申请退款
	* @param
	* @return
	 */
	public function refund(){
		$this->set_seo('申请退款_商城');
		return view();
	}
	/**
	* @Title: usercenter
	* @Description: 个人中心
	* @param
	* @return
	 */
	public function usercenter(){
		$this->set_seo('个人中心_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 我的学币
	* @param
	* @return
	 */
	public function mycoin(){
		$this->set_seo('我的余额_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币充值记录
	* @param
	* @return
	 */
	public function coinrecharged(){
		$this->set_seo('余额充值记录_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币花费记录
	* @param
	* @return
	 */
	public function spendcoin(){
		$this->set_seo('余额花费记录_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 我的羊币
	* @param
	* @return
	 */
	public function myintegral(){
		$this->set_seo('我的羊币_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 羊币充值记录
	* @param
	* @return
	 */
	public function integralrecharged(){
		$this->set_seo('羊币充值记录_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 花费羊币记录
	* @param
	* @return
	 */
	public function spendintegral(){
		$this->set_seo('花费羊币记录_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 学币充值
	* @param
	* @return
	 */
	public function buycoin(){
		$this->set_seo('余额充值_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 注册
	* @param
	* @return
	 */
	public function register(){
		$this->set_seo('注册_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 找回密码
	* @param
	* @return
	 */
	public function find_password(){
		$this->set_seo('找回密码_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 修改资料
	* @param
	* @return
	 */
	public function editinfo(){
		$this->set_seo('修改资料_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 消息详情
	* @param
	* @return
	 */
	public function message_detail(){
		$this->set_seo('消息详情_商城');
		return view();
	}
	/**
	* @Title:
	* @Description: 购买学币失败
	* @param
	* @return
	 */
	public function buycoin_fail(){
		$this->set_seo('购买羊币失败_商城');
		return view();
	}
	public function share(){
		$this->set_seo('我的分享_商城');
		return view();
	}
	public function team(){
		$this->set_seo('我的团队_商城');
		return view();
	}
	public function recharge(){
		$this->set_seo('提现_商城');
		return view();
	}
	public function addcard(){
		$this->set_seo('绑定银行卡_商城');
		return view();
	}
	public function binding(){
		$this->set_seo('绑定支付宝_商城');
		return view();
	}
	public function recharge_list(){
		$this->set_seo('提现记录_商城');
		return view();
	}

	public function perfect_info(){
	    $this->set_seo('完善资料_商城');
		return view();
    }
	public function artical(){
	    $this->set_seo('文章详情_商城');
		return view();
    }
	public function news(){
	    $this->set_seo('新闻_商城');
		return view();
    }
}
