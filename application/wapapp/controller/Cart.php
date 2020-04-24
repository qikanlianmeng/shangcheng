<?php
namespace app\wapapp\controller;


class Cart extends Base{
	/**
	* @Title: shopcar
	* @Description: 购物车
	* @param 
	* @return
	 */
	public function shopcar(){
		$this->set_seo('我的购物车');
		return View();
	}
}