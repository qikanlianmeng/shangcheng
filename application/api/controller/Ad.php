<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 广告
 */
class Ad
{
	/**
	获得wap首页广告列表
	*/
	public function wap_index(){
		$list = array(
						1 =>	get_adinfo(29,$type='list'),//轮播广告列表
						2 =>	get_adinfo(30),
						3 =>	get_adinfo(31),
						4 =>	get_adinfo(32),
						5 =>	get_adinfo(33)
						);
		return json($list);
	}
    public function yang_index(){
        $list = array(
            1 =>	get_adinfo(34,$type='list'),//轮播广告列表
        );
        return json($list);
    }
}