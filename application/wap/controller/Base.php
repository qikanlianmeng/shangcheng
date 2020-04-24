<?php
namespace app\wap\controller;

use think\Controller;
use think\Db;
class Base extends Controller{
	public function _initialize()
	{
        $app_url = DB::name('config')->where('name','app_url')->value('value');
        $this->assign('app_url', $app_url);
		if ($uid = get_uid()) {

			//判断是否超时代付款订单
			$order = Db::name('order')->where(['user_id' => $uid, 'pay_status' => 0, 'order_status' => 0, 'shipping_status' => 0, 'add_time' => ['lt', time()-60*60*2],'order_prom_type'=>['neq',10]])->select();
			if ($order) {
				$orderModel = new \app\common\model\Order();
				foreach($order as $v) {
					$orderModel->cancel_order($uid, $v['order_id'], '订单超时取消');
				}
			}
		}else{
		    /*//判断是否在微信 是了就让他自动登录
            $action =strtolower(request()->action());
            $controller = strtolower(request()->controller());
            $reg=$controller."/".$action;

            if(is_weixin() && $reg != 'usercenter/perfect_info' && $reg != 'index/register'){
                $this->redirect('api/oalogin/login');
            }*/
        }
	}
	public function set_seo($title = '羊羊商城', $keywords = '，羊币商城，图书商城，，图书，网上商城', $description = '羊币商城，微信专题，网上商城，图书，初中高中教材购物平台，图书涵盖初中高中上百种类')
	{
		$this->assign('seo', ['title' => $title, 'keywords' => $keywords, 'description' => $description]);
	}
}