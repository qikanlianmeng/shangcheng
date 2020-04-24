<?php
namespace app\index\controller;

use app\api\controller\PayNotify;
use app\common\model\IntegralLog;
use app\common\model\MoneyLog;
use app\common\service\Member;
use app\common\service\Payment;
use app\common\service\Users;

class Index 
{
    
    public function index(){
		if(get_uid()) $this->redirect('index');
		return view();
	}
    public function about(){
		if(get_uid()) $this->redirect('about');
		return view();
	}
    public function help(){
		if(get_uid()) $this->redirect('help');
		return view();
	}
    public function mobile(){
		if(get_uid()) $this->redirect('mobile');
		return view();
	}



}
