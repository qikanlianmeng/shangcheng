<?php
namespace app\wap\controller;

class Order extends Base{
	/**
	* @Title: comment_list
	* @Description: 评论列表
	* @param
	* @return
	 */
	public function comment_list(){
		$this->set_seo('评论列表_羊羊商城');
		return view();
	}
	/**
	* @Title: comment
	* @Description: 订单评价
	* @param
	* @return
	 */
	public function comment(){
		$this->set_seo('订单评价_羊羊商城');
		return view();
	}
	/**
	* @Title: firm_order
	* @Description: 确认订单
	* @param
	* @return
	 */
	public function firm_order(){
		$this->set_seo('确认订单_羊羊商城');
		return view();
	}
	/**
	* @Title: order_detail
	* @Description: 订单详情
	* @param
	* @return
	 */
	public function order_detail(){
		$this->set_seo('订单详情_羊羊商城');
		return view();
	}
	/**
	* @Title: order_list
	* @Description: 订单管理
	* @param
	* @return
	 */
	public function order_list(){
		$this->set_seo('订单管理_羊羊商城');
		return view();
	}
	/**
	* @Title: select_address
	* @Description: 选择收货地址
	* @param
	* @return
	 */
	public function select_address(){
		$this->set_seo('收货地址管理_羊羊商城');
		return view();
	}
	/**
	* @Title: wuliu
	* @Description: 物流详情
	* @param
	* @return
	 */
	public function wuliu(){
		$this->set_seo('物流详情_羊羊商城');
		return view();
	}
	/*支付订单*/
	public function pay_order(){
		$this->set_seo('支付订单_羊羊商城');
		return view();
	}
	/*支付成功*/
	public function pay_success(){
		$this->set_seo('支付成功_羊羊商城');
		return view();
	}
	/*支付失败*/
	public function pay_fail(){
		$this->set_seo('支付失败_羊羊商城');
		return view();
	}
}
