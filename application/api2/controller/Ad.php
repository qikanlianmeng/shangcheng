<?php

namespace app\api2\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 广告
 */
class Ad extends Base
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
    public function index(){
        $list = get_adinfo(29,$type='list');
        return json(['status'=>1,'list'=>$list]);
    }
    public function index_btn(){
        $list = get_adinfo(34,$type='list');
        return json(['status'=>1,'list'=>$list]);
    }
    public function center_btn(){
        $list = get_adinfo(35,$type='list');
        return json(['status'=>1,'list'=>$list]);
    }
    //统计更新用户新增的create_dl_income，create_dy_income两个字段的值
    /* public  function update_create_dl_income(){
        set_time_limit(0);
        $income_list = Db::name('log_income')->where(['type'=>['in',[1,5,6,16,17]],'income_uid'=>['>',0]])->field('sum(money) as total,income_uid')->group('income_uid')->select();
        foreach($income_list as $v){
            Db::name('member')->where('id',$v['income_uid'])->update(['create_dl_income'=>$v['total']]);
        }
        echo 'done';
    }
    public  function update_create_dy_income(){
        set_time_limit(0);
        $income_list = Db::name('log_income')->where(['type'=>['in',[2,3,4,7]],'income_uid'=>['>',0]])->field('sum(money) as total,income_uid')->group('income_uid')->select();
        foreach($income_list as $v){
            Db::name('member')->where('id',$v['income_uid'])->update(['create_dy_income'=>$v['total']]);
        }
        echo 'done';
    } */
    /* public function test_income(){
        \app\common\service\Income::buy_income();
    } */
}