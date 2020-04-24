<?php

namespace app\admin\controller;
use app\admin\model\MsgConfig;
use think\Request;
use app\common\service\MsgApi;
//use app\common\model\Freight;

class Remind extends Base
{
	private $m;
	public function _initialize()
    {
		parent::_initialize();
		$this->m = new MsgConfig();
    }

   //消息提醒列表
    public function index(){
		$info = $this->m->get_msg_list();
		$this->assign("list",$info['list']);
		$this->assign("page",$info['page']);
        return $this->fetch();
    }
	//消息提醒编辑页面
    public function add()
    {	
		$id = input('param.id');
		if($id >0){
			$this->assign('info',$this->m->get_msg_info($id));
		}
        return $this->fetch();
    }
	//消息提醒 编辑处理
	public function edit()
    {
		$data = input("post."); // 获取经过过滤的全部post变量

		$back = array('code'=>0,'msg'=>'操作异常');
		$data['auth'] = array_sum($data['auth']);
		$data['param'] = serialize($data['param']);
		if(isset($data['id']) && $data['id']>0){
			//修改
			$id = $data['id'];
			unset($data['id']);
			if($this->m->save($data,['id'=>$id])){
				$back = array('code'=>1,'msg'=>'修改成功');
			}
		}else{
			//添加
			if($this->m->save($data)){
				unset($data['id']);
				$back = array('code'=>1,'msg'=>'添加成功');
			}
		}
		echo json_encode($back);
		//if (Request::instance()->isPost()) echo "当前为 POST 请求";
        
    }
	//异步修改消息的发送权限
	function edit_auth(){
		$id = input("post.id");
		$auth = input("post.auth");
		$act = input("post.act");
		$back = array("code"=>0,"msg"=>'操作异常');
		if($id>0 && $auth>0 && in_array($act,array("add","cut"))){
			$user_auth = $this->m->where('id',$id)->value('auth');
			if($act == 'add'){
				$new_auth = $user_auth+$auth;
			}elseif($act == 'cut'){
				$new_auth = $user_auth-$auth;
			}
			if($this->m->where('id',$id)->update(['auth'=>$new_auth])){
				$back = array("code"=>1,"msg"=>'ok');
			}
		}
		echo json_encode($back);
	}
	
	//删除信息
	 public function del()
    {
		$id = input('post.id');
		if($this->m->where('id',$id)->delete()){
			echo true;
		}else{
			echo false;
		}
    }
	
	public function template(){
		$info = $this->m->get_msg_list();
		$this->assign("list",$info['list']);
		$this->assign("page",$info['page']);
		return $this->fetch();
	}
	//消息的模板编辑页
	public function templateinfo(){
		$id = input('param.id');
		$tpname = input('param.tpname');
		$info = $this->m->get_msg_info($id);
		$tp_arr = array('sms'=>'短信','email'=>'邮件','mail'=>'站内信','weichat'=>'微信','push'=>'推送');
		
		$this->assign('tpname_zh',$tp_arr[$tpname]);
		$this->assign('info',$info);
		$tpinfo = unserialize($info[$tpname]);

		
		$this->assign('tpinfo',$tpinfo);
		return $this->fetch();
	}
	//编辑消息模板
	public function edit_tp(){
		$back = array('code' => 0,'msg'=>'操作异常');
		$data = input('post.');
		if($data['id']>0 && $data['tpname']){
			$id = $data['id'];
			$tpname = $data['tpname'];
			unset($data['id']);
			unset($data['tpname']);
			$data = serialize($data);

			if($this->m->where('id',$id)->update([$tpname=>$data])){
				$back = array('code' => 1,'msg'=>'保存成功');
			}
		}else{
			$back['msg'] = '参数异常';
		}
		echo json_encode($back);
	}
	public function test(){
		
		//发送消息主方法，登陆后才能调用，返回一个数组
		//参数分别为: 后台消息标识id，接收人id，与后台对应消息设置的预定义变量一一对应的参数数组
		//\app\common\service\Msg :: send(1,12,array('name'=>'张三','verify'=>'110026','age'=>11));
		
		//未登录状态下，发送手机或者邮件信息，用于用户获取验证码
		//参数分别为: 后台消息标识id，手机/邮箱，与后台对应消息设置的预定义变量一一对应的参数数组
		//$res = \app\common\service\Msg :: send(4,212072,array('nam'=>'张三','order_id'=>'110026'));
		//\app\common\service\Msg :: send_email(1,'4411@qq.com',array('name'=>'张三','verify'=>'110026','age'=>11));
		
		//print_r($res);
	}

}
