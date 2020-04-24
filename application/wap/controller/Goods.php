<?php
namespace app\wap\controller;


class Goods extends Base{

	/**
	* @Title: 分类
	* @Description:
	* @param
	 */
	public function classification(){
		$this->set_seo('分类_商城');
		return view();
	}
	/**
	* @Title: 羊币抵扣
	* @Description:
	* @param
	* @return
	 */
	public function deduction(){
		$this->set_seo('羊币抵扣_商城');
		return view();
	}
	/**
	* @Title: 我的足迹
	* @Description:
	* @param
	* @return
	 */
	public function footprint(){
		$this->set_seo('我的足迹_商城');
		return view();
	}
	/**
	 *
	* @Title: 商品详情
	* @Description:
	* @param
	* @return
	 */
	public function goods(){
		$this->set_seo('商品详情_商城');
		return view();
	}
	/**
	 *
	* @Title: 热门推荐
	* @Description:
	* @param
	* @return
	 */
	public function hotgoods(){
		$this->set_seo('热门推荐_商城');
		return view();
	}
	/**
	* @Title: 羊币
	* @Description:
	* @param
	* @return
	 */
	public function integralshop(){
		$this->set_seo('羊币商城_商城');
		return view();
	}
	/**
	* @Title: 注意事项
	* @Description:
	* @param
	* @return
	 */
	public function introduce(){
		$this->set_seo('注意事项_商城');
		return view();
	}
	/**
	* @Title: 返羊币
	* @Description:
	* @param
	* @return
	 */
	public function returnintegral(){
		$this->set_seo('购物返金币_商城');
		return view();
	}
	/**
	* @Title: 分类进入搜索列表
	* @Description:
	* @param
	* @return
	 */
	public function searchlist(){
		$this->set_seo('分类商品_商城');
		return view();
	}
	/**
	* @Title: 搜索列表
	* @Description:
	* @param
	* @return
	 */
	public function searchlist_name(){
		$this->set_seo('搜索结果_商城');
		return view();
	}
	/**
	* @Title: 秒杀
	* @Description:
	* @param
	* @return
	 */
	public function seckill(){
		return view();
	}
}
